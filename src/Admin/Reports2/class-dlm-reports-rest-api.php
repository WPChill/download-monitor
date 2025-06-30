<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DLM_Reports
 *
 * @since 5.1.0
 */
class DLM_Reports_Rest_Api {

	/**
	 * DLM_Reports constructor.
	 *
	 * @since 5.1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register DLM Logs Routes
	 *
	 * @since 5.1.0
	 */
	public function register_routes() {

		// The REST route for downloads reports.
		register_rest_route(
			'download-monitor/v1',
			'/overview_stats',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'overview_stats' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		// The REST route for user reports.
		register_rest_route(
			'download-monitor/v1',
			'/detailed_stats',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'detailed_stats' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

	}

	public function overview_stats( $request ) {

		return new WP_REST_Response(
			DLM_Reports2::get_overview_stats( $request ),
			200
		);
	}

	public function detailed_stats( $request ) {

		return new WP_REST_Response(
			DLM_Reports2::get_detailed_stats( $request ),
			200
		);
	}

	/**
	 * Check permissions to display data
	 *
	 * @param array $request The request.
	 *
	 * @return bool|WP_Error
	 * @since 5.1.0
	 */
	public function check_api_rights( $request ) {

		return true; // TODO: remove, this exists just for Postman testing

		if ( ! isset( $request['user_can_view_reports'] ) || ! (bool) $request['user_can_view_reports'] ||
				! is_user_logged_in() || ! current_user_can( 'dlm_view_reports' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to see data from this endpoint.', 'download-monitor' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}

