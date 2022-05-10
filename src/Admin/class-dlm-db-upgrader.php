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
		 * @since 4.5.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * Class constructor
		 *
		 * @return void
		 * @since 4.5.0
		 */
		public function __construct() {

			// Don't do anything if we don't need to or if upgrader already done.
			if ( ! self::do_upgrade() ) {

				return;
			}

			// Add notice for user to update the DB.
			add_action( 'admin_notices', array( $this, 'add_db_update_notice' ) );
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
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Admin_Helper object.
		 * @since 4.5.0
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_DB_Upgrader ) ) {
				self::$instance = new DLM_DB_Upgrader();
			}

			return self::$instance;

		}

		/**
		 * Check to see if we need to upgrade
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		public static function do_upgrade() {

			if ( false !== get_transient( 'dlm_db_upgrade_offset' ) ) {
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
		 * @since 4.5.0
		 */
		public function count_log_entries() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;
			$posts_table = "{$wpdb->prefix}posts";

			if ( ! DLM_Utils::table_checker( $wpdb->download_log ) ) {

				wp_send_json( '0' );
				exit;
			}

			// Made it here, now let's create the table and start migrating.
			$this->create_new_table( $wpdb->dlm_reports );

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

			// @todo: For the moment maybe it is best not to alter the download_log table in case someone wants 
			// to revert back to the before-reports version of the plugin. So update option and send json success here
			// Also, will keep the code and in the future delete said columns from table

			// Final step has been made, upgrade is complete.
			$dlm_db_upgrade                  = get_option( 'dlm_db_upgraded' );
			$dlm_db_upgrade['db_upgraded']   = '1';
			$dlm_db_upgrade['upgraded_date'] = wp_date( 'Y-m-d' );

			update_option( 'dlm_db_upgraded', $dlm_db_upgrade );

			// Now lets clear all transients.
			download_monitor()->service( 'transient_manager' )->clear_all_version_transients();

			// Add uuid column to download_log table.
			global $wpdb;
			$alter_statement = "ALTER TABLE {$wpdb->download_log} ADD COLUMN uuid VARCHAR(200) AFTER USER_IP;";
			$hash_statement  = "UPDATE {$wpdb->download_log} SET uuid = md5(user_ip) WHERE uuid IS NULL;";

			$wpdb->query( $alter_statement );
			$wpdb->query( $hash_statement );


			wp_send_json( array( 'success' => true ) );
			exit;
			/*
			global $wpdb;

			$check_status = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->download_log} LIKE %s", 'download_status' ) );

			$check_agent = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->download_log} LIKE %s", 'user_agent' ) );

			$check_message = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->download_log} LIKE %s", 'download_status_message' ) );

			$drop_statement = '';

			if ( null !== $check_status && ! empty( $check_status ) ) {
				$drop_statement .= 'DROP COLUMN download_status';

				// We need to remove data that is was not imported in order to give propper stats.
				$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.ID as `ID` FROM {$wpdb->download_log} dlm_log WHERE 1=1 AND ( dlm_log.download_status NOT IN ('completed','redirected') OR dlm_log.download_status IS NULL )" ), ARRAY_A );

				if ( null !== $data && ! empty( $data ) ) {
					foreach ( $data as $log ) {
						$wpdb->delete( $wpdb->download_log, array( 'ID' => $log['id'] ) );
					}
				}
			}

			if ( null !== $check_agent && ! empty( $check_agent ) ) {
				$drop_statement .= ', DROP COLUMN user_agent';
			}

			if ( null !== $check_message && ! empty( $check_message ) ) {
				$drop_statement .= ', DROP COLUMN download_status_message';
			}

			// Check if we indeed have those columns.
			// IF not, don't alter the table.
			if ( '' !== $drop_statement ) {
				// Drop not needed columns in table.
				$wpdb->query( "ALTER TABLE {$wpdb->download_log} {$drop_statement};" );
			}

			// Final step has been made, upgrade is complete.
			$dlm_db_upgrade                  = get_option( 'dlm_db_upgraded' );
			$dlm_db_upgrade['db_upgraded']   = '1';
			$dlm_db_upgrade['upgraded_date'] = wp_date( 'Y-m-d' );

			update_option( 'dlm_db_upgraded', $dlm_db_upgrade );
			wp_send_json( array( 'success' => true ) );
			exit; */

		}

		/**
		 * Create the new table
		 *
		 * @return void
		 * @since 4.5.0
		 */
		public function create_new_table( $table ) {

			global $wpdb;

			// Let check if table does not exist.
			if ( ! DLM_Utils::table_checker( $table ) ) {

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE IF NOT EXISTS `$table` (
		  `date` DATE NOT NULL,
		  `download_ids` longtext NULL,
		  `revenue` longtext NULL,
		  `refunds` longtext NULL,
		  PRIMARY KEY (`date`))
		ENGINE = InnoDB $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
			}

		}

		/**
		 * Check if DB migrated or not
		 *
		 * @return bool
		 * @since 4.5.0
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
		 * @since 4.5.0
		 */
		public function update_log_table_db() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;

			$limit = 10000;

			$offset = ( isset( $_POST['offset'] ) ) ? $limit * absint( $_POST['offset'] ) : 0;

			$sql_limit = "LIMIT {$offset},{$limit}";

			$items   = array();
			$table_1 = "{$wpdb->download_log}";
			$able_2  = "{$wpdb->posts}";

			$column_check = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->download_log} LIKE %s", 'download_status' ) );

			if ( null !== $column_check && ! empty( $column_check ) ) {

				$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`,  DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_posts.post_title AS `title` FROM $table_1 dlm_log LEFT JOIN $able_2 dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 AND dlm_log.download_status IN ('completed','redirected') $sql_limit" ), ARRAY_A );
			} else {

				$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`,  DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_posts.post_title AS `title` FROM $table_1 dlm_log LEFT JOIN $able_2 dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 $sql_limit" ), ARRAY_A );
			}

			foreach ( $data as $row ) {

				if ( ! isset( $items[ $row['date'] ][ $row['ID'] ] ) ) {
					$items[ $row['date'] ][ $row['ID'] ] = array(
						'downloads' => 1,
						'title'     => $row['title'],
					);
				} else {
					$items[ $row['date'] ][ $row['ID'] ]['downloads'] = absint( $items[ $row['date'] ][ $row['ID'] ]['downloads'] ) + 1;
				}
			}

			foreach ( $items as $key => $log ) {

				$table = "{$wpdb->dlm_reports}";

				$sql_check  = "SELECT * FROM $table  WHERE date = %s;";
				$sql_insert = "INSERT INTO $table (date,download_ids) VALUES ( %s , %s );";
				$sql_update = "UPDATE $table dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
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

			set_transient( 'dlm_db_upgrade_offset', absint( $_POST['offset'] ) );

			// We save the previous so that we make sure all the entries from that range will be saved.
			wp_send_json( $offset );
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

			$limit     = 10000;
			$offset    = $_POST['offset'];
			$sql_limit = "LIMIT {$offset},{$limit}";
			$items     = array();
			$table_1   = "{$wpdb->download_log}";
			$able_2    = "{$wpdb->prefix}posts";

			$column_check = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->download_log} LIKE %s", 'download_status' ) );

			if ( null !== $column_check && ! empty( $column_check ) ) {

				$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`,  DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_posts.post_title AS `title` FROM $table_1 dlm_log INNER JOIN $able_2 dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 AND dlm_log.download_status IN ('completed','redirected') $sql_limit" ), ARRAY_A );
			} else {

				$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`,  DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_posts.post_title AS `title` FROM $table_1 dlm_log INNER JOIN $able_2 dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 $sql_limit" ), ARRAY_A );
			}

			foreach ( $data as $row ) {

				if ( ! isset( $items[ $row['date'] ][ $row['ID'] ] ) ) {
					$items[ $row['date'] ][ $row['ID'] ] = array(
						'downloads' => 1,
						'title'     => $row['title'],
					);
				} else {
					$items[ $row['date'] ][ $row['ID'] ]['downloads'] = absint( $items[ $row['date'] ][ $row['ID'] ]['downloads'] ) + 1;
				}
			}

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

			wp_send_json_success();
			exit;
		}

		/**
		 * Add the DB Upgrader notice
		 *
		 * @since 4.5.0
		 */
		public function add_db_update_notice() {
			$current_screen = get_current_screen();

			if ( 'dlm_download' !== $current_screen->post_type ) {
				return;
			}

			?>
			<div class="dlm-upgrade-db-notice notice">
				<div class="inside">
					<div class="main">
						<h3><?php esc_html_e( 'Download Monitor!', 'download-monitor' ); ?></h3>
						<h4><?php esc_html_e( 'Hello there, we have changed the way we show our reports, now being faster than ever + many more. Please update your database.', 'download-monitor' ); ?></h4>
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
		 * @since 4.5.0
		 */
		public function enqueue_db_upgrader_scripts() {

			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_script( 'dlm-log-db-upgrade', download_monitor()->get_plugin_url() . '/assets/js/database-upgrader' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', array( 'jquery' ), '4.4.7', true );
			wp_add_inline_script( 'dlm-log-db-upgrade', 'dlm_upgrader =' . wp_json_encode( array( 'nonce' => wp_create_nonce( 'dlm_db_log_nonce' ) ) ), 'before' );

			wp_enqueue_style( 'dlm-db-upgrade-style', download_monitor()->get_plugin_url() . '/assets/css/db-upgrader.css', array(), '4.4.7' );
			wp_enqueue_style( 'jquery-ui-style', download_monitor()->get_plugin_url() . '/assets/css/jquery-ui.css', array(), DLM_VERSION );
		}

	}
}
