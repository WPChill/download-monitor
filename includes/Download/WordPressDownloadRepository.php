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
	 * @return bool
	 */
	public function persist( $download ) {
		return true;
	}

}