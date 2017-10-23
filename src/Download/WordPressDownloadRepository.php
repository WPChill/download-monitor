<?php

class DLM_WordPress_Download_Repository implements DLM_Download_Repository {

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
			throw new Exception( 'Download not found' );
		}

		$data = new stdClass();

		$data->id             = $post->ID;
		$data->status         = $post->post_status;
		$data->title          = $post->post_title;
		$data->slug           = $post->post_name;
		$data->author         = $post->post_author;
		$data->description    = $post->post_content;
		$data->excerpt        = wpautop( do_shortcode( $post->post_excerpt ) );
		$data->redirect_only  = ( 'yes' == get_post_meta( $post->ID, '_redirect_only', true ) );
		$data->featured       = ( 'yes' == get_post_meta( $post->ID, '_featured', true ) );
		$data->members_only   = ( 'yes' == get_post_meta( $post->ID, '_members_only', true ) );
		$data->download_count = absint( get_post_meta( $post->ID, '_download_count', true ) );

		/**
		 * This is added for backwards compatibility but will be removed in a later version!
		 * @deprecated 4.0
		 */
		$data->post = $post;

		return $data;
	}

	/**
	 * @param DLM_Download $download
	 *
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function persist( $download ) {

		// check if new download or existing
		if ( 0 == $download->get_id() ) {

			// create
			$download_id = wp_insert_post( array(
				'post_title'   => $download->get_title(),
				'post_content' => $download->get_description(),
				'post_excerpt' => $download->get_excerpt(),
				'post_author'  => $download->get_author(),
				'post_type'    => 'dlm_download',
				'post_status'  => $download->get_status()
			) );

			if ( is_wp_error( $download_id ) ) {
				throw new \Exception( 'Unable to insert download in WordPress database' );
			}
			// set new vehicle ID
			$download->set_id( $download_id );

		} else {

			// update
			$download_id = wp_update_post( array(
				'ID'           => $download->get_id(),
				'post_title'   => $download->get_title(),
				'post_content' => $download->get_description(),
				'post_excerpt' => $download->get_excerpt(),
				'post_author'  => $download->get_author(),
				'post_status'  => $download->get_status()
			) );

			if ( is_wp_error( $download_id ) ) {
				throw new \Exception( 'Unable to update download in WordPress database' );
			}

		}

		// persist 'Download Options'
		update_post_meta( $download_id, '_featured', ( ( $download->is_featured() ) ? 'yes' : 'no' ) );
		update_post_meta( $download_id, '_members_only', ( ( $download->is_members_only() ) ? 'yes' : 'no' ) );
		update_post_meta( $download_id, '_redirect_only', ( ( $download->is_redirect_only() ) ? 'yes' : 'no' ) );

		// other download meta
		update_post_meta( $download_id, '_download_count', $download->get_download_count() );

		// clear versions transient
		download_monitor()->service( 'download_manager' )->clear_transient( $download_id );

		return true;
	}

}