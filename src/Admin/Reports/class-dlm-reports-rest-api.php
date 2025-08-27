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

		register_rest_route(
			'download-monitor/v1',
			'/reports/graph_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_graph_downloads_data' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		register_rest_route(
			'download-monitor/v1',
			'/reports/table_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_table_downloads_data' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		register_rest_route(
			'download-monitor/v1',
			'/reports/overview_card_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_overview_card_stats' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		register_rest_route(
			'download-monitor/v1',
			'/reports/detailed_card_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_users_card_stats' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		register_rest_route(
			'download-monitor/v1',
			'/reports/users_download_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_users_downloads_data' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);

		register_rest_route(
			'download-monitor/v1',
			'/reports/users_data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_data' ),
				'permission_callback' => array( $this, 'check_api_rights' ),
			)
		);
	}

	public function get_graph_downloads_data( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_graph_downloads_data( $request ),
			200
		);
	}

	public function get_table_downloads_data( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_table_downloads_data( $request ),
			200
		);
	}

	public function get_overview_card_stats( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_overview_card_stats( $request ),
			200
		);
	}

	public function get_user_data( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_user_data( $request ),
			200
		);
	}

	public function get_users_downloads_data( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_users_downloads_data( $request ),
			200
		);
	}

	public function get_users_card_stats( $request ) {
		//check_ajax_referer( 'wp_rest' ); TODO Enable.
		return new WP_REST_Response(
			DLM_Reports::get_users_card_stats( $request ),
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

		if ( is_user_logged_in() && current_user_can( 'dlm_view_reports' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden_context',
			esc_html__( 'Sorry, you are not allowed to see data from this endpoint.', 'download-monitor' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
