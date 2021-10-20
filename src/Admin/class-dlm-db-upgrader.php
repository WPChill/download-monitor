<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_DB_Upgrader' ) ) {

	class DLM_DB_Upgrader {

		/**
		 * Holds the class object.
		 *
		 * @since 1.0.0
		 *
		 * @var object
		 */
		public static $instance;

		public function __construct() {

			global $wpdb;

			add_action( 'wp_ajax_dlm_db_log_entries', array( $this, 'count_log_entries' ) );
			add_action( 'wp_ajax_dlm_upgrade_db', array( $this, 'update_log_table_db' ) );

			//if ( $this->check_for_table( $wpdb->prefix . 'download_log' ) && ! $this->check_for_table( $wpdb->prefix . 'dlm_reports_log' ) ) {
				add_action( 'admin_notices', array( $this, 'add_db_update_notice' ) );

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_db_upgrader_scripts' ) );
			//}

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Admin_Helper object.
		 * @since 1.0.0
		 *
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
			$log_table   = $wpdb->prefix . 'download_log';
			$posts_table = $wpdb->prefix . 'posts';

			if ( ! $this->check_for_table( $log_table ) ) {

				wp_send_json( false );
				exit;
			}

			$this->create_new_table();

			$results = $wpdb->get_results(
					"SELECT  COUNT(download_id) as `entries` FROM {$log_table} dlm_log INNER JOIN {$posts_table} dlm_posts ON dlm_log.download_id = dlm_posts.ID;"
					, ARRAY_A );

			wp_send_json( $results[0]['entries'] );
			exit;
		}

		public function create_new_table() {

			global $wpdb;

			$new_log_table = $wpdb->prefix . 'dlm_reports_log';

			// Let check if table does not exist
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
		 * Check to see if table exists
		 *
		 * @param $table
		 *
		 * @return bool
		 */
		public function check_for_table( $table ) {
			global $wpdb;

			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $table . "'" ) ) {
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

			$items = array();
			$data  = $wpdb->get_results(
					"SELECT  download_id as `ID`,  DATE_FORMAT(`download_date`, '%Y-%m-%d') AS `date`, post_title AS `title` FROM {$wpdb->prefix}download_log dlm_log INNER JOIN {$wpdb->prefix}posts dlm_posts ON dlm_log.download_id = dlm_posts.ID WHERE 1=1 AND dlm_log.download_status IN ('completed','redirected') {$sql_limit};"
					, ARRAY_A );

			foreach ( $data as $row ) {

				if ( ! isset( $items[ $row['date'] ][ $row['ID'] ] ) ) {
					$items[ $row['date'] ][ $row['ID'] ] = array(
							'id'        => $row['ID'],
							'downloads' => 1,
							'title'     => $row['title']
					);
				} else {
					$items[ $row['date'] ][ $row['ID'] ]['downloads'] = absint( $items[ $row['date'] ][ $row['ID'] ]['downloads'] ) + 1;
				}

			}

			foreach ( $items as $key => $log ) {

				$sql_check  = "SELECT * FROM {$wpdb->prefix}dlm_reports_log  WHERE date = %s;";
				$sql_insert = "INSERT INTO {$wpdb->prefix}dlm_reports_log (date,download_ids) VALUES ( %s , %s );";
				$sql_update = "UPDATE {$wpdb->prefix}dlm_reports_log dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
				$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $key ), ARRAY_A );

				if ( $check ) {
					$downloads = json_decode( $check[0]['download_ids'], array( 'allowed_classes' => false ) );

					foreach ( $log as $item_key => $item ) {
						if ( isset( $downloads[ $item_key ] ) ) {
							$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] + $item['downloads'];
							unset( $downloads[ $item_key ]['date'] );
						} else {
							$downloads[ $item_key ] = array(
									'id'        => $item_key,
									'downloads' => $item['downloads'],
									'title'     => $item['title']
							);
						}
					}

					$wpdb->query( $wpdb->prepare( $sql_update, json_encode( $downloads ), $key ) );

				} else {
					$wpdb->query( $wpdb->prepare( $sql_insert, $key, json_encode( $log ) ) );
				}

			}

			wp_send_json( $offset );
			exit;
		}

		/**
		 * Add the DB Upgrader notice
		 */
		public function add_db_update_notice() {

			?>
			<div class="dlm-upgrade-db-notice notice">
				<div class="inside">
					<div class="main">
						<?php __('Hello there, we have changed the wa','download-monitor') ?>
					</div>
					<button id="dlm-upgrade-db" class="button button-primary">just do it!</button>
					<button id="dlm-upgrade-db" class="button button-secondary">Remind me later</button>
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
			wp_enqueue_script( 'dlm-log-db-upgrade', download_monitor()->get_plugin_url() . '/assets/js/database-upgrader.js', array( 'jquery' ) );
			wp_add_inline_script( 'dlm-log-db-upgrade', 'dlm_upgrader =' . json_encode( array( 'nonce' => wp_create_nonce( 'dlm_db_log_nonce' ) ) ), 'before' );

			wp_enqueue_style( 'dlm-db-upgrade-style', download_monitor()->get_plugin_url() . '/assets/css/db-upgrader.css' );
			wp_enqueue_style( 'jquery-ui-style', download_monitor()->get_plugin_url() . '/assets/css/jquery-ui.css', array(), DLM_VERSION );
		}

	}
}