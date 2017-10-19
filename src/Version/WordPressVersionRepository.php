<?php

class DLM_WordPress_Version_Repository implements DLM_Version_Repository {

	/**
	 * @param int $id
	 *
	 * @throws \Exception
	 *
	 * @return \stdClass()
	 */
	public function retrieve( $id ) {

		$post = get_post( $id );

		if ( null === $post ) {
			throw new Exception( 'Version not found' );
		}

		$data = new stdClass();

		$data->id             = $post->ID;
		$data->download_id    = $post->post_parent;
		$data->date           = new DateTime( $post->post_date );
		$data->version        = strtolower( get_post_meta( $data->id, '_version', true ) );
		$data->download_count = get_post_meta( $data->id, '_download_count', true );
		$data->filesize       = get_post_meta( $data->id, '_filesize', true );
		$data->md5            = get_post_meta( $data->id, '_md5', true );
		$data->sha1           = get_post_meta( $data->id, '_sha1', true );
		$data->crc32          = get_post_meta( $data->id, '_crc32', true );
		$data->mirrors        = get_post_meta( $data->id, '_files', true );

		if ( is_string( $data->mirrors ) ) {
			$data->mirrors = array_filter( (array) json_decode( $data->mirrors ) );
		} elseif ( is_array( $data->mirrors ) ) {
			$data->mirrors = array_filter( $data->mirrors );
		} else {
			$data->mirrors = array();
		}

		$data->url      = current( $data->mirrors );
		$data->filename = current( explode( '?', DLM_Utils::basename( $data->url ) ) );
		$data->filetype = strtolower( substr( strrchr( $data->filename, "." ), 1 ) );

		if ( "" === $data->filesize ) {
			// Get the file size
			$data->filesize = download_monitor()->service( 'file_manager' )->get_file_size( $data->url );

			update_post_meta( $data->id, '_filesize', $data->filesize );
		}

		return $data;
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
				'post_content' => '',
				'post_excerpt' => '',
				'post_author'  => $version->get_author(),
				'post_type'    => 'dlm_download_version',
				'post_status'  => 'publish',
				'post_parent'  => $version->get_download_id(),
				'post_date'    => $version->get_date()->format( 'Y-m-d H:i:s' )
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
				'post_content' => '',
				'post_excerpt' => '',
				'post_author'  => $version->get_author(),
				'post_status'  => 'publish',
				'post_parent'  => $version->get_download_id(),
				'menu_order'   => $version->get_menu_order(),
				'post_date'    => $version->get_date()->format( 'Y-m-d H:i:s' )
			) );

			if ( is_wp_error( $version_id ) ) {
				throw new \Exception( 'Unable to update version in WordPress database' );
			}

		}

		// store version download count if it's not NULL
		if ( null !== $version->get_download_count() ) {
			update_post_meta( $version_id, '_download_count', absint( $version->get_download_count() ) );
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
			$hashes   = download_monitor()->service( 'file_manager' )->get_file_hashes( $main_file_path );
			update_post_meta( $version_id, '_filesize', $filesize );
			update_post_meta( $version_id, '_md5', $hashes['md5'] );
			update_post_meta( $version_id, '_sha1', $hashes['sha1'] );
			update_post_meta( $version_id, '_crc32', $hashes['crc32'] );
		} else {
			update_post_meta( $version_id, '_filesize', $filesize );
			update_post_meta( $version_id, '_md5', '' );
			update_post_meta( $version_id, '_sha1', '' );
			update_post_meta( $version_id, '_crc32', '' );
		}

		return true;
	}

}