<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Handles WP Rest API endpoints
 *
 * @package Download_Monitor_REST_API
 */
class DLM_Rest_API {

	/**
	 * DMR Rest API endpoints.
	 *
	 * @var Array
	 * @since 5.0.0
	 */
	private $endpoints;

	/**
	 * Download plugin repository for downloads.
	 *
	 * @var Object
	 * @since 5.0.0
	 */
	private $download_repository;

	/**
	 * Download plugin repository for versions.
	 *
	 * @var Object
	 * @since 5.0.0
	 */
	private $version_repository;

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 */
	public static $instance;

	/**
	 * DLM Rest API namespace.
	 *
	 * @var String
	 * @since 5.0.0
	 */
	private $namespace;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		$this->namespace           = 'download-monitor/v1';
		$this->download_repository = DLM_Download_REST::get_instance();
		$this->version_repository  = DLM_Version_REST::get_instance();
		$this->endpoints           = array();

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_filter( 'posts_orderby', array( $this, 'modify_download_version_orderby' ), 10, 2 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_REST_API object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_REST_API ) ) {
			self::$instance = new DLM_REST_API();
		}

		return self::$instance;
	}

	/**
	 * Prepare the endpoints.
	 *
	 * @since 5.0.0
	 */
	public function get_endpoints() {
		$endpoints = array_merge(
			$this->download_repository->get_endpoints(),
			$this->version_repository->get_endpoints()
		);

		/**
		 * Filters the array of available REST API endpoints.
		 *
		 * @hook  dlm_rest_endpoints
		 *
		 * @param  Array  $endpoints  Array of available REST API endpoints.
		 *
		 * @since 5.0.0
		 */
		$endpoints = apply_filters( 'dlm_rest_endpoints', $endpoints );

		/**
		 * Apply default configuration if not set on the endpoints.
		 */
		foreach ( $endpoints as &$handlers ) {
			foreach ( $handlers as &$handler ) {
				// Basic authentication.
				if ( ! isset( $handler['permission_callback'] ) ) {
					$method                         = $handler['methods'];
					$handler['permission_callback'] = array( $this, 'is_user_allowed_' . strtolower( $method ) );
				}

				// Arguments sanitization.
				if ( isset( $handler['args'] ) ) {
					foreach ( $handler['args'] as $arg_name => &$arg ) {
						if ( ! isset( $arg['sanitize_callback'] ) ) {
							$arg['sanitize_callback'] = array( $this, 'sanitize_arg' );
						}
					}
				} else {
					$handler['args']['sanitize_callback'] = array( $this, 'sanitize_arg' );
				}
			}
		}

		return $endpoints;
	}

	/**
	 * Modifies the default orderby for download versions.
	 *
	 * @param  String    $orderby  Current orderby.
	 * @param  WP_Query  $query    Current query.
	 *
	 * @return String  Modified orderby.
	 *
	 * @since 5.0.0
	 */
	public function modify_download_version_orderby( $orderby, $query ) {
		global $wpdb;

		if ( 'dlm_download_version' === $query->get( 'post_type' ) ) {
			$orderby = "$wpdb->posts.post_date DESC";
		}

		return $orderby;
	}

	/**
	 * Register REST routes
	 *
	 * @since 5.0.0
	 */
	public function register_endpoints() {
		$endpoints = $this->get_endpoints();

		foreach ( $endpoints as $endpoint => &$handlers ) {
			register_rest_route( $this->namespace, $endpoint, $handlers );
		}
	}

	/**
	 * Basic authentication handler for the REST API
	 *
	 * @param  string  $name  Name of the method to call.
	 * @param  array   $args  Arguments to pass when calling the method.
	 *
	 * @return  Boolean True if the user is authorized, false otherwise.
	 *
	 * @since 5.0.0
	 */
	public function __call( $name, $args ) {
		$can_do = false;
		switch ( $name ) {
			case 'is_user_allowed_get':
				$can_do = current_user_can( 'dlm_use_rest_api_get' );
				break;
			case 'is_user_allowed_post':
				$can_do = current_user_can( 'dlm_use_rest_api_post' );
				break;
			case 'is_user_allowed_patch':
				$can_do = current_user_can( 'dlm_use_rest_api_update' );
				break;
			case 'is_user_allowed_delete':
				$can_do = current_user_can( 'dlm_use_rest_api_delete' );
				break;
		}

		// Check if the request is authorized by the API key.
		$request       = $args[0];
		$authorization = $request->get_header( 'X-DLM-AUTHORIZATION' );
		if ( md5( get_option( 'dlm_rest_api_key' ) ) === $authorization ) {
			$can_do = true;
		}

		return $can_do;
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed  $value  Value of the 'filter' argument.
	 *
	 * @return WP_Error|boolean
	 *
	 * @since 5.0.0F
	 */
	public function sanitize_arg( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Check permissions to display data
	 *
	 * @param  WP_REST_Request  $request  The request.
	 *
	 * @return bool|WP_Error
	 * @since 5.0.0
	 */
	public function check_api_rights( $request ) {
		$key    = $request->get_header( 'x_dlm_api_key' );
		$secret = $request->get_header( 'x_dlm_api_secret' );

		if ( null == $key || null == $secret ) {
			return new WP_Error( 'rest_forbidden', __( 'Authentication failed', 'download-monitor' ), array( 'status' => 403 ) );
		}

		global $wpdb;
		$sql = $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}dlm_api_keys WHERE secret_key = %s AND public_key = %s", sanitize_key( $secret ), sanitize_key( $key ) );
		$res = $wpdb->get_row( $sql );

		if ( null == $res || ! isset( $res->user_id ) ) {
			return new WP_Error( 'rest_forbidden', __( 'No user found for authentication keys.', 'download-monitor' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
