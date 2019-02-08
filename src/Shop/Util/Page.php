<?php

namespace Never5\DownloadMonitor\Shop\Util;

class Page {

	/**
	 * Checks if current page is cart page
	 *
	 * @return bool
	 */
	public function is_cart() {
		error_log(get_the_ID() . ' :: '. download_monitor()->service( 'settings' )->get_option( 'page_cart' ), 0 );
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
	 * Returns add to cart URL for given download ID
	 *
	 * @param int $download_id
	 *
	 * @return string
	 */
	public function get_add_to_cart_url( $download_id ) {
		return add_query_arg( array( 'dlm-add-to-cart' => $download_id ), $this->get_cart_url() );
	}

	/**
	 * Returns checkout URL
	 *
	 * @param string $action
	 *
	 * @return string
	 */
	public function get_checkout_url( $action = '' ) {

		$endpoint = '';
		if ( ! empty( $action ) ) {
			switch ( $action ) {
				case 'complete':
					$endpoint = 'complete';
					break;
				default:
					$endpoint = '';
					break;
			}
		}

		$url = get_permalink( download_monitor()->service( 'settings' )->get_option( 'page_checkout' ) );

		if ( ! empty( $endpoint ) ) {
			$url = add_query_arg( 'ep', $endpoint, $url );
		}

		return $url;
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