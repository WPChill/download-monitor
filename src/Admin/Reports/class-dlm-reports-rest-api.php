<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports_REST_API' ) ) {

	class DLM_Reports_REST_API {

		/**
		 * Holds the class object.
		 *
		 * @since 4.4.6
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * DLM_Upsells constructor.
		 *
		 * @since 4.4.6
		 */
		public function __construct() {

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Extension_Notices object.
		 *
		 * @since 4.4.6
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Reports_REST_API ) ) {
				self::$instance = new DLM_Reports_REST_API();
			}

			return self::$instance;

		}

		/**
		 * Register DLM Logs Routes
		 *
		 * @since 4.4.6
		 */
		public function register_routes() {

			register_rest_route(
				'download-monitor/v1',
				'/stats',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'stats' ),
					'permission_callback' => '__return_true',				
				)
			);

		}

		/**
		 * Validate the date parameter
		 *
		 * @param $param
		 * @param $one
		 * @param $two
		 *
		 * @return bool
		 * @since 4.4.6
		 */
		public function validate_date_param( $param, $one, $two ) {
			return strtotime( $param ) !== false;
		}

		/**
		 * Get our stats for the chart
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return WP_REST_Response
		 * @throws Exception
		 * @since 4.4.6
		 */
		public function stats( \WP_REST_Request $request ) {

			$params = $request->get_query_params();
			$args   = $this->set_getters( $params );

			/** @var DLM_WordPress_Log_Item_Repository $repo */
			$repo = download_monitor()->service( 'log_item_repository' );

			$data = $repo->retrieve_grouped_count( $args['filters'], 'day' );
			$popular_data = $repo->retrieve_grouped_count( $args['filters'], $args['period'], "download_id", 1, 0, "amount", "DESC" );

			$chart = new DLM_Reports_Chart( $data, array(
				'from' => '2010-01-01',
				'to'   => date('Y-m-d')
			),'day' );

			$response = $chart->generate_chart_data();

			if ( ! empty( $popular_data ) ) {
				$d           = array_shift( $popular_data );
				$download_id = $d->value;
				try{
					/** @var DLM_Download $download */
					$download         = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
					$popular_download = $download->get_title();
				}
				catch ( Exception $e ){

				}
			}

			$response['most_popular'] = $popular_download;

			return $this->respond( $response );
		}

		/**
		 * Total downloads
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return WP_REST_Response
		 * @throws Exception
		 */
		public function total_stats( \WP_REST_Request $request ) {

			$params = $request->get_query_params();
			$args   = $this->set_getters($params);

			/** @var DLM_WordPress_Log_Item_Repository $repo */
			$repo = download_monitor()->service( 'log_item_repository' );

			// fetch totals
			$total = $repo->num_rows( $args['filters'] );

			// calculate how many days are in this range
			$interval = $args['from_object']->diff( $args['to_object'] );
			$days     = absint( $interval->format( "%a" ) ) + 1;

			// fetch download stats grouped by downloads
			$popular_download = "n/a";
			$data             = $repo->retrieve_grouped_count( $args['filters'], $args['period'], "download_id", 1, 0, "amount", "DESC" );
			$response         = array();
			if ( ! empty( $data ) ) {
				$d           = array_shift( $data );
				$download_id = $d->value;
				try{
					/** @var DLM_Download $download */
					$download         = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
					$popular_download = $download->get_title();
				}
				catch ( Exception $e ){

				}
			}

			$response['total']   = $total;
			$response['average'] = round( ( $total / $days ), 2 );
			$response['popular'] = $popular_download;

			return $this->respond( $response );
		}

		/**
		 * Send our data
		 *
		 * @param $data
		 *
		 * @return WP_REST_Response
		 * @since 4.4.6
		 */
		private function respond( $data ) {

			$result = new \WP_REST_Response( $data, 200 );
			$result->set_headers(
				array(
					'Cache-Control' => 'max-age=3600, s-max-age=3600',
					'Content-Type'  => 'application/json'
				)
			);

			return $result;
		}

		/**
		 * Set getters
		 *
		 * @param $params
		 *
		 * @return array
		 * @throws Exception
		 * @since 4.4.6
		 */
		private function set_getters( $params ) {

			$args = array();

			// getters
			$args['start_date'] = isset( $params['start_date'] ) ? $params['start_date'] : gmdate( 'Y-m-d', strtotime( '1st of this month' ) + get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
			$args['end_date']   = isset( $params['end_date'] ) ? $params['end_date'] : gmdate( 'Y-m-d', time() + get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
			$args['period']     = isset( $params['period'] ) ? $params['period'] : 'day';

			// setup date filter query
			$args['filters']     = array(
				array( "key" => "download_status", "value" => array( "completed", "redirected" ), "operator" => "IN" ),
			);
			$args['from_object'] = new DateTime( $args['start_date'] );
			$args['to_object']   = new DateTime( $args['end_date'] );
			$args['filters'][]   = array(
				'key'      => 'download_date',
				'value'    => $args['from_object']->format( 'Y-m-d 00:00:00' ),
				'operator' => '>='
			);

			$args['filters'][] = array(
				'key'      => 'download_date',
				'value'    => $args['to_object']->format( 'Y-m-d 23:59:59' ),
				'operator' => '<='
			);

			return $args;
		}
	}

	DLM_Reports_REST_API::get_instance();

}