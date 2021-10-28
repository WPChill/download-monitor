<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports' ) ) {

	/**
	 * DLM_Reports
	 */
	class DLM_Reports {

		/**
		 * Holds the class object.
		 *
		 * @since 4.4.6
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 4.4.6
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
		 * @since 4.4.6
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Reports ) ) {
				self::$instance = new DLM_Reports();
			}

			return self::$instance;

		}

		/**
		 * Set our global variable dlmReportsStats so we can manipulate given data
		 */
		public function create_global_variable() {
			wp_add_inline_script( 'dlm_reports', 'dlmReportsStats = ' . wp_json_encode( $this->stats() ), 'before' );
		}

		/**
		 * Register DLM Logs Routes
		 *
		 * @since 4.4.6
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
		 * Validate the date parameter
		 *
		 * @param  mixed $param
		 * @param  mixed $one
		 * @param  mixed $two
		 * @return mixed
		 */
		private function validate_date_param( $param, $one, $two ) {
			return strtotime( $param ) !== false;
		}

		/**
		 * Get our stats for the chart
		 *
		 * @return WP_REST_Response
		 * @throws Exception
		 * @since 4.4.6
		 */

		public function rest_stats() {

			return $this->respond( wp_json_encode( $this->stats() ) );
		}

		/**
		 * Send our data
		 *
		 * @param $data
		 *
		 * @return WP_REST_Response
		 * @since 4.4.6
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
		 * @since 4.4.6
		 */
		public function stats() {

			/** @var DLM_WordPress_Log_Item_Repository $repo */
			$repo = download_monitor()->service( 'log_item_repository' );

			return $repo->retrieve_downloads_info_per_day();

		}

		/**
		 * Return stats
		 *
		 * @return array
		 * @throws Exception
		 * @since 4.4.6
		 */
		public function stats_legacy() {

			$filters = array(
				array(
					'key'      => 'download_status',
					'value'    => array( 'completed', 'redirected' ),
					'operator' => 'IN',
				),
			);

			/** @var DLM_WordPress_Log_Item_Repository $repo */
			$repo = download_monitor()->service( 'log_item_repository' );

			$data              = $repo->retrieve_grouped_count( $filters );
			$response['chart'] = $this->generate_chart_data(
				$data,
				array(
					// Get the date from the first download log, meaning it is the last element from the array.
					'from' => $data[ count( $data ) - 1 ]->value,
					// To current date.
					'to'   => date( 'Y-m-d' ),
				)
			);

			$response['summary'] = $repo->retrieve_downloads_info_per_day_legacy();

			return $response;
		}

		/**
		 * Generate data for our chart
		 *
		 * @return array
		 * @throws Exception
		 */
		private function generate_chart_data( $data, $range ) {

			$format = 'Y-m-d';

			$data_map = array();

			foreach ( $data as $data_row ) {
				$data_map[ $data_row->value ] = $data_row->amount;
			}

			$data_formatted = array();

			$start_date = new DateTime( $range['from'] );
			$end_date   = new DateTime( $range['to'] );

			$format_label = 'j M Y';

			while ( $start_date <= $end_date ) {

				if ( isset( $data_map[ $start_date->format( $format ) ] ) ) {

					$data_formatted[] = array(
						'x' => $start_date->format( $format_label ),
						'y' => absint( $data_map[ $start_date->format( $format ) ] ),
					);
				} else {
					$data_formatted[] = array(
						'x' => $start_date->format( $format_label ),
						'y' => 0,
					);
				}

				$start_date->modify( '+1  day' );

			}

			return $data_formatted;
		}

	}
}
