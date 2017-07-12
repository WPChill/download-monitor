<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$download = new DLM_Download( get_the_id() );

// check if download exists
if ( $download->exists() ) {

	// check if version is set
	if ( ! empty( $version ) ) {
		$version_id = $download->get_version_id( $version );
	}

	// check if version ID is set
	if ( isset( $version_id ) && 0 != $version_id ) {
		$download->set_version( $version_id );
	}

	$downloadURL = $download->get_the_download_link();

}else{
	$downloadURL = esc_url( home_url() ).'/not-found/';
}

wp_redirect($downloadURL);
