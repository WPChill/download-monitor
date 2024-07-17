<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Interaction with the download repository of the download plugin.
 *
 * @package DLM_Download_REST
 *
 * @since   5.0.0
 */
class DLM_Download_REST {

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
	 * @return object The DLM_Download_REST object.
	 *
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Download_REST ) ) {
			self::$instance = new DLM_Download_REST();
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
			// Fetch all downloads.
			'/downloads'                     => array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_downloads' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
				),
			),
			// Fetch, update and delete a download.
			'/download/(?P<download_id>\d+)' => array(
				// Get a single download.
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_download' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),
				),
				// Delete a single download.
				array(
					'methods'  => 'DELETE',
					'callback' => array( $this, 'delete_download' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),
				),
				// Update a single download.
				array(
					'methods'  => 'PATCH',
					'callback' => array( $this, 'update_download' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
					'args'     => array(
						'download_id' => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'The download\'s ID', 'download-monitor' ),
						),
					),
				),
			),
			// Create a new download.
			'/download'                      => array(
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'store_download' ),
					'permission_callback' => array( $dlm_rest_api, 'check_api_rights' ),
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
		$total_downloads = download_monitor()->service( 'download_repository' )->num_rows();
		$per_page        = $req->get_param( 'per_page' ) ? $req->get_param( 'per_page' ) : 10;
		$page            = $req->get_param( 'page' ) ? $req->get_param( 'page' ) : 1;
		$offset          = absint( ( $page - 1 ) * $per_page );
		$fetch_downloads = download_monitor()->service( 'download_repository' )->retrieve(
			array(
				'orderby' => 'date',
				'order'   => 'DESC',
			),
			$per_page,
			$offset
		);

		$download_items = array_map( array( $this, 'get_download_item' ), $fetch_downloads );

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
		$download_id = $req->get_param( 'download_id' );

		try {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
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
			$download = $this->create_download_object( $download, $params );

			try {
				download_monitor()->service( 'download_repository' )->persist( $download );
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

		$download_id = absint( $params['download_id'] );
		unset( $params['download_id'] );

		// Let's check if download exists first.
		try {
			$old_download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_not_found', __( 'Download does not exist.', 'download-monitor' ), array( 'status' => 404 ) );
		}

		$download = new DLM_Download();
		$download->set_id( $download_id );
		$download = $this->create_download_object( $download, $params, $old_download );

		/**
		 * Filters the download item before it is updated. Allow to modify the download item before it is updated.
		 *
		 * @hook  dlm_download_rest_api_update
		 *
		 * @param  DLM_Download  $download  The download item.
		 * @param  Array         $params    The parameters.
		 *
		 * @return DLM_Download  The download item.
		 *
		 * @since 5.0.0
		 */
		$download = apply_filters( 'dlm_download_rest_api_update', $download, $params );

		try {
			download_monitor()->service( 'download_repository' )->persist( $download );
		} catch ( Exception $e ) {
			return new WP_Error( 'download_error', __( 'Unable to update the download item.', 'download-monitor' ), array( 'status' => 400 ) );
		}

		// Handle the post meta, assuming that what is left from the params are post meta.
		foreach ( $params as $key => $param ) {
			update_post_meta( $download_id, $key, $param );
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
		$download_versions       = download_monitor()->service( 'version_repository' )->retrieve( $args );
		$total_download_versions = download_monitor()->service( 'version_repository' )->num_rows( $args );

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

	/**
	 * Create a download object.
	 *
	 * @param  DLM_Download  $download  The download object.
	 * @param  Array         $params    The parameters.
	 *
	 * @return  DLM_Download  The download object.
	 *
	 * @since 5.0.0
	 */
	private function create_download_object( $download, $params, $old_download = null ) {
		isset( $params['title'] ) ? $download->set_title( $params['title'] ) : $download->set_title( null !== $old_download ? $old_download->get_title() : 'Download' );
		isset( $params['status'] ) ? $download->set_status( $params['status'] ) : $download->set_status( null !== $old_download ? $old_download->get_status() : 'publish' );
		isset( $params['author'] ) ? $download->set_author( $params['author'] ) : $download->set_author( null !== $old_download ? $old_download->get_author() : '1' );
		isset( $params['description'] ) ? $download->set_description( $params['description'] ) : $download->set_description( null !== $old_download ? $old_download->get_description() : '' );
		isset( $params['excerpt'] ) ? $download->set_excerpt( $params['excerpt'] ) : $download->set_excerpt( null !== $old_download ? $old_download->get_excerpt() : '' );
		isset( $params['_members_only'] ) ? $download->set_members_only( $params['_members_only'] ) : $download->set_members_only( null !== $old_download ? $old_download->is_members_only() : 'no' );
		isset( $params['_featured'] ) ? $download->set_featured( $params['_featured'] ) : $download->set_featured( null !== $old_download ? $old_download->is_featured() : 'no' );
		isset( $params['_redirect_only'] ) ? $download->set_redirect_only( $params['_redirect_only'] ) : $download->set_redirect_only( null !== $old_download ? $old_download->is_redirect_only() : 'no' );
		isset( $params['_new_tab'] ) ? $download->set_new_tab( $params['_new_tab'] ) : $download->set_new_tab( null !== $old_download ? $old_download->is_new_tab() : 'no' );

		return $download;
	}
}
