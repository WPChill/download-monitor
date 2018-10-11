<?php

namespace Never5\DownloadMonitor\Ecommerce\Util;

class Page {

	/**
	 * Checks if current page is cart page
	 *
	 * @return bool
	 */
	public function is_cart() {
		if ( download_monitor()->service( 'settings' )->get_option( 'page_cart' ) == get_the_ID() ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if current page is checkout page
	 *
	 * @return bool
	 */
	public function is_checkout() {
		if ( download_monitor()->service( 'settings' )->get_option( 'page_checkout' ) == get_the_ID() ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns cart URL
	 *
	 * @return string
	 */
	public function get_cart_url() {
		return get_permalink( download_monitor()->service( 'settings' )->get_option( 'page_cart' ) );
	}

	/**
	 * Returns checkout URL
	 *
	 * @return string
	 */
	public function get_checkout_url() {
		return get_permalink( download_monitor()->service( 'settings' )->get_option( 'page_checkout' ) );
	}

	/**
	 * Get all pages and format them in a id(k)=>title(v) array
	 *
	 * @return array
	 */
	public function get_pages() {
		// setup array with default option
		$pages = array(
			0 => '-- ' . __( 'no page', 'download-monitor' ) . ' --'
		);
		// get pages from WP
		$pages_raw = get_pages();
		// count. loop. add
		if ( count( $pages_raw ) > 0 ) {
			foreach ( $pages_raw as $page ) {
				$pages[ $page->ID ] = $page->post_title;
			}
		}

		// return
		return $pages;
	}

}