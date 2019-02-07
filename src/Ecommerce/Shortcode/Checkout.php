<?php

namespace Never5\DownloadMonitor\Ecommerce\Shortcode;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Checkout {

	/**
	 * Register the shortcode
	 */
	public function register() {
		add_shortcode( 'dlm_checkout', array( $this, 'content' ) );
	}

	/**
	 * Shortcode content
	 *
	 * @param $atts array
	 */
	public function content( $atts ) {

		/** @var \Never5\DownloadMonitor\Ecommerce\Cart\Cart $cart */
		$cart = Services::get()->service( 'cart' )->get_cart();


		$endpoint = ( isset( $_GET['ep'] ) ? $_GET['ep'] : "" );

		switch ( $endpoint ) {
			case "complete":

				// get order data
				$order_id   = absint( ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : 0 );
				$order_hash = ( isset( $_GET['order_hash'] ) ? $_GET['order_hash'] : '' );
				$order      = null;

				if ( $order_id > 0 ) {
					/** @var \Never5\DownloadMonitor\Ecommerce\Order\WordPressRepository $op */
					try {
						$op    = Services::get()->service( 'order_repository' );
						$order = $op->retrieve_single( $order_id );

						// check order hashes
						if ( $order_hash !== $order->get_hash() ) {
							throw new \Exception( 'Order hash incorrect' );
						}
					} catch ( \Exception $e ) {
						return;
					}
				}

				// load the template
				download_monitor()->service( 'template_handler' )->get_template_part( 'shop/checkout/order-complete', '', '', array(
					'order_id' => $order_id,
					'order'    => $order
				) );

				break;
			case "":
				if ( ! $cart->is_empty() ) {
					download_monitor()->service( 'template_handler' )->get_template_part( 'shop/checkout', '', '', array(
						'cart'         => $cart,
						'url_cart'     => Services::get()->service( 'page' )->get_cart_url(),
						'url_checkout' => Services::get()->service( 'page' )->get_checkout_url()
					) );
				} else {
					download_monitor()->service( 'template_handler' )->get_template_part( 'shop/checkout/empty', '', '', array() );
				}

				break;
		}


	}

}