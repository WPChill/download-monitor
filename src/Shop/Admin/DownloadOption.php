<?php

namespace WPChill\DownloadMonitor\Shop\Admin;

use WPChill\DownloadMonitor\Shop\Services\Services;

class DownloadOption {

	/**
	 * Setup the download option
	 */
	public function setup() {

		// Add option
		add_action( 'dlm_options_end', array( $this, 'add_download_option' ), 10, 1 );
	}

	/**
	 * Add paid only info to download options
	 *
	 * @param $post_id
	 */
	public function add_download_option( $post_id ) {
		$product_ids = self::get_download_products( $post_id );
		if( ! $product_ids ){
			return;
		}
		echo '<div class="dlm_product_locked_downloads">';
		echo '<span class="dlm_product_locked_label dlm-description" ><span class="dashicons dashicons-cart"></span>' . esc_html__( 'Paid Only', 'download-monitor' ) . '</span>';
			echo '<span class="dlm_product_locked_description dlm-description" >' . esc_html__( 'Only users who purchased a product that contains this download will be able to access the file.', 'download-monitor' ) . '</span>';
			echo '<span id="dlm_view_locked_products">' . esc_html__( 'View Products', 'download-monitor' ) . '<span class="dashicons dashicons-arrow-down"></span></span>';
			echo '<ul class="dlm_product_locked_list">';
				foreach( $product_ids as $product_id ){
					$product = Services::get()->service( 'product_repository' )->retrieve_single( $product_id );
					echo '<li class="dlm_product_locked_product">';
					echo '<span class="dlm_product_locked_product_dot"></span>';
					echo '<a class="dlm_product_locked_product_title" href="' . esc_url( get_permalink( $product_id ) ) . '"/>' . esc_html( $product->get_title() ) . ' </a>';
					echo '<a class="dlm_product_locked_product_edit" href="' . esc_url( get_edit_post_link( $product_id ) ) . '"/>' . esc_html__( 'Edit', 'download-monitor' ) . ' </a>';
					echo '</li>';
				}
			echo '</ul>';
		echo '</div>';
	}

	/**
	 * Get products that lock a download
	 *
	 * @param  int  $download_id
	 *
	 * @return array|bool
	 * @since 5.0.0
	 */
	public static function get_download_products( $download_id = 0 ) {
		if ( 0 == $download_id ) {
			return false;
		}
		global $wpdb;

		// SQL query to retrieve post IDs with the specified meta_key and meta_value
		$query = $wpdb->prepare( "
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = '_downloads'
			AND meta_value = %d
		", $download_id );

		// Execute the query
		$results = $wpdb->get_col( $query );

		// Check if there are any results
		if ( ! empty( $results ) ) {
			return $results;
		} else {
			return false;
		}
	}
}
