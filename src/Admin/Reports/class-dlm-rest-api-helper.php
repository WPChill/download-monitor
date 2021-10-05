<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'DLM_REST_API_Helper' ) ) {

	class DLM_REST_API_Helper {

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

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Extension_Notices object.
		 *
		 * @since 4.4.6
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_REST_API_Helper ) ) {
				self::$instance = new DLM_REST_API_Helper();
			}

			return self::$instance;

		}

		/**
		 * Return chart data
		 *
		 * @param $args
		 *
		 * @return false|string
		 */
		public function rest_api_chart_source( $args ) {

			 if ( isset( $args['date_from'] ) ) {
				$date_from = $args['date_from'];
			} else {
				$date_from = new DateTime( 'tomorrow' );
				$date_from = $date_from->format('Y-m-d');
			}

			if ( isset( $args['date_to'] ) ) {
				$date_to = $args['date_to'];
			} else {
				$date_to = new DateTime( 'yesterday' );
				$date_to = $date_to->format('Y-m-d');
			}

			if ( isset( $args['period'] ) ) {
				$period = $args['period'];
			} else {
				$period = 'day';
			} 
			
			$response = wp_remote_get( get_home_url() . '/wp-json/download-monitor/v1/chart_stats' );

			$data     = false;

			if ( ! is_wp_error( $response ) ) {

				return wp_remote_retrieve_body( $response );

				// Decode the data that we got.
				$data = json_decode(wp_remote_retrieve_body( $response ));
			}

			$order_data = false;

			if ( $data->datasets[0]->data ) {


				foreach ( $data->datasets[0]->data as $log ) {

					$log_date = strtotime( $log->x );

					if ( strtotime( $date_from ) < $log_date && $log_date < strtotime( $date_to ) ) {

						$order_data[] = $log;
					}
				}

				$data->datasets[0]->data = $order_data;
			}

			return json_encode($data); 

		}

		/**
		 * Return chart data
		 *
		 * @param $args
		 *
		 * @return false|string
		 */
		public function rest_api_summary_downloads( $args ) {

			if ( isset( $args['date_from'] ) ) {
				$date_from = $args['date_from'];
			} else {
				$date_from = new DateTime( 'tomorrow' );
				$date_from = $date_from->format('Y-m-d');
			}

			if ( isset( $args['date_to'] ) ) {
				$date_to = $args['date_to'];
			} else {
				$date_to = new DateTime( 'yesterday' );
				$date_to = $date_to->format('Y-m-d');
			}

			$response = wp_remote_get( esc_url(get_home_url() . '/wp-json/download-monitor/v1/total_stats?start_date=' . $date_from . '&end_date=' . $date_to ));
			$data     = false;

			if ( ! is_wp_error( $response ) ) {

				// Decode the data that we got.
				$data = wp_remote_retrieve_body( $response );
			}

			return $data;

		}
	}
}
