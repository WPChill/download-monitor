<?php

namespace Never5\DownloadMonitor\Shop\Cart;

use Never5\DownloadMonitor\Shop\Services\Services;

class Hooks {

	/**
	 * Setup cart hooks
	 */
	public function setup() {
		add_action( 'init', array( $this, 'catch_add_to_cart' ), 1 );
		add_action( 'init', array( $this, 'catch_remove_from_cart' ), 1 );
	}

	/**
	 * Catch add to cart request
	 */
	public function catch_add_to_cart() {
		if ( ! empty( $_GET['dlm-add-to-cart'] ) ) {
			$atc_id = absint( $_GET['dlm-add-to-cart'] );

			if ( $atc_id > 0 ) {
				Services::get()->service( 'cart' )->add_to_cart( $atc_id, 1 );
				Services::get()->service( 'redirect' )->to_cart();
			}
		}
	}

	/**
	 * Catch remove from cart request
	 */
	public function catch_remove_from_cart() {
		if ( ! empty( $_GET['dlm-remove-from-cart'] ) ) {
			$atc_id = absint( $_GET['dlm-remove-from-cart'] );

			if ( $atc_id > 0 ) {
				Services::get()->service( 'cart' )->remove_from_cart( $atc_id );
				Services::get()->service( 'redirect' )->to_cart();
			}
		}
	}


}