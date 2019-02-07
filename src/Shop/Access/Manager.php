<?php

namespace Never5\DownloadMonitor\Shop\Access;

use Never5\DownloadMonitor\Shop\Services\Services;

class Manager {

	/**
	 * Setup Access related things
	 */
	public function setup() {
		add_filter( 'dlm_can_download', array( $this, 'check_access' ), 30, 3 );
	}

	/**
	 * Check if requester has access to download
	 *
	 * @param bool $has_access
	 * @param \DLM_Download $download
	 * @param \DLM_Download_Version $version
	 *
	 * @return bool
	 */
	public function check_access( $has_access, $download, $version ) {

		// check if request still has access at this point this is a purchasable download
		if ( true === $has_access && $download->is_purchasable() ) {

			/**
			 * This is a purchasable product.
			 * This means we need an order_id and an order_hash set in request
			 */

			$order_id   = ( isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : "" );
			$order_hash = ( isset( $_GET['order_hash'] ) ? $_GET['order_hash'] : "" );

			// if id or hash are empty, no access for you
			if ( empty( $order_id ) || empty( $order_hash ) ) {
				return false;
			}

			/** @var \Never5\DownloadMonitor\Shop\Order\Repository $order_repo */
			$order_repo = Services::get()->service( 'order_repository' );

			// try to fetch order with given order ID
			try {
				$order = $order_repo->retrieve_single( $order_id );
			} catch ( \Exception $exception ) {
				// can't find your order? no access for you
				return false;
			}

			// check if the given hash matches the hash we know the order has
			if ( $order_hash !== $order->get_hash() ) {
				return false;
			}

			// check if the order has the complete status
			if ( $order->get_status()->get_key() !== 'completed' ) {
				return false;
			}

			/**
			 * This request is valid.
			 * We do not return false, leaving $has_access to what it is (which is true if this point is reached).
			 */
		}

		return $has_access;
	}
}