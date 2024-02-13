<?php

/**
 * Interaction with the version repository of the download plugin.
 *
 * @package DLM_Version_REST
 *
 * @since   5.0.0
 */
class DLM_Version_REST {

	/**
	 * Download plugin repository for version.
	 *
	 * @var Object
	 *
	 * @since 5.0.0
	 */
	private $version_repository;

	/**
	 * Download plugin manager for transients.
	 *
	 * @var Object
	 *
	 * @since 5.0.0
	 */
	private $transient_manager;

	/**
	 * DMR Rest API ref.
	 *
	 * @var DLM_Rest_API
	 *
	 * @since 5.0.0
	 */
	private $mda_api;

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 */
	public static $instance;

	/**
	 * Constructor.
	 *
	 * @param  DLM_Rest_API  $api  DMR Rest API ref.
	 *
	 * @since 5.0.0
	 */
	private function __construct( $api ) {
		$this->mda_api            = $api;
		$this->version_repository = download_monitor()->service( 'version_repository' );
		$this->transient_manager  = download_monitor()->service( 'transient_manager' );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @param  DLM_Rest_API  $args  DMR Rest API ref.
	 *
	 * @return object The DLM_Version_REST object.
	 *
	 * @since 5.0.0
	 */
	public static function get_instance( $args ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Version_REST ) ) {
			self::$instance = new DLM_Version_REST( $args );
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
		return array(

			// Fetch all versions from a download id.
			'/versions'                    => array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_versions' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),

				),
			),

			// Fetch, update and delete a version.
			'/version/(?P<version_id>\d+)' => array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_version' ),
					'args'     => array(
						'version_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The version\'s ID', 'download-monitor' ),
						),
					),
				),

				array(
					'methods'  => 'DELETE',
					'callback' => array( $this, 'delete_version' ),
					'args'     => array(
						'version_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The version\'s ID', 'download-monitor' ),
						),
					),
				),

				array(
					'methods'  => 'PATCH',
					'callback' => array( $this, 'update_version' ),
					'args'     => array(
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
					'methods'  => 'POST',
					'callback' => array( $this, 'store_version' ),
					'args'     => array(
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

		$total_versions = $this->version_repository->num_rows( $args );
		$fetch_versions = $this->version_repository->retrieve( $args );

		if ( count( $fetch_versions ) <= 0 ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
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
			$version = $this->version_repository->retrieve_single( $params['version_id'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'version_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
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

		$this->transient_manager->clear_versions_transient( $version->post_parent );
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
			$download_id = intval( $params['download_id'] );

			$version = new DLM_Download_Version();
			$version->set_download_id( $download_id );
			$version->set_version( $params['version'] );
			$version->set_mirrors( array( $params['url'] ) );
			$version->set_author( $this->mda_api->author_id );
			$version->set_date( new DateTime( current_time( 'mysql' ) ) );

			try {
				$this->version_repository->persist( $version );
				$this->transient_manager->clear_versions_transient( $download_id );

				// Get latest data.
				$version = $this->version_repository->retrieve_single( $version->get_id() );
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
				$version = $this->version_repository->retrieve_single( $params['version_id'] );
			} catch ( Exception $e ) {
				return new WP_Error( 'version_not_found', __( 'Version does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
			}

			$version->set_version( $params['version'] );
			$version->set_mirrors( array( $params['url'] ) );
			$version->set_date( new DateTime( current_time( 'mysql' ) ) );

			try {
				$this->version_repository->persist( $version );
				$this->transient_manager->clear_versions_transient( $version->get_download_id() );

				// Get latest data.
				$version = $this->version_repository->retrieve_single( $version->get_id() );
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
