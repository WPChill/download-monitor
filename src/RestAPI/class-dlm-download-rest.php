<?php

/**
 * Interaction with the download repository of the download plugin.
 *
 * @package DLM_Download_REST
 *
 * @since   5.0.0
 */
class DLM_Download_REST {
	/**
	 * Download plugin repository for downloads.
	 *
	 * @var Object
	 *
	 * @since 5.0.0
	 */
	private $download_repository;

	/**
	 * Version plugin repository for downloads.
	 *
	 * @var Object
	 *
	 * @since 5.0.0
	 */
	private $version_repository;

	/**
	 * DLM Rest API ref.
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
		$this->mda_api             = $api;
		$this->download_repository = download_monitor()->service( 'download_repository' );
		$this->version_repository  = download_monitor()->service( 'version_repository' );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @param  DLM_Rest_API  $args  DMR Rest API ref.
	 *
	 * @return object The DLM_REST_API object.
	 *
	 * @since 5.0.0
	 */
	public static function get_instance( $args ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Download_REST ) ) {
			self::$instance = new DLM_Download_REST( $args );
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

			// Fetch all downloads.
			'/downloads'                     => array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_downloads' ),
				),
			),

			// Fetch, update and delete a download.
			'/download/(?P<download_id>\d+)' => array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_download' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),
				),

				array(
					'methods'  => 'DELETE',
					'callback' => array( $this, 'delete_download' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),
				),

				array(
					'methods'  => 'PATCH',
					'callback' => array( $this, 'update_download' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
						'title'       => array(
							'required'    => false,
							'type'        => 'string',
							'description' => __( 'The download\'s title', 'download-monitor' ),
						),
					),
				),
			),

			// Create a new download.
			'/download'                      => array(
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'store_download' ),
					'args'     => array(
						'title' => array(
							'required'    => true,
							'type'        => 'string',
							'description' => __( 'The download\'s title', 'download-monitor' ),
						),
					),
				),
			),

		);
	}

	/**
	 * Fetch all downloads
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function get_downloads( $req ) {
		$total_downloads = $this->download_repository->num_rows();
		$fetch_downloads = $this->download_repository->retrieve(
			array(
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$download_items = array_map(
			function ( $download ) {
				return $this->get_download_item( $download );
			},
			$fetch_downloads
		);

		return new WP_REST_Response(
			array(
				'count' => $total_downloads,
				'items' => $download_items,
			)
		);
	}

	/**
	 * Fetch a single download
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function get_download( $req ) {
		$params = $req->get_params();

		try {
			$download = $this->download_repository->retrieve_single( $params['download_id'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		$download_data = $this->get_download_item( $download );

		return new WP_REST_Response( $download_data );
	}

	/**
	 * Delete a single download
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function delete_download( $req ) {
		$params = $req->get_params();

		$download = get_post( $params['download_id'] );

		if ( ! $download || 'dlm_download' !== $download->post_type ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		wp_delete_post( $download->ID, true );

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Create a new download
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function store_download( $req ) {
		$params = $req->get_params();

		if ( ! empty( $params['title'] ) ) {
			$download = new DLM_Download();
			$download->set_title( $params['title'] );
			$download->set_author( $this->mda_api->author_id );
			$download->set_status( 'publish' );

			try {
				$this->download_repository->persist( $download );
			} catch ( Exception $e ) {
				return new WP_Error( 'download_error', __( 'Unable to create a download item.', 'download-monitor' ), array( 'status' => 400 ) );
			}

			return new WP_REST_Response(
				array(
					'download_id' => $download->get_id(),
					'title'       => $download->get_title(),
				)
			);
		} else {
			return new WP_Error( 'rest_invalid_param', __( 'You must provide a title.', 'download-monitor' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Update a download
	 *
	 * @param  WP_REST_Request  $req  Request object.
	 *
	 * @return  WP_REST_Response|WP_Error  Json response
	 *
	 * @since 5.0.0
	 */
	public function update_download( $req ) {
		$params = $req->get_params();

		// Let's check if download exists first.
		try {
			$this->download_repository->retrieve_single( $params['download_id'] );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		$download = new DLM_Download();
		$download->set_id( $params['download_id'] );
		$download->set_title( $params['title'] );
		$download->set_author( $this->mda_api->author_id );
		$download->set_status( 'publish' );

		try {
			$this->download_repository->persist( $download );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_error', __( 'Unable to update the download item.', 'download-monitor' ), array( 'status' => 400 ) );
		}

		$download_data = $this->get_download_item( $download );

		return new WP_REST_Response( $download_data );
	}

	/**
	 * Get the download item.
	 *
	 * @return  callable  The download item.
	 *
	 * @since 5.0.0
	 */
	private function get_download_item( $download ) {
		$download_data = array(
			'download_id' => $download->get_id(),
			'title'       => $download->get_title(),
		);

		// Let's get the versions.
		$args                    = array( 'post_parent' => $download->get_id() );
		$download_versions       = $this->version_repository->retrieve( $args );
		$total_download_versions = $this->version_repository->num_rows( $args );

		if ( count( $download_versions ) > 0 ) {
			$download_data['versions'] = array(
				'count' => $total_download_versions,
				'items' => array_map(
					function ( $version ) {
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
					},
					$download_versions
				),
			);
		}

		return $download_data;
	}
}
