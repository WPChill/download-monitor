<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports' ) ) {

	/**
	 * DLM_Reports
	 *
	 * @since 4.6.0
	 */
	class DLM_Reports {

		/**
		 * Holds the class object.
		 *
		 * @since 4.6.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 4.6.0
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'create_global_variable' ) );
			add_action( 'wp_ajax_dlm_update_report_setting', array( $this, 'save_reports_settings' ) );
		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Reports object.
		 *
		 * @since 4.6.0
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Reports ) ) {
				self::$instance = new DLM_Reports();
			}

			return self::$instance;

		}

		/**
		 * Set our global variable dlmReportsStats so we can manipulate given data
		 *
		 * @since 4.6.0
		 */
		public function create_global_variable() {

			$rest_route_reports = rest_url() . 'download-monitor/v1/reports';
			$rest_route_users   = rest_url() . 'download-monitor/v1/user_reports';
			wp_add_inline_script( 'dlm_reports', 'dlm_admin_url = "' . admin_url() . '" ; dlmReportsAPI ="' . $rest_route_reports . '"; ', 'before' );
		}

		/**
		 * Register DLM Logs Routes
		 *
		 * @since 4.6.0
		 */
		public function register_routes() {

			// The REST route for downloads reports
			register_rest_route(
				'download-monitor/v1',
				'/reports',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_stats' ),
					'permission_callback' => '__return_true',
				)
			);

		}

		/**
		 * Get our stats for the chart
		 *
		 * @return WP_REST_Response
		 * @throws Exception
		 * @since 4.6.0
		 */
		public function rest_stats() {

			return $this->respond( $this->report_stats() );
		}

		/**
		 * Send our data
		 *
		 * @param $data JSON data received from report_stats.
		 *
		 * @return WP_REST_Response
		 * @since 4.6.0
		 */
		public function respond( $data ) {

			$result = new \WP_REST_Response( $data, 200 );
			$result->set_headers(
				array(
					'Cache-Control' => 'max-age=3600, s-max-age=3600',
					'Content-Type'  => 'application/json',
				)
			);

			return $result;
		}

		/**
		 * Return stats
		 *
		 * @return array
		 * @throws Exception
		 *
		 * @retun array
		 * @since 4.6.0
		 */
		public function report_stats() {

			global $wpdb;

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return array();
			}

			$cache_key = 'dlm_insights';
			$stats     = wp_cache_get( $cache_key, 'dlm_reports_page' );

			if ( ! $stats ) {

				$downloads        = $wpdb->get_results( 'SELECT dlm.user_id, dlm.user_ip, dlm.download_id, dlm.download_date, dlm.download_status FROM ' . $wpdb->download_log . ' dlm ORDER BY dlm.ID desc;', ARRAY_A );
				$users            = get_users();
				$users_data       = array();

				foreach ( $users as $user ) {
					$user_data                    = $user->data;
					$users_data[ $user_data->ID ] = array(
						'id'           => $user_data->ID,
						'nicename'     => $user_data->user_nicename,
						'url'          => $user_data->user_url,
						'registered'   => $user_data->user_registered,
						'display_name' => $user_data->display_name,
						'role'         => ( ( ! in_array( 'administrator', $user->roles, true ) ) ? $user->roles : '' ),
					);
				}

				$user_reports = array(
					'all'        => $downloads,
					'users'      => $users_data,
				);

				$stats = array(
					'download_reports' => $wpdb->get_results( "SELECT  * FROM {$wpdb->dlm_reports};", ARRAY_A ),
					'users_reports'    => $user_reports,
				);
				wp_cache_set( $cache_key, $stats, 'dlm_reports_page', 12 * HOUR_IN_SECONDS );
			}

			return $stats;
		}

		/**
		 * Save reports settings
		 *
		 * @return void
		 * @since 4.6.0
		 */
		public function save_reports_settings() {

			if ( ! isset( $_POST['nonce'] ) ) {
				wp_send_json_error( 'No nonce' );
			}
			check_ajax_referer( 'dlm_reports_nonce', $_POST['nonce'] );

			$option = sanitize_text_field( wp_unslash( $_POST['name'] ) );
			$value  = sanitize_text_field( wp_unslash( $_POST['value'] ) );

			if ( '' !== $option ) {
				update_option( $option, $value );
			}
			die();
		}

	}

}
