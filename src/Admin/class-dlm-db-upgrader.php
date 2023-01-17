<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_DB_Upgrader' ) ) {

	/**
	 * DLM_DB_Upgrader
	 */
	class DLM_DB_Upgrader {

		/**
		 * Holds the class object.
		 *
		 * @since 4.6.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * Class constructor
		 *
		 * @return void
		 * @since 4.6.0
		 */
		public function __construct() {

			// We need to add this because there are scenarios where the user deleted the download_log table.
			add_action( 'admin_init', array( $this, 'check_upgrade_necessity' ), 15, 1 );
		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Admin_Helper object.
		 * @since 4.6.0
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_DB_Upgrader ) ) {
				self::$instance = new DLM_DB_Upgrader();
			}

			return self::$instance;

		}

		/**
		 * Automatically recreate the upgrade environment if the download_log is not present
		 *
		 * @return false|void
		 */
		public function check_upgrade_necessity() {
			global $wpdb;
			if ( ! DLM_Utils::table_checker( $wpdb->download_log ) ) {
				DLM_Admin_Helper::redo_upgrade();
			}

			$this->check_upgrade_type();

			$this->init();
		}

		/**
		 * Init the upgrade process. Only actions after admin_init should be added here.
		 *
		 * @return void
		 */
		public function init(){
			// Don't do anything if we don't need to or if upgrader already done.
			if ( ! self::do_upgrade() ) {
				return;
			}

			// Add notice for user to update the DB.
			add_action( 'admin_notices', array( $this, 'add_db_update_notice' ), 8 );
			// Enqueue our upgrader scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_db_upgrader_scripts' ) );
			// Add our AJAX actions.
			add_action( 'wp_ajax_dlm_db_log_entries', array( $this, 'count_log_entries' ) );
			add_action( 'wp_ajax_dlm_upgrade_db', array( $this, 'update_log_table_db' ) );
			add_action( 'wp_ajax_dlm_alter_download_log', array( $this, 'alter_download_log_table' ) );
			add_action( 'wp_ajax_dlm_upgrade_db_clear_offset', array( $this, 'clear_offset' ) );

			$dlm_logging = get_option( 'dlm_enable_logging' );
			// Also add the new option to the DB and set it to default.
			add_option(
				'dlm_db_upgraded',
				array(
					'db_upgraded'   => '0',
					'using_logs'    => ( isset( $dlm_logging ) && '1' === $dlm_logging ) ? '1' : '0',
					'upgraded_date' => wp_date( 'Y-m-d' ) . ' 00:00:00',
				)
			);
		}

		/**
		 * Check to see if we need to upgrade
		 *
		 * @since 4.6.0
		 *
		 * @return bool
		 */
		public static function do_upgrade() {

			if ( ! is_admin() ) {
				return false;
			}

			if ( false !== get_transient( 'dlm_db_upgrade_offset' ) || false !== get_transient( 'dlm_upgrade_type' ) ) {
				return true;
			}

			if ( self::check_if_migrated() ) {
				return false;
			}

			if ( ! self::version_checker() ) {
				return false;
			}

			return true;
		}

		/**
		 * Check for version
		 *
		 * @return bool
		 */
		public static function version_checker() {

			if ( get_transient( 'dlm_needs_upgrade' ) && '1' === get_transient( 'dlm_needs_upgrade' ) ) {
				return true;
			}

			$installed_version = get_option( 'dlm_current_version' );

			if ( $installed_version && version_compare( $installed_version, DLM_UPGRADER_VERSION, '<' ) ) {

				set_transient( 'dlm_needs_upgrade', '1', 30 * DAY_IN_SECONDS );
				return true;
			}

			return false;

		}

		/**
		 * Check the old table entries
		 *
		 * @since 4.6.0
		 */
		public function count_log_entries() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;
			$posts_table = "{$wpdb->prefix}posts";

			// Made it here, now let's create the table and start migrating.
			// Let check if table does not exist.
			if ( ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->dlm_reports}` (
					  `date` DATE NOT NULL,
					  `download_ids` longtext NULL,
					  `revenue` longtext NULL,
					  `refunds` longtext NULL,
					  PRIMARY KEY (`date`))
					  ENGINE = InnoDB $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
			}

			// Made it here, now let's create the table and start migrating.
			// Let check if table does not exist.
			if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->dlm_downloads}` (
					    ID bigint(20) NOT NULL auto_increment,
						download_id bigint(20) NOT NULL,
						download_count bigint(20) NOT NULL,
						download_versions varchar(200) NOT NULL,
		 				PRIMARY KEY (`ID`))
						ENGINE = InnoDB $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
			}

			// Check if the table exists. User might have deleted it in the past.
			if ( ! DLM_Utils::table_checker( $wpdb->download_log ) ) {
				wp_send_json( '0' );
				exit;
			}

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT  COUNT(dlm_log.ID) as `entries` FROM {$wpdb->download_log} dlm_log INNER JOIN $posts_table dlm_posts ON dlm_log.download_id = dlm_posts.ID" ), ARRAY_A );

			// If there is a transient it means that the import has taken place but did not complete.
			// Let's start from that offset.
			$upgrader_offset = get_transient( 'dlm_db_upgrade_offset' );
			if ( false !== $upgrader_offset ) {

				wp_send_json(
					array(
						'entries' => $results[0]['entries'],
						'offset'  => absint( $upgrader_offset ),
					)
				);
				exit;
			}

			wp_send_json(
				array(
					'entries' => $results[0]['entries'],
					'offset'  => 0,
				)
			);
			exit;
		}


		/**
		 * Alter the download_log table after the migration has taken place
		 *
		 * @return void
		 */
		public function alter_download_log_table() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			// Managed this far, means migration of data is finalized so we can delete our set transient with the offset.
			delete_transient( 'dlm_db_upgrade_offset' );
			delete_transient( 'dlm_needs_upgrade' );

			// Flush the permalinks.
			flush_rewrite_rules( false );

			// Final step has been made, upgrade is complete.
			$dlm_db_upgrade                  = get_option( 'dlm_db_upgraded' );
			$dlm_db_upgrade['db_upgraded']   = '1';
			$dlm_db_upgrade['upgraded_date'] = wp_date( 'Y-m-d' );

			update_option( 'dlm_db_upgraded', $dlm_db_upgrade );

			// Now lets clear all transients.
			download_monitor()->service( 'transient_manager' )->clear_all_version_transients();

			// Add extra columns to the table
			global $wpdb;

			// In the event that the user had previously deleted the downlod_log table, we need to create it again.
			if ( ! DLM_Utils::table_checker( $wpdb->download_log ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				$charset_collate = $wpdb->get_charset_collate();
				$dlm_tables = '
					CREATE TABLE `' . $wpdb->prefix . "download_log` (
						ID bigint(20) NOT NULL auto_increment,
						user_id bigint(20) NOT NULL,
						user_ip varchar(200) NOT NULL,
						uuid varchar(200) NOT NULL,
						user_agent varchar(200) NOT NULL,
						download_id bigint(20) NOT NULL,
						version_id bigint(20) NOT NULL,
						version varchar(200) NOT NULL,
						download_date datetime DEFAULT NULL,
						download_status varchar(200) DEFAULT NULL,
						download_status_message varchar(200) DEFAULT NULL,
						download_location varchar(200) DEFAULT NULL,
						download_category varchar(200) DEFAULT NULL,
						meta_data longtext DEFAULT NULL,
						PRIMARY KEY  (ID),
						KEY attribute_name (download_id)
					) $charset_collate;
					";

				dbDelta( $dlm_tables );
				wp_send_json( array( 'success' => true ) );
				exit;
			}

			$columns = '';

			if ( ! DLM_Utils::column_checker( $wpdb->download_log, 'uuid' ) ) {
				$columns .= 'ADD COLUMN uuid VARCHAR(200) AFTER USER_IP';
			}

			if ( ! DLM_Utils::column_checker( $wpdb->download_log, 'download_location' ) ) {
				$columns .= ( ! empty( $columns ) ) ? ',ADD COLUMN download_location VARCHAR(200) AFTER download_status_message' : 'ADD COLUMN download_location VARCHAR(200) AFTER download_status_message';
			}

			if ( ! DLM_Utils::column_checker( $wpdb->download_log, 'download_category' ) ) {
				$columns .= ( ! empty( $columns ) ) ? ',ADD COLUMN download_category VARCHAR(200) AFTER download_status_message' : 'ADD COLUMN download_category VARCHAR(200) AFTER download_status_message';
			}

			// Let's check if all the required columns are present. If not, let's add them.
			if ( ! empty( $columns ) ) {
				$alter_statement = "ALTER TABLE {$wpdb->download_log} {$columns}";
				$hash_statement  = "UPDATE {$wpdb->download_log} SET uuid = md5(user_ip) WHERE uuid IS NULL;";
				$wpdb->query( $alter_statement );
				$wpdb->query( $hash_statement );
			}

			// SQL to add index for download_log
			$add_index = "ALTER TABLE {$wpdb->download_log} ADD INDEX download_count (version_id);";
			$wpdb->query( $add_index );
			// Keep transient deletion here, we are using it to check if the upgrade is total or partial.
			delete_transient( 'dlm_upgrade_type' );
			wp_send_json( array( 'success' => true ) );
			exit;
		}

		/**
		 * Check if DB migrated or not
		 *
		 * @return bool
		 * @since 4.6.0
		 */
		public static function check_if_migrated() {

			global $wpdb;
			$upgrade_option = get_option( 'dlm_db_upgraded' );

			// Check if table exists and if option is valid.
			if ( null !== DLM_Utils::table_checker( $wpdb->dlm_reports ) && isset( $upgrade_option['db_upgraded'] ) && '1' === $upgrade_option['db_upgraded'] ) {

				return true;
			}

			return false;
		}

		/**
		 * The new table update functionality
		 *
		 * @since 4.6.0
		 */
		public function update_log_table_db() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;
			$upgrade_type = get_transient( 'dlm_upgrade_type' );

			$limit = 10000;

			$offset = ( isset( $_POST['offset'] ) ) ? $limit * absint( $_POST['offset'] ) : 0;

			$sql_limit = "LIMIT {$offset},{$limit}";

			$items           = array();
			$table_1         = "{$wpdb->download_log}";
			$able_2          = "{$wpdb->posts}";
			// Table that contains downlaod counts about each Download and their versions. Used for performance issues introduced in version 4.6.0 of the plugin.
			$downloads_table = "{$wpdb->dlm_downloads}";
			// If at this point the table does not exist, we need to create it.
			if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
				$charset_collate = $wpdb->get_charset_collate();
				$sql             = "CREATE TABLE IF NOT EXISTS `{$wpdb->dlm_downloads}` (
					    ID bigint(20) NOT NULL auto_increment,
						download_id bigint(20) NOT NULL,
						download_count bigint(20) NOT NULL,
						download_versions varchar(200) NOT NULL,
		 				PRIMARY KEY (`ID`))
						ENGINE = InnoDB $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
			}
			// Check if data has been introduced into table and update it, else we need to populate it.
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`, dlm_log.version_id as `version`, DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_log.download_status as `status`, dlm_posts.post_title AS `title` FROM {$table_1} dlm_log LEFT JOIN {$able_2} dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 {$sql_limit}" ), ARRAY_A );

			// Create empty array of downloads. Will be used to add to the table as a bulk insert and not each row at a time.
			// This will improve performance.
			$downloads          = array();
			// This should not be a problem as the number of entries would be the number of existing downloads.
			$saved_downloads = $wpdb->get_results( "SELECT * FROM {$downloads_table};", ARRAY_A );
			$download_id_column = array_column( $saved_downloads,'download_id' );
			$looped = array();

			foreach ( $data as $row ) {
				// Only add non-failed attempts to the table.
				if ( 'failed' !== $row['status'] ) {
					list( $loop_key, $download ) = $this->create_downloads( $row, $downloads, $download_id_column, $saved_downloads, $looped );
					$downloads[ $row['ID'] ] = $download;

					if ( false !== $loop_key ) {
						$looped[] = $loop_key;
					}
				}
				// Only do this if we need to recreate the dlm_reports_log table also.
				if ( 'total' === $upgrade_type ) {
					$item = $this->create_dates( $row, $items );
					if ( is_array( $item ) ) {
						$items[ $row['date'] ][ $row['ID'] ] = $item;
					} else {
						$items[ $row['date'] ][ $row['ID'] ]['downloads'] = $item;
					}
				}
			}
			// Import Downloads into new table.
			if ( ! empty( $downloads ) ) {
				$this->import_downloads( $downloads );
			}
			// Import Report Dates into new table.
			if ( 'total' === $upgrade_type ) {
				$this->import_dates( $items );
			}
			set_transient( 'dlm_db_upgrade_offset', absint( $_POST['offset'] ) );
			// We save the previous so that we make sure all the entries from that range will be saved.
			wp_send_json_success( $offset );
			exit;
		}

		/**
		 * Clear the previous set offset
		 *
		 * @return void
		 */
		public function clear_offset() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			if ( ! isset( $_POST['offset'] ) ) {
				// We need the previous set offset
				wp_send_json_error();
				exit;
			}

			global $wpdb;
			$downloads_table = "{$wpdb->dlm_downloads}";
			$limit           = 10000;
			$offset          = ( isset( $_POST['offset'] ) ) ? $limit * absint( $_POST['offset'] ) : 0;
			$sql_limit       = "LIMIT {$offset},{$limit}";
			$items           = array();
			$table_1         = "{$wpdb->download_log}";
			$able_2          = "{$wpdb->prefix}posts";

			$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`, dlm_log.version_id as `version`, DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_log.download_status as `status`, dlm_posts.post_title AS `title` FROM {$table_1} dlm_log LEFT JOIN {$able_2} dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 {$sql_limit}" ), ARRAY_A );

			// Create empty array of downloads. Will be used to add to the table as a bulk insert and not each row at a time.
			// This will improve performance.
			$downloads = array();
			// This should not be a problem as the number of entries would be the number of existing downloads.
			$saved_downloads    = $wpdb->get_results( "SELECT * FROM {$downloads_table};", ARRAY_A );
			$download_id_column = array_column( $saved_downloads, 'download_id' );
			$looped             = array();

			foreach ( $data as $row ) {

				if ( 'failed' !== $row['status'] ) {
					list( $loop_key, $download ) = $this->create_downloads( $row, $downloads, $download_id_column, $saved_downloads, $looped );
					$downloads[ $row['ID'] ] = $download;
					if ( false !== $loop_key ) {
						$looped[] = $loop_key;
					}
				}

				if ( 'total' === get_transient( 'dlm_upgrade_type' ) ) {
					$item = $this->create_dates( $row, $items );
					if ( is_array( $item ) ) {
						$items[ $row['date'] ][ $row['ID'] ] = $item;
					} else {
						$items[ $row['date'] ][ $row['ID'] ]['downloads'] = $item;
					}
				}
			}

			// Clear offset Downloads.
			if ( ! empty( $downloads ) ) {
				$this->clear_downloads( $downloads );
			}
			// Clear offset Report Dates
			if ( 'total' === get_transient( 'dlm_upgrade_type' ) ) {
				foreach ( $items as $key => $log ) {

					$table = "{$wpdb->dlm_reports}";

					$sql_check  = "SELECT * FROM $table  WHERE date = %s;";
					$sql_update = "UPDATE $table dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
					$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $key ), ARRAY_A );

					if ( null !== $check && ! empty( $check ) ) {

						$downloads = json_decode( $check[0]['download_ids'], ARRAY_A );

						foreach ( $log as $item_key => $item ) {

							if ( isset( $downloads[ $item_key ] ) ) {
								$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] - $item['downloads'];
							}
						}

						$wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $key ) );

					}
				}
			}

			wp_send_json_success();
			exit;
		}

		/**
		 * Add the DB Upgrader notice
		 *
		 * @since 4.6.0
		 */
		public function add_db_update_notice() {

			?>
			<div class="dlm-upgrade-db-notice notice">
				<div class="dlm-yellow-band-notice">
					<?php echo wp_kses_post( __( 'Please <a href="#" class="dlm-db-upgrade-link">upgrade the database</a>.' ) ); ?>
				</div>
				<div class="inside">
					<div class="main">
						<img class="dlm-notice-logo" src="data:image/png;base64,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">
						<h4><?php echo wp_kses_post( __('Hello there! We made some changes on how Download Monitor\'s data is structured. Please <a href="#" class="dlm-db-upgrade-link">click here</a> to upgrade your database', 'download-monitor' ) ); ?></h4>
						<button id="dlm-upgrade-db" class="button button-primary">
							<?php
							// If the transient is present it means that this is a Resume Upgrade request
							if ( get_transient( 'dlm_db_upgrade_offset' ) ) {
								esc_html_e( 'Resume Upgrade', 'download-monitor' );
							} else {
								esc_html_e( 'Upgrade', 'download-monitor' );
							}
							?>
						</button>
					</div>	
					<div class="dlm-progress-label">0%</div>		
				</div>
				<div id="dlm_progress-bar"></div>
			</div>
			<?php
		}

		/**
		 * Enqueue the DB Upgrader scripts
		 *
		 * @since 4.6.0
		 */
		public function enqueue_db_upgrader_scripts() {

			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_script( 'dlm-log-db-upgrade', download_monitor()->get_plugin_url() . '/assets/js/database-upgrader' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', array( 'jquery' ), DLM_VERSION, true );
			wp_add_inline_script( 'dlm-log-db-upgrade', 'dlm_upgrader =' . wp_json_encode( array( 'nonce' => wp_create_nonce( 'dlm_db_log_nonce' ) ) ), 'before' );

			wp_enqueue_style( 'dlm-db-upgrade-style', download_monitor()->get_plugin_url() . '/assets/css/db-upgrader.min.css', array(), DLM_VERSION );
			wp_enqueue_style( 'jquery-ui-style', download_monitor()->get_plugin_url() . '/assets/css/jquery-ui.min.css', array(), DLM_VERSION );
		}

		/**
		 * Import downloads in upgrader. Imports data to new {prefix}dlm_downloads table.
		 *
		 * @param $downloads
		 *
		 * @return void
		 * @since 4.7.0
		 */
		public function import_downloads( $downloads ) {
			global $wpdb;
			$downloads_table = "{$wpdb->dlm_downloads}";
			// The queries we need to make so that we insert each download as a row.
			$check_for_downloads = "SELECT * FROM {$downloads_table}  WHERE download_id = %s;";
			$downloads_insert    = "INSERT INTO {$downloads_table} (download_id,download_count,download_versions) VALUES ( %s , %s, %s );";
			$downloads_update    = "UPDATE {$downloads_table} dlm SET dlm.download_count = %s, dlm.download_versions = %s WHERE dlm.download_id = %s";

			if ( ! empty( $downloads ) ) {
				foreach ( $downloads as $key => $dlm ) {
					$check = $wpdb->get_results( $wpdb->prepare( $check_for_downloads, $key ), ARRAY_A );
					if ( ! empty( $check ) ) {
						$wpdb->query( $wpdb->prepare( $downloads_update, $dlm['download_count'], $dlm['download_versions'], $key ) );
					} else {
						$wpdb->query( $wpdb->prepare( $downloads_insert, $key, $dlm['download_count'], $dlm['download_versions'] ) );
					}
				}
			}
		}

		/**
		 * Clear created downloads
		 *
		 * @param $downloads
		 *
		 * @return void
		 * @since 4.7.0
		 */
		public function clear_downloads( $downloads ) {
			global $wpdb;
			$downloads_table = "{$wpdb->dlm_downloads}";
			// The queries we need to make so that we insert each download as a row.
			$check_for_downloads = "SELECT * FROM {$downloads_table}  WHERE download_id = %s;";
			$downloads_update    = "UPDATE {$downloads_table} dlm SET dlm.download_count = %s, dlm.download_versions = %s WHERE dlm.download_id = %s";

			if ( ! empty( $downloads ) ) {
				foreach ( $downloads as $key => $dlm ) {
					$check = $wpdb->get_results( $wpdb->prepare( $check_for_downloads, $key ), ARRAY_A );
					if ( ! empty( $check ) ) {
						$saved_download   = $check[0];
						$download_count   = absint( $saved_download['download_count'] ) - absint( $dlm['download_count'] );
						$saved_versions   = json_decode( $saved_download['download_versions'], true );
						$current_versions = json_decode( $dlm['download_versions'], true );
						foreach ( $saved_versions as $id => $count ) {
							if ( isset( $current_versions[ $id ] ) ) {
								$saved_versions[ $id ] = absint( $count ) - absint( $current_versions[ $id ] );
							}
						}
						$wpdb->query( $wpdb->prepare( $downloads_update, $download_count, json_encode( $saved_versions ), $key ) );
					}
				}
			}
		}

		/**
		 * Import data used for Reports chart. Adds data to new table {prefix}dlm_reports_log
		 *
		 * @param $items
		 *
		 * @return void
		 * @since 4.7.0
		 */
		public function import_dates( $items ){
			global $wpdb;

			foreach ( $items as $key => $log ) {

				$table = "{$wpdb->dlm_reports}";

				$sql_check  = "SELECT * FROM {$table}  WHERE date = %s;";
				$sql_insert = "INSERT INTO {$table} (date,download_ids) VALUES ( %s , %s );";
				$sql_update = "UPDATE {$table} dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
				$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $key ), ARRAY_A );

				if ( null !== $check && ! empty( $check ) ) {

					$downloads = json_decode( $check[0]['download_ids'], ARRAY_A );

					foreach ( $log as $item_key => $item ) {
						if ( isset( $downloads[ $item_key ] ) ) {
							$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] + $item['downloads'];
							unset( $downloads[ $item_key ]['date'] );
						} else {
							$downloads[ $item_key ] = array(
								'downloads' => $item['downloads'],
								'title'     => $item['title'],
							);
						}
					}

					$wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $key ) );

				} else {

					$wpdb->query( $wpdb->prepare( $sql_insert, $key, wp_json_encode( $log ) ) );
				}
			}
		}

		/**
		 * Check what type of upgrade we need
		 *
		 * @since 4.7.0
		 */
		public function check_upgrade_type() {
			global $wpdb;
			$transient = 'dlm_upgrade_type';
			if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
				set_transient( $transient, 'partial', 7 * DAY_IN_SECONDS );
				if ( ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
					set_transient( $transient, 'total', 7 * DAY_IN_SECONDS );
				}
			}
		}

		/**
		 * Create upgrader downloads
		 *
		 * @param $row
		 * @param $downloads
		 * @param array $download_id_column
		 * @param array $saved_downloads
		 * @param array $looped
		 *
		 * @return array
		 * @since 4.7.0
		 */
		public function create_downloads( $row, $downloads, $download_id_column = array(), $saved_downloads = array(), $looped = array() ) {

			$download_versions = array();
			$download_id       = $row['ID'];
			$version_id        = $row['version'];
			$download_count    = 1;
			$existing_versions = array(
				$version_id => 1,
			);
			$looped_key = false;

			// If download already exists in $downloads means we looped through, and we should take into consideration the existing data.
			if ( isset( $downloads[ $download_id ] ) ) {
				$existing_versions = json_decode( $downloads[ $download_id ]['download_versions'], true );
				$download_count    = absint( $downloads[ $download_id ]['download_count'] ) + 1;
				if ( isset( $existing_versions[ $version_id ] ) ) {
					$existing_versions[ $version_id ] = absint( $existing_versions[ $version_id ] ) + 1;
				} else {
					$existing_versions = array(
						$version_id => 1,
					);
				}
			}

			// IF the download does not exist in the table, we need to insert it, else we need to update it.
			if ( ! empty( $saved_downloads ) ) {
				// Search in a multidimensional array if download_id exists.
				$key = array_search( $download_id, $download_id_column );
				// We should only search the saved downloads once, otherwise it will falsely add data.
				if ( false !== $key && ! in_array( $key, $looped ) ) {
					$looped_key        = $key;
					$download          = $saved_downloads[ $key ];
					$download_versions = ! empty( $download['download_versions'] ) ? json_decode( $download['download_versions'], true ) : array();
					$download_count    = absint( $download['download_count'] ) + absint( $download_count );

					if ( isset( $download_versions[ $version_id ] ) ) {
						$download_versions[ $version_id ] = absint( $download_versions[ $version_id ] ) + absint( $existing_versions[ $version_id ] );
					} else {
						$download_versions[ $version_id ] = absint( $existing_versions[ $version_id ] );
					}
				}
			}

			if ( empty( $download_versions ) ) {
				$download_versions = $existing_versions;
			}

			$download = array(
				'download_id'       => $download_id,
				'download_count'    => $download_count,
				'download_versions' => wp_json_encode( $download_versions )
			);

			return array(
				$looped_key,
				$download
			);
		}

		/**
		 * Create upgrader dates.
		 *
		 * @param $row array Current row of the query.
		 * @param $items array Array of items parsed.
		 *
		 * @return array|int
		 * @since 4.7.0
		 */
		public function create_dates( $row, $items ) {
			if ( ! isset( $items[ $row['date'] ][ $row['ID'] ] ) ) {
				return array(
					'downloads' => 1,
					'title'     => $row['title'],
				);
			} else {
				return absint( $items[ $row['date'] ][ $row['ID'] ]['downloads'] ) + 1;
			}
		}
	}
}
