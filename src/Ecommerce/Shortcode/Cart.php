<?php

namespace Never5\DownloadMonitor\Ecommerce\Shortcode;

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



		download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/cart', '', '', array(
			'items' => $session->get_items(),
			'coupons' => $session->get_coupons()
		) );
	}

}