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
		 * @since 4.4.7
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * Class constructor
		 *
		 * @return void
		 * @since 4.4.7
		 */
		public function __construct() {

			global $wpdb;

			add_action( 'wp_ajax_dlm_db_log_entries', array( $this, 'count_log_entries' ) );
			add_action( 'wp_ajax_dlm_upgrade_db', array( $this, 'update_log_table_db' ) );

			if ( false === get_option( 'dlm_db_upgraded' ) ) {
				// Also add the new option to the DB and set it to 0.
				add_option( 'dlm_db_upgraded', '0' );
			}

			// if ( ! self::check_if_migrated() ) {

				// Add notice for user to update the DB.
				add_action( 'admin_notices', array( $this, 'add_db_update_notice' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_db_upgrader_scripts' ) );

			// }
		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Admin_Helper object.
		 * @since 4.4.7
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_DB_Upgrader ) ) {
				self::$instance = new DLM_DB_Upgrader();
			}

			return self::$instance;

		}

		/**
		 * Check the old table entries
		 */
		public function count_log_entries() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;
			$log_table   = "{$wpdb->prefix}download_log";
			$posts_table = "{$wpdb->prefix}posts";

			if ( ! $this->check_for_table( $log_table ) ) {

				wp_send_json( false );
				exit;
			}

			$this->create_new_table();

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT  COUNT(dlm_log.ID) as `entries` FROM $log_table dlm_log INNER JOIN $posts_table dlm_posts ON dlm_log.download_id = dlm_posts.ID" ), ARRAY_A );

			wp_send_json( $results[0]['entries'] );
			exit;
		}

		/**
		 * Create the new table
		 *
		 * @return void
		 * @since 4.4.7
		 */
		public function create_new_table() {

			global $wpdb;

			// Came here it means the user clicked the upgrade button, so we save it.
			update_option( 'dlm_db_upgraded', '1' );

			$new_log_table = $wpdb->prefix . 'dlm_reports_log';

			// Let check if table does not exist.
			if ( ! $this->check_for_table( $new_log_table ) ) {

				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE IF NOT EXISTS `$new_log_table` (
		  `date` DATETIME NOT NULL,
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
		 * Check for existing table
		 *
		 * @param  mixed $table
		 * @return bool
		 */
		public function check_for_table( $table ) {
			global $wpdb;

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {

				return true;

			}

			return false;
		}

		/**
		 * Check if DB migrated or not
		 *
		 * @return bool
		 */
		public static function check_if_migrated() {

			global $wpdb;

			// First we need to check if table exists.
			if ( null !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'dlm_reports_log' ) ) ) {

				return true;
			}

			if ( '1' === get_option( 'dlm_db_upgraded' ) ) {
				return true;
			}

			return false;
		}

		/**
		 * The new table update functionality
		 */
		public function update_log_table_db() {

			wp_verify_nonce( $_POST['nonce'], 'dlm_db_log_nonce' );

			global $wpdb;

			$limit     = 10000;
			$offset    = ( isset( $_POST['offset'] ) ) ? $limit * absint( $_POST['offset'] ) : 0;
			$sql_limit = "LIMIT {$offset},{$limit}";

			$items   = array();
			$table_1 = "{$wpdb->prefix}download_log";
			$able_2  = "{$wpdb->prefix}posts";

			$data = $wpdb->get_results( $wpdb->prepare( "SELECT  dlm_log.download_id as `ID`,  DATE_FORMAT(dlm_log.download_date, '%%Y-%%m-%%d') AS `date`, dlm_posts.post_title AS `title` FROM $table_1 dlm_log INNER JOIN $able_2 dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 AND dlm_log.download_status IN ('completed','redirected') $sql_limit" ), ARRAY_A );

			foreach ( $data as $row ) {

				if ( ! isset( $items[ $row['date'] ][ $row['ID'] ] ) ) {
					$items[ $row['date'] ][ $row['ID'] ] = array(
						'id'        => $row['ID'],
						'downloads' => 1,
						'title'     => $row['title'],
					);
				} else {
					$items[ $row['date'] ][ $row['ID'] ]['downloads'] = absint( $items[ $row['date'] ][ $row['ID'] ]['downloads'] ) + 1;
				}
			}

			foreach ( $items as $key => $log ) {

				$table = "{$wpdb->prefix}dlm_reports_log";

				$sql_check  = "SELECT * FROM $table  WHERE date = %s;";
				$sql_insert = "INSERT INTO $table (date,download_ids) VALUES ( %s , %s );";
				$sql_update = "UPDATE $table dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
				$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $key ), ARRAY_A );

				if ( $check ) {

					$downloads = json_decode( $check[0]['download_ids'], ARRAY_A );

					foreach ( $log as $item_key => $item ) {
						if ( isset( $downloads[ $item_key ] ) ) {
							$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] + $item['downloads'];
							unset( $downloads[ $item_key ]['date'] );
						} else {
							$downloads[ $item_key ] = array(
								'id'        => $item_key,
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

			wp_send_json( $offset );
			exit;
		}

		/**
		 * Add the DB Upgrader notice
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
						<h4><?php esc_html_e( 'Hello there, we have changed the way we show our reports, now being faster than ever. Please update your database in order for the new reports to work.', 'download-monitor' ); ?></h4>
						<button id="dlm-upgrade-db" class="button button-primary"><?php esc_html_e( 'Upgrade', 'download-monitor' ); ?></button>
					</div>			
				</div>
				<div id="dlm_progress-bar"><div class="dlm-progress-label"></div></div>
			</div>
			<?php
		}

		/**
		 * Enqueue the DB Upgrader scripts
		 */
		public function enqueue_db_upgrader_scripts() {

			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_script( 'dlm-log-db-upgrade', download_monitor()->get_plugin_url() . '/assets/js/database-upgrader.js', array( 'jquery' ), '4.4.7', true );
			wp_add_inline_script( 'dlm-log-db-upgrade', 'dlm_upgrader =' . wp_json_encode( array( 'nonce' => wp_create_nonce( 'dlm_db_log_nonce' ) ) ), 'before' );

			wp_enqueue_style( 'dlm-db-upgrade-style', download_monitor()->get_plugin_url() . '/assets/css/db-upgrader.css', array(), '4.4.7' );
			wp_enqueue_style( 'jquery-ui-style', download_monitor()->get_plugin_url() . '/assets/css/jquery-ui.css', array(), DLM_VERSION );
		}

	}
}
