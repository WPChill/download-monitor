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

		if ( ! $cart->is_empty() ) {
			download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/checkout', '', '', array(
				'cart'         => $cart,
				'url_cart'     => Services::get()->service( 'page' )->get_cart_url(),
				'url_checkout' => Services::get()->service( 'page' )->get_checkout_url()
			) );
		} else {
			download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/checkout/empty', '', '', array() );
		}

	}

}