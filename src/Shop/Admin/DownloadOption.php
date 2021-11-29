<?php

namespace WPChill\DownloadMonitor\Shop\Admin;

class DownloadOption {

	const OPTION_KEY = '_paid_only';

	/**
	 * Setup the download option
	 */
	public function setup() {

		// Add option
		add_action( 'dlm_options_end', array( $this, 'add_download_option' ), 10, 1 );

		// Save download options
		add_action( 'dlm_save_metabox', array( $this, 'save_download_option' ), 10, 1 );
	}

	/**
	 * Add mail lock to download options
	 *
	 * @param $post_id
	 */
	public function add_download_option( $post_id ) {

		wp_nonce_field( 'saving_dlm_paid_only', 'dlm-paid-only' );

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '" id="' . esc_attr( self::OPTION_KEY ) . '" ' . checked( get_post_meta( $post_id, self::OPTION_KEY, true ), '1', false ) . ' />
			<label for="' . esc_attr( self::OPTION_KEY ) . '">' . esc_html__( 'Paid Only', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . esc_html__( 'Only users who purchased a product that contains this download will be able to access the file.', 'download-monitor' ) . '</span>
		</p>';
	}

	/**
	 * Save download option
	 *
	 * @param $post_id
	 */
	public function save_download_option( $post_id ) {

		if ( ! isset( $_REQUEST['dlm-paid-only'] ) ) {
			return;
		}

		// check nonce
		// phpcs:ignore
		if ( ! wp_verify_nonce( $_REQUEST['dlm-paid-only'], 'saving_dlm_paid_only' ) ) {
			return;
		}

		$enabled = ( isset( $_POST[ self::OPTION_KEY ] ) );
		delete_post_meta( $post_id, self::OPTION_KEY );
		if ( $enabled ) {
			add_post_meta( $post_id, self::OPTION_KEY, 1 );
		}

	}
}
