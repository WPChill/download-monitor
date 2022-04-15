<?php

namespace Never5\DownloadMonitor\Shop\Admin;

use Never5\DownloadMonitor\Shop\Services\Services;
use Never5\DownloadMonitor\Shop\Util\PostType;

class ProductTableColumns {

	/**
	 * Setup product columns
	 */
	public function setup() {
		add_filter( 'manage_edit-' . PostType::KEY . '_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . PostType::KEY . '_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
		add_filter( 'manage_edit-' . PostType::KEY . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'the_title', array( $this, 'prepend_id_to_title' ), 10, 2 );
	}

	/**
	 * columns function.
	 *
	 * @access public
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_columns( $columns ) {
		$columns = array();

		$columns["cb"]        = "<input type=\"checkbox\" />";
		$columns["thumb"]     = '<span>' . __( "Image", 'download-monitor' ) . '</span>';
		$columns["product_title"]     = __( "Title", 'download-monitor' );
		$columns["shortcode"] = __( "Shortcode", 'download-monitor' );
		$columns["price"]     = __( "Price", 'download-monitor' );
		$columns["date"]      = __( "Date", 'download-monitor' );

		return $columns;
	}

	/**
	 * custom_columns function.
	 *
	 * @access public
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function column_data( $column, $post_id ) { 
		
		/** @var \Never5\DownloadMonitor\Shop\Product\Product $product */
		try {
			$product = Services::get()->service( 'product_repository' )->retrieve_single( $post_id );
		} catch ( \Exception $exception ) {
			return;
		}

		switch ( $column ) {
			case "product_title" :
				if ( ! class_exists( 'WP_List_Table' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
				}
				global $wp_list_table, $post;
				$wp_list_table->column_title( $post );
				break;
			case "thumb" :
				echo wp_kses_post( $product->get_image() );
				break;
			case "price" :
				echo esc_html( dlm_format_money( $product->get_price() ) );
				break;
			case "shortcode" :
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-shortcode" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy shortcode', 'download-monitor' ) . '</span><div class="dl-shortcode-copy"><code>[dlm_buy id="' . absint( $post_id ) . '"]</code><input type="text" value="[dlm_buy id=\'' . absint( $post_id ) . '\']" class="hidden"></div></div></button>';
				break;
		}
	}

	/**
	 * sortable_columns function.
	 *
	 * @access public
	 *
	 * @param mixed $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'price' => 'price'
		);

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Prepends the id to the title.
	 *
	 * @access public
	 *
	 * @param string $title
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function prepend_id_to_title( $title, $id){
		
		if( 'dlm_product' === get_post_type( $id ) ) {
			return '#' . $id . ' - ' . $title;
		}

        return $title;
    }
}