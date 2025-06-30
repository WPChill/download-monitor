<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports2' ) ) {

	/**
	 * DLM_Reports
	 *
	 * @since 5.1.0
	 */
	class DLM_Reports2 {

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 5.1.0
		 */
		public function __construct() {
			add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );
		}

		/**
		 * Add settings menu item
		 *
		 * @param mixed $links The links for the menu.
		 *
		 * @return array
		 */
		public function add_admin_menu( $links ) {
			// If Reports are disabled don't add the menu item.
			if ( ! DLM_Logging::is_logging_enabled() ) {
				return $links;
			}

			// Reports page.
			$links[] = array(
				'page_title' => __( 'Reports2', 'download-monitor' ),
				'menu_title' => __( 'Reports2', 'download-monitor' ),
				'capability' => 'dlm_view_reports',
				'menu_slug'  => 'download-monitor-reports2',
				'function'   => array( $this, 'view' ),
				'priority'   => 55,
			);

			return $links;
		}

		/**
		 * Create React root for reports page
		 * @since 5.1.0
		 * @return void
		 */
		public function view() {
			?>
				<div id="dlm_reports_page"></div>
			<?php
		}

		/**
		 * Get our stats for the chart
		 *
		 * @return WP_REST_Response
		 * @since 5.1.0
		 */
		public static function get_overview_stats( $request ) {
			global $wpdb;

			$data = array();

			//check_ajax_referer( 'wp_rest' );

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return $data;
			}

			// Pregătim range-ul de date
			$start_date = null;
			$end_date   = null;

			$date_range = $request->get_param( 'date_range' );

			if ( is_array( $date_range ) && isset( $date_range['start'], $date_range['end'] ) ) {
				$start_date = sanitize_text_field( $date_range['start'] );
				$end_date   = sanitize_text_field( $date_range['end'] );
			} else {
				// Implicit: ultimele 7 zile
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			// Construim și executăm query-ul
			$data['downloads_data'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->dlm_reports} WHERE `date` BETWEEN %s AND %s ORDER BY `date` ASC;",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

			$data = apply_filters( 'dlm_overview_stats_data', $data, $request );

			return $data;
		}

		/**
		 * Get our stats for the user reports
		 *
		 * @return WP_REST_Response
		 * @since 5.1.0
		 */
		public static function get_detailed_stats( $request ) {
			global $wpdb;

			check_ajax_referer( 'wp_rest' );

			$data = array();

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return $data;
			}

			$date_range = $request->get_param( 'date_range' );

			if ( is_array( $date_range ) && isset( $date_range['start'], $date_range['end'] ) ) {
				$start_date = sanitize_text_field( $date_range['start'] ) . ' 00:00:00';
				$end_date   = sanitize_text_field( $date_range['end'] ) . ' 23:59:59';
			} else {
				$end_date   = current_time( 'Y-m-d 23:59:59' );
				$start_date = gmdate( 'Y-m-d 00:00:00', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT r.*, u.display_name
					FROM {$wpdb->download_log} r
					LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
					WHERE r.download_date BETWEEN %s AND %s
					ORDER BY r.ID DESC
					",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

			$data['user_data'] = $results;

			$data = apply_filters( 'dlm_detailed_stats_data', $data, $request );

			return $data;
		}
	}
}
