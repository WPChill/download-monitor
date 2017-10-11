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



		$data->id                = $post->ID;
		$data->status            = $post->post_status;
		$data->title             = $post->post_title;
		$data->author            = $post->post_author;
		$data->expiration        = ( ( false != get_post_meta( $post->ID, $pm_prefix . 'expiration', true ) ) ? new \DateTime( get_post_meta( $post->ID, $pm_prefix . 'expiration', true ) ) : null );
		$data->description       = $post->post_content; // @todo check if we need to apply filters here
		$data->short_description = wp_trim_words( ( ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content ), absint( apply_filters( 'wpcm_vehicle_short_description_length', 30 ) ) );
		$data->condition         = get_post_meta( $post->ID, $pm_prefix . 'condition', true );
		$data->make              = get_post_meta( $post->ID, $pm_prefix . 'make', true );
		$data->model             = get_post_meta( $post->ID, $pm_prefix . 'model', true );
		$data->price        = get_post_meta( $post->ID, $pm_prefix . 'price', true );
		$data->color        = get_post_meta( $post->ID, $pm_prefix . 'color', true );
		$data->mileage      = get_post_meta( $post->ID, $pm_prefix . 'mileage', true );
		$data->fuel_type    = get_post_meta( $post->ID, $pm_prefix . 'fuel_type', true );
		$data->transmission = get_post_meta( $post->ID, $pm_prefix . 'transmission', true );
		$data->engine       = get_post_meta( $post->ID, $pm_prefix . 'engine', true );
		$data->power_hp     = get_post_meta( $post->ID, $pm_prefix . 'power_hp', true );
		$data->power_kw     = get_post_meta( $post->ID, $pm_prefix . 'power_kw', true );
		$data->body_style   = get_post_meta( $post->ID, $pm_prefix . 'body_style', true );
		$data->doors        = get_post_meta( $post->ID, $pm_prefix . 'doors', true );
		$data->sold         = get_post_meta( $post->ID, $pm_prefix . 'sold', true );

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