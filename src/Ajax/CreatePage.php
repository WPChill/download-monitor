<?php

use \Never5\DownloadMonitor\Util;

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

		if ( ! empty( $_GET['page'] ) ) {

			$pc      = new Util\PageCreator();
			$success = false;

			switch ( $_GET['page'] ) {
				case 'no-access':
					$success = $pc->create_no_access_page();
					break;
				case 'cart':
					$success = $pc->create_cart_page();
					break;
				case 'checkout':
					$success = $pc->create_checkout_page();
					break;
			}

			if ( $success ) {
				wp_send_json( array( 'result' => 'success' ) );
				exit;
			} else {
				wp_send_json( array(
					'result' => 'failed',
					'error'  => __( "Couldn't create page", 'download-monitor' )
				) );
			}
		}
		
		wp_send_json( array( 'result' => 'failed', 'error' => __( "No page set", 'download-monitor' ) ) );

		exit;
	}

}