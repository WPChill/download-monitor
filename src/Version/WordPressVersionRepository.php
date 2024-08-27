<?php

class DLM_WordPress_Version_Repository implements DLM_Version_Repository {

	/**
	 * Filter query arguments for version WP_Query queries
	 *
	 * @param array $args
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	private function filter_query_args( $args = array(), $limit = 0, $offset = 0 ) {

		// must be absint
		$limit  = absint( $limit );
		$offset = absint( $offset );

		// start with removing reserved keys
		unset( $args['post_type'] );
		unset( $args['posts_per_page'] );
		unset( $args['offset'] );
		unset( $args['paged'] );
		unset( $args['nopaging'] );

		// setup our reserved keys
		$args['post_type']      = 'dlm_download_version';
		$args['posts_per_page'] = - 1;
		$args['orderby']        = 'menu_order';
		$args['order']          = 'ASC';

		// set limit if set
		if ( $limit > 0 ) {
			$args['posts_per_page'] = $limit;
		}

		// set offset if set
		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}

		return $args;
	}

	/**
	 * Returns number of rows for given filters
	 *
	 * @param array $filters
	 *
	 * @return int
	 */
	public function num_rows( $filters = array() ) {
		$q = new WP_Query();
		$q->query( $this->filter_query_args( $filters ) );

		return $q->found_posts;
	}

	/**
	 * Retrieve single version
	 *
	 * @param int $id
	 *
	 * @return DLM_Download_Version
	 * @throws Exception
	 */
	public function retrieve_single( $id ) {
		$versions = $this->retrieve( array( 'p' => absint( $id ) ) );

		if ( count( $versions ) != 1 ) {
			throw new Exception( "Version not found" );
		}

		return array_shift( $versions );
	}

	/**
	 * Retreieve the version download count
	 *
	 * @param mixed $version_id
	 *
	 * @return string
	 */
	public function retrieve_version_download_count( $version_id ) {
		global $wpdb;
		$version_count = 0;
		$download_id   = get_post( $version_id )->post_parent;
		// Check to see if the table exists first.
		if ( DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
			if ( isset( $GLOBALS['dlm_download'] ) ) {
				$download = $GLOBALS['dlm_download'];
				$meta = ( ! empty( $download->get_versions_download_counts() ) ) ? json_decode( $download->get_versions_download_counts(), true ) : array();
			} else {
				// Data in the table are based on Download and its meta, so we need to get the Download to find the version count.
				$download_count = $wpdb->get_results( $wpdb->prepare( "SELECT download.download_versions FROM {$wpdb->dlm_downloads} download WHERE download_id = %s;", $download_id ), ARRAY_A );
				// Version counts are present in the `download_versions` column of the table, as a json object, containing information for all versions.
				$meta = ( ! empty( $download_count[0]['download_versions'] ) ) ? json_decode( $download_count[0]['download_versions'], true ) : array();
			}
			// Get the information for our current version.
			if ( ! empty( $meta ) && isset( $meta[ $version_id ] ) ) {
				$version_count = $meta[ $version_id ];
			}
		}

		return apply_filters( 'dlm_add_version_meta_download_count', $version_count, $version_id );
	}

	/**
	 * Retrieve downloads
	 *
	 * @param array $filters
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array<DLM_Download>
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0 ) {
		$items = array();
		$posts = get_posts( $this->filter_query_args( $filters, $limit, $offset ) );

		if ( count( $posts ) > 0 ) {

			/** @var DLM_File_Manager $file_manager */
			$file_manager = download_monitor()->service( 'file_manager' );

