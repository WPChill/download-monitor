<?php

namespace Never5\DownloadMonitor\Ecommerce\Admin\Pages;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Orders {

	/**
	 * Setup admin order page
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
	}

	/**
	 * Add settings menu item
	 */
	public function add_admin_menu() {
		// Settings page
		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Orders', 'download-monitor' ), __( 'Orders', 'download-monitor' ), 'manage_options', 'download-monitor-orders', array(
			$this,
			'view'
		) );
	}

	/**
	 * Display view
	 */
	public function view() {
		if ( isset( $_GET['details'] ) && ! empty( $_GET['details'] ) ) {
			$order_id = absint( $_GET['details'] );
			try {
				$order = Services::get()->service( 'order_repository' )->retrieve_single( $order_id );
				download_monitor()->service( 'view_manager' )->display( 'order/page-order-details', array(
					'order' => $order
				) );
			} catch ( \Exception $exception ) {
				wp_die( __( "Order with that ID could not be found", 'download-monitor' ) );
			}

		} else {
			download_monitor()->service( 'view_manager' )->display( 'order/page-order-overview' );
		}


	}
}