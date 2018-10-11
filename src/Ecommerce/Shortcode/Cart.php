<?php

namespace Never5\DownloadMonitor\Ecommerce\Shortcode;

use Never5\DownloadMonitor\Ecommerce\Services\Services;
use Never5\DownloadMonitor\Ecommerce\Session\Manager;

class Cart {

	/**
	 * Register the shortcode
	 */
	public function register() {
		add_shortcode( 'dlm_cart', array( $this, 'content' ) );
	}

	/**
	 * Shortcode content
	 *
	 * @param $atts array
	 */
	public function content( $atts ) {

		$cart = Services::get()->service( 'cart' )->get_cart();

		download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/cart', '', '', array(
			'cart'         => $cart,
			'url_checkout' => Services::get()->service( 'page' )->get_checkout_url()
		) );
	}

}