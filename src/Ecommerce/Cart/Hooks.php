<?php

namespace Never5\DownloadMonitor\Ecommerce\Cart;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Hooks {

	/**
	 * Setup cart hooks
	 */
	public function setup() {
		add_action( 'init', array( $this, 'catch_add_to_cart' ), 1 );
	}

	/**
	 * Catch add to cart request
	 */
	public function catch_add_to_cart() {
		if ( ! empty( $_GET['dlm-add-to-cart'] ) ) {
			$atc_id = absint( $_GET['dlm-add-to-cart'] );

			if ( $atc_id > 0 ) {
				Services::get()->service( 'cart' )->add_to_cart( $atc_id, 1 );
			}
		}
	}


}