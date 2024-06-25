<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_TC_Log_Manager
 *
 * @since 5.0.0
 */
class DLM_TC_Log_Manager {

	/**
	 * Setup class
	 *
	 *  @since 5.0.0
	 */
	public function setup() {
		// Filter the download to redirect regular download buttons of mail locked downloads
		add_filter( 'dlm_log_item', array( $this, 'filter_log' ), 10, 3 );
		add_filter( 'dlm_xhr_download_headers', array( $this, 'add_request_headers' ), 15, 5 );
	}

	/**
	 * Set XHR Headers required by Email Lock
	 *
	 * @param $headers
	 * @param $file_path
	 * @param $download
	 * @param $version
	 * @param $post_data
	 *
	 * @return mixed
	 * @since 5.0.0
	 */
	public function add_request_headers( $headers, $file_path, $download, $version, $post_data ) {

		if ( isset( $post_data['tc_accepted'] ) && '' !== $post_data['tc_accepted']  ) {
			$headers['x-tc_accepted'] = sanitize_text_field( wp_unslash( $post_data['tc_accepted'] ) );
		}

		return $headers;
	}

	/**
	 * Filter log item to add email address
	 *
	 * @param DLM_Log_Item $log_item
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 *
	 * @return DLM_Log_Item $log_item
	 * @since 5.0.0
	 */
	public function filter_log( $log_item, $download, $version ) {

		/**
		 * If this is set, and we're at the point of filtering the log, the access manager accepted the download request,
		 * add tc_accepted to download meta
		 */
		if ( isset( $_POST['tc_accepted'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['tc_accepted'] ) ) ) {
			// add set email to log item.
			$log_item->add_meta_data_item( 'tc_accepted', sanitize_text_field( wp_unslash( $_POST['tc_accepted'] ) ) );
		}

		/**
		 * Need to check the XHR sent headers also, located in $_POST['responseHeaders']
		 */
		if ( isset( $_POST['responseHeaders']['x-tc_accepted'] ) && '' !== $_POST['responseHeaders']['x-tc_accepted'] ) {
			// add set email to log item
			$log_item->add_meta_data_item( 'tc_accepted', sanitize_text_field( wp_unslash( $_POST['responseHeaders']['x-tc_accepted'] ) ) );
		}

		return $log_item;
	}
}
