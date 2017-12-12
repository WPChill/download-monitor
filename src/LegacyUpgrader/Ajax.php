<?php

class DLM_LU_Ajax {

	/**
	 * Setup AJAX report hooks
	 */
	public function setup() {
		add_action( 'wp_ajax_dlm_lu_get_download_queue', array( $this, 'handle_get_download_queue' ) );
		add_action( 'wp_ajax_dlm_lu_get_content_queue', array( $this, 'handle_get_content_queue' ) );
		add_action( 'wp_ajax_dlm_lu_upgrade_download', array( $this, 'handle_upgrade_download' ) );
		add_action( 'wp_ajax_dlm_lu_upgrade_content', array( $this, 'handle_upgrade_content_item' ) );
	}

	/**
	 * Handle dlm_lu_get_queue AJAX request
	 */
	public function handle_get_download_queue() {

		// @TODO add nonce check

		// queue object
		$queue = new DLM_LU_Download_Queue();

		// build queue
		$queue->build_queue();

		// send queue as response
		wp_send_json( $queue->get_queue() );

		// bye
		exit;
	}

	/**
	 * Handle dlm_lu_upgrade_download AJAX request
	 */
	public function handle_upgrade_download() {

		// @TODO add nonce check

		// get download id
		$download_id = absint( $_GET['download_id'] );

		// upgrade download
		$upgrader = new DLM_LU_Download_Upgrader();

		if ( $upgrader->upgrade_download( $download_id ) ) {
			wp_send_json( array( 'success' => true ) );
		} else {
			wp_send_json( array( 'success' => false ) );
		}

		// alaaf
		exit;
	}

	/**
	 * Handle dlm_lu_get_queue AJAX request
	 */
	public function handle_get_content_queue() {

		// @TODO add nonce check

		// queue object
		$queue = new DLM_LU_Content_Queue();

		// build queue
		$queue->build_queue();

		// send queue as response
		wp_send_json( $queue->get_queue() );

		// bye
		exit;
	}

	/**
	 * Handle dlm_lu_upgrade_download AJAX request
	 */
	public function handle_upgrade_content_item() {

		// @TODO add nonce check

		// get download id
		$content_id = absint( $_GET['content_id'] );

		// upgrade download
		$upgrader = new DLM_LU_Content_Upgrader();

		if ( $upgrader->upgrade_item( $content_id ) ) {
			wp_send_json( array( 'success' => true ) );
		} else {
			wp_send_json( array( 'success' => false ) );
		}

		// alaaf
		exit;
	}

}