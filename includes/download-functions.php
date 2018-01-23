<?php

/**
 * Gets the name of the default template
 * @return string
 */
function dlm_get_default_download_template() {
	$default = get_option( 'dlm_default_template' );

	if ( $default == 'custom' ) {
		$default = get_option( 'dlm_custom_template' );
	}

	return $default;
}

// Filter the single_template with our custom function
function dlm_download_single_post_type_template($single) {
	global $wp_query, $post;
	/* Checks for single template by post type */
	if ($post->post_type == 'dlm_download' || $post->post_type == 'dlm_download_version'){
		$download_id = get_the_id();
		/** @var DLM_Download $download */
		$download = null;

		if ( $download_id > 0 ) {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
		}

		// Handle version (if set)
		$version_id = '';

		if ( ! empty( $_GET['version'] ) ) {
			$version_id = $download->get_version_id_version_name( $_GET['version'] );
		}

		if ( ! empty( $_GET['v'] ) ) {
			$version_id = absint( $_GET['v'] );
		}

		if ( $version_id ) {
			try {
				$version = download_monitor()->service( 'version_repository' )->retrieve_single( $version_id );
				$download->set_version( $version );
			} catch ( Exception $e ) {

			}
		}

		// Action on found download
		if ( ! is_null( $download ) && $download->exists() ) {
			if ( post_password_required( $download_id ) ) {
				wp_die( get_the_password_form( $download_id ), __( 'Password Required', 'download-monitor' ) );
			}
			$downloadURL = $download->get_the_download_link();
			wp_redirect($downloadURL);
			exit();
		} elseif ( $redirect = apply_filters( 'dlm_404_redirect', false ) ) {
			wp_redirect( $redirect );
			exit();
		} else {
			wp_die( __( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
		}
	}
	return $single;
}
add_filter('single_template', 'dlm_download_single_post_type_template');
