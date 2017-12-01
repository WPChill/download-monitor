<?php

class DLM_LU_Ajax {

	/**
	 * Setup AJAX report hooks
	 */
	public function setup() {
		add_action( 'wp_ajax_dlm_lu_get_queue', array( $this, 'handle_get_queue' ) );
	}

	/**
	 * Handle dlm_lu_get_queue AJAX request
	 */
	public function handle_get_queue() {

		// queue object
		$queue = new DLM_LU_Queue();

		// build queue
		$queue->build_queue();

		// send queue as response
		wp_send_json($queue->get_queue());

		// bye
		exit;
	}

}