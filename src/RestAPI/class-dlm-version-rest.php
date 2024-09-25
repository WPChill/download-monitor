<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Interaction with the version repository of the download plugin.
 *
 * @package DLM_Version_REST
 *
 * @since   5.0.0
 */
class DLM_Version_REST {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 */
	public static $instance;

	/**
	 * Constructor.
	 *
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 *
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Version_REST ) ) {
			self::$instance = new DLM_Version_REST();
		}

		return self::$instance;
	}

	/**
	 * Registers the download endpoints.
	 *
	 * @return  Array  Endpoints settings.
	 *
	 * @since 5.0.0
	 */
	public function get_endpoints() {
		$dlm_rest_api = DLM_Rest_API::get_instance();

		return array(
			// Fetch all versions from a download id.
			'/versions'                    => array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_versions' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'                => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),

				),
			),
			// Fetch single version.
			'/version/(?P<version_id>\d+)' => array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_version' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'                => array(
						'version_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The version\'s ID', 'download-monitor' ),
						),
					),
				),
				// Delete a version.
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_version' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'                => array(
						'version_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The version\'s ID', 'download-monitor' ),
						),
					),
				),
				// Update a version.
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_version' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'                => array(
						'version_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The version\'s ID', 'download-monitor' ),
						),
						'version'    => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The version\'s number', 'download-monitor' ),
						),
						'url'        => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The version\'s URL', 'download-monitor' ),
						),
					),
				),
			),
			// Create a new version.
			'/version'                     => array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'store_version' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'                => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
						'version'     => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The version\'s number', 'download-monitor' ),
						),
						'url'         => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The version\'s URL', 'download-monitor' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Fetch all version
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function get_versions( $req ) {
		$params = $req->get_params();

		$args = array( 'post_parent' => $params['download_id'] );

		$total_versions = download_monitor()->service( 'version_repository' )->num_rows( $args );
		$fetch_versions = download_monitor()->service( 'version_repository' )->retrieve( $args );

		if ( count( $fetch_versions ) <= 0 ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist or does not have any versions.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		// Let's get the versions.
		$version_items = array_map(
			function ( $version ) {
				return $this->get_version_item( $version );
			},
			$fetch_versions
		);

		return new WP_REST_Response(
			array(
				'count'    => $total_versions,
				'versions' => $version_items,
			)
		);
	}

	/**
	 * Fetch a single version
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function get_version( $req ) {
		$params = $req->get_params();

		try {
			$version = download_monitor()->service( 'version_repository' )->retrieve_single( $params['version_id'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'version_not_found', __( 'Version does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response(
			$this->get_version_item( $version )
		);
	}

	/**
	 * Delete a single version
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function delete_version( $req ) {
		$params = $req->get_params();

		$version = get_post( $params['version_id'] );

		if ( ! $version || 'dlm_download_version' !== $version->post_type ) {
			return new WP_Error( 'version_not_found', __( 'Version does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		download_monitor()->service( 'transient_manager' )->clear_versions_transient( $version->post_parent );
		wp_delete_post( $version->ID, true );

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Create a new version
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function store_version( $req ) {
		$params = $req->get_params();

		if ( ! empty( $params['download_id'] ) ) {
			$download_id = absint( $params['download_id'] );
			$versions    = download_monitor()->service( 'version_repository' )->retrieve( array( 'post_parent' => $download_id ) );
			// Check if there are versions.
			if ( ! empty( $versions ) ) {
				// Increase the menu order, so that the new version is the first one.
				foreach ( $versions as $version ) {
					// update
					wp_update_post(
						array(
							'ID'         => $version->get_id(),
							'menu_order' => $version->get_menu_order() + 1,
						)
					);
				}
			}
			$version = new DLM_Download_Version();
			$version->set_download_id( $download_id );
			$version->set_version( isset( $params['version'] ) ? $params['version'] : '' );
			$version->set_mirrors( isset( $params['url'] ) ? array( $params['url'] ) : array() );
			$version->set_date( new DateTime( current_time( 'mysql' ) ) );
			$version->set_menu_order( 0 );

			try {
				download_monitor()->service( 'version_repository' )->persist( $version );
				download_monitor()->service( 'transient_manager' )->clear_versions_transient( $download_id );

				// Get latest data.
				$version = download_monitor()->service( 'version_repository' )->retrieve_single( $version->get_id() );
			} catch ( Exception $e ) {
				return new WP_Error( 'version_error', __( 'Unable to create a version item.', 'download-monitor' ), array( 'status' => 400 ) );
			}

			return new WP_REST_Response(
				$this->get_version_item( $version )
			);
		} else {
			return new WP_Error( 'rest_invalid_param', __( 'You must provide a download_id.', 'download-monitor' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Update a version
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function update_version( $req ) {
		$params = $req->get_params();

		if ( ! empty( $params['version_id'] ) ) {
			// Let's check if version exists first.
			try {
				$version = download_monitor()->service( 'version_repository' )->retrieve_single( $params['version_id'] );
			} catch ( Exception $e ) {
				return new WP_Error( 'version_not_found', __( 'Version does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
			}

			$version->set_version( isset( $params['version'] ) ? $params['version'] : $version->get_version() );
			$version->set_mirrors( isset( $params['url'] ) ? array( $params['url'] ) : $version->get_mirrors() );

			if ( isset( $params['date'] ) ) {
				$version->set_date( new DateTime( $params['date'] ) );
			}

			try {
				download_monitor()->service( 'version_repository' )->persist( $version );
				download_monitor()->service( 'transient_manager' )->clear_versions_transient( $version->get_download_id() );

				// Get latest data.
				$version = download_monitor()->service( 'version_repository' )->retrieve_single( $version->get_id() );
			} catch ( Exception $e ) {
				return new WP_Error( 'version_error', __( 'Unable to update the version item.', 'download-monitor' ), array( 'status' => 400 ) );
			}

			return new WP_REST_Response( $this->get_version_item( $version ) );
		} else {
			return new WP_Error( 'rest_invalid_param', __( 'You must provide a download_id.', 'download-monitor' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Get the version item.
	 *
	 * @param  DLM_Download_Version  $version  The version object.
	 *
	 * @return  Array  The version item.
	 *
	 * @since 5.0.0
	 */
	private function get_version_item( $version ) {
		return array(
			'download_id' => $version->get_download_id(),
			'version_id'  => $version->get_id(),
			'date'        => $version->get_date()->format( 'c' ),
			'version'     => $version->get_version(),
			'url'         => $version->get_url(),
			'filename'    => $version->get_filename(),
			'filetype'    => $version->get_filetype(),
			'downloads'   => $version->get_download_count(),
		);
	}
}
