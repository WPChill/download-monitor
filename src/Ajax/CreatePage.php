<?php

class DLM_Ajax_CreatePage extends DLM_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'create_page' );
	}

	/**
	 * AJAX callback method
	 *
	 * @return void
	 */
	public function run() {
		// check nonce
		$this->check_nonce();

		// check caps
		if ( ! current_user_can( 'edit_posts' ) ) {
			exit( 0 );
		}


		wp_send_json( array( 'lol' ) );

		exit;
	}

}