			foreach ( $posts as $post ) {

				if ( isset( $items[ $post->ID ] ) ) {
					continue;
				}
				// Get all meta data.
				$meta_data = get_post_meta( $post->ID );
				// create download object.
				$version = new DLM_Download_Version();
				$version->set_id( $post->ID );
				$version->set_author( $post->post_author );
				$version->set_download_id( $post->post_parent );
				$version->set_menu_order( $post->menu_order );
				$version->set_date( new DateTime( $post->post_date ) );
				if ( DLM_Utils::meta_checker( $meta_data, '_version' ) ) {
					$version->set_version( strtolower( DLM_Utils::meta_checker( $meta_data, '_version' ) ) );
				}
				$version->set_download_count( absint( $this->retrieve_version_download_count( $version->get_id() ) ) );
				$version->set_meta_download_count( absint( DLM_Utils::meta_checker( $meta_data, '_download_count' ) ) );
				$version->set_filesize( DLM_Utils::meta_checker( $meta_data, '_filesize' ) );
				$version->set_md5( DLM_Utils::meta_checker( $meta_data, '_md5' ) );
				$version->set_sha1( DLM_Utils::meta_checker( $meta_data, '_sha1' ) );
				$version->set_sha256( DLM_Utils::meta_checker( $meta_data, '_sha256' ) );
				$version->set_crc32b( DLM_Utils::meta_checker( $meta_data, '_crc32' ) );

				// mirrors
				$mirrors = get_post_meta( $version->get_id(), '_files', true );
				if ( is_string( $mirrors ) ) {
					$mirrors = array_filter( (array) json_decode( $mirrors ) );
				} elseif ( is_array( $mirrors ) ) {
					$mirrors = array_filter( $mirrors );
				} else {
					$mirrors = array();
				}
				$version->set_mirrors( $mirrors );

				// url
				$url = current( $mirrors );
				$version->set_url( $url );

				// filename
				$filename = $file_manager->get_file_name( $url );
				$version->set_filename( $filename );

				// filetype
				$version->set_filetype( $file_manager->get_file_type( $filename ) );

				// fix empty file sizes
				if ( "" === $version->get_filesize() ) {
					// Get the file size
					$filesize = $file_manager->get_file_size( $url );

					update_post_meta( $version->get_id(), '_filesize', $filesize );
					$version->set_filesize( $filesize );
				}

				// add download to return array
				$items[ $post->ID ] = $version;
			}
		}

		return $items;
	}

	/**
	 * @param DLM_Download_Version $version
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function persist( $version ) {

		// check if new download or existing
		if ( 0 == $version->get_id() ) {

			// create
			$version_id = wp_insert_post( array(
				'post_title'   => $version->get_title(),
				'post_author'  => $version->get_author(),
				'post_type'    => 'dlm_download_version',
				'post_status'  => 'publish',
				'post_parent'  => $version->get_download_id(),
				'menu_order'   => $version->get_menu_order(),
				'post_date'    => $version->get_date()->format( 'Y-m-d H:i:s' ),
			) );

			if ( is_wp_error( $version_id ) ) {
				throw new \Exception( 'Unable to insert version in WordPress database' );
			}
			// set new vehicle ID
			$version->set_id( $version_id );

		} else {

			// update
			$version_id = wp_update_post( array(
				'ID'           => $version->get_id(),
				'post_title'   => $version->get_title(),
				'post_author'  => $version->get_author(),
				'post_status'  => 'publish',
				'post_parent'  => $version->get_download_id(),
				'menu_order'   => $version->get_menu_order(),
				'post_date'    => $version->get_date()->format( 'Y-m-d H:i:s' ),
			) );

			if ( is_wp_error( $version_id ) ) {
				throw new \Exception( 'Unable to update version in WordPress database' );
			}
		}

		// store version download count if it's not NULL
		if ( null !== $version->get_meta_download_count() ) {
			update_post_meta( $version_id, '_download_count', absint( $version->get_meta_download_count() ) );
		}

		// store version
		update_post_meta( $version_id, '_version', $version->get_version() );

		// store mirrors
		update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $version->get_mirrors() ) );

		// set filesize and hashes
		$filesize       = - 1;
		$main_file_path = current( $version->get_mirrors() );
		if ( $main_file_path ) {
			$filesize = download_monitor()->service( 'file_manager' )->get_file_size( $main_file_path );
			$hashes   = download_monitor()->service( 'hasher' )->get_file_hashes( $main_file_path );
			update_post_meta( $version_id, '_filesize', $filesize );
			update_post_meta( $version_id, '_md5', $hashes['md5'] );
			update_post_meta( $version_id, '_sha1', $hashes['sha1'] );
			update_post_meta( $version_id, '_sha256', $hashes['sha256'] );
			update_post_meta( $version_id, '_crc32', $hashes['crc32b'] );
		} else {
			update_post_meta( $version_id, '_filesize', $filesize );
			update_post_meta( $version_id, '_md5', '' );
			update_post_meta( $version_id, '_sha1', '' );
			update_post_meta( $version_id, '_sha256', '' );
			update_post_meta( $version_id, '_crc32', '' );
		}

		return true;
	}

}
