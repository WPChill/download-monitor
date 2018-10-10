<?php

namespace Never5\DownloadMonitor\Ecommerce\Util;

class Page {

	/**
	 * Checks if current page is cart page
	 *
	 * @return bool
	 */
	public function is_cart() {
		return false;
	}

	/**
	 * Checks if current page is checkout page
	 *
	 * @return bool
	 */
	public function is_checkout() {
		return false;
	}

	/**
	 * Returns cart URL
	 *
	 * @return string
	 */
	public function get_cart_url() {
		return '';
	}

	/**
	 * Returns checkout URL
	 *
	 * @return string
	 */
	public function get_checkout_url() {
		return '';
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