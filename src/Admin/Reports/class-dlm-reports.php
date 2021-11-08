<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports' ) ) {

	/**
	 * DLM_Reports
	 * 
	 * @since 4.5.0
	 */
	class DLM_Reports {

		/**
		 * Holds the class object.
		 *
		 * @since 4.5.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 4.5.0
		 */
		public function __construct() {

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'create_global_variable' ) );

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Reports object.
		 *
		 * @since 4.5.0
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
		 * @since 4.5.0
		 */
		public function create_global_variable() {
			wp_add_inline_script( 'dlm_reports', 'dlmReportsStats = ' . wp_json_encode( $this->report_stats() ), 'before' );
		}

		/**
		 * Register DLM Logs Routes
		 *
		 * @since 4.5.0
		 */
		public function register_routes() {

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
		 * @since 4.5.0
		 */

		public function rest_stats() {

			return $this->respond( $this->report_stats() );
		}

		/**
		 * Send our data
		 *
		 * @param $data
		 *
		 * @return WP_REST_Response
		 * @since 4.5.0
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
		 * @since 4.5.0
		 */
		public function report_stats() {

			global $wpdb;
			$cache_key = 'dlm_reports';
			$stats     = wp_cache_get( $cache_key, 'dlm_reports_page' );

			if ( ! $stats ) {
				$stats = $wpdb->get_results( "SELECT  * FROM {$wpdb->dlm_reports};", ARRAY_A );
				wp_cache_set( $cache_key, $stats, 'dlm_reports_page', 12 * HOUR_IN_SECONDS );
			}

			return $stats;
		}

	}
}
