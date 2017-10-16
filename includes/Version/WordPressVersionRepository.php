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
		$data->date           = $post->post_date;
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
	 * @return bool
	 */
	public function persist( $version ) {

		// TODO only save if DOWNLOAD_COUNT string not empty
		// update_post_meta( $file_id, '_download_count', absint( $file_download_count ) );

		/**
		 * TODO RECALCULATE ALL HASHES ON VERSION PERSIST
		 */
		$filesize       = - 1;
		$main_file_path = current( $files );

		if ( $main_file_path ) {
			$filesize = $file_manager->get_file_size( $main_file_path );
			$hashes   = $file_manager->get_file_hashes( $main_file_path );
			update_post_meta( $file_id, '_filesize', $filesize );
			update_post_meta( $file_id, '_md5', $hashes['md5'] );
			update_post_meta( $file_id, '_sha1', $hashes['sha1'] );
			update_post_meta( $file_id, '_crc32', $hashes['crc32'] );
		} else {
			update_post_meta( $file_id, '_filesize', $filesize );
			update_post_meta( $file_id, '_md5', '' );
			update_post_meta( $file_id, '_sha1', '' );
			update_post_meta( $file_id, '_crc32', '' );
		}

		return true;
	}

}