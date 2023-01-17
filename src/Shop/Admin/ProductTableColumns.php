<?php

namespace WPChill\DownloadMonitor\Shop\Admin;

use WPChill\DownloadMonitor\Shop\Services\Services;
use WPChill\DownloadMonitor\Shop\Util\PostType;

class ProductTableColumns {

	/**
	 * Setup product columns
	 */
	public function setup() {
		add_filter( 'manage_edit-' . PostType::KEY . '_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . PostType::KEY . '_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
		add_filter( 'manage_edit-' . PostType::KEY . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'the_title', array( $this, 'prepend_id_to_title' ), 15, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'set_primary_column_name' ), 10, 2 );
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

		/** @var \WPChill\DownloadMonitor\Shop\Product\Product $product */
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

				if ( ! $wp_list_table ) {
					$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
				}

				$wp_list_table->column_title( $post );
				$downloads = $product->get_download_ids();

				if ( $downloads && ! empty( $downloads ) ) {
					echo '<div class="product-download__links">';
					foreach ( $downloads as $download_id ) {
						$download = $this->get_download( $download_id );
						if ( $download ) {
							echo '<a class="product-download__download-link" target="_blank" href="' . esc_url( get_edit_post_link( $download->get_id() ) ) . '"><code>' . esc_html( $download->get_title() ) . '</code></a>';
						}
					}
					echo '</div>';

				} else {
					echo '<div class="dlm-listing-no-file"><code>' . esc_html__( 'No Downloads provided', 'download-monitor' ) . '</code></div>';
				}
				break;
			case "thumb" :
				echo wp_kses_post( $product->get_image() );
				break;
			case "price" :
				echo esc_html( dlm_format_money( $product->get_price() ) );
				break;
			case "shortcode" :
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-shortcode" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy shortcode', 'download-monitor' ) . '</span><div class="dl-shortcode-copy"><code>[dlm_buy id="' . absint( $post_id ) . '"]</code><input type="text" readonly value="[dlm_buy id=\'' . absint( $post_id ) . '\']" class="dlm-copy-shortcode-input"></div></div></button>';
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
	public function prepend_id_to_title( $title, $id = null ) {

		if ( ! isset( $id ) ) {
			$id = get_the_ID();
		}

		if ( 'dlm_product' === get_post_type( $id ) ) {
			return '#' . $id . ' - ' . $title;
		}

		return $title;
	}

	/**
	 * Get the download based on post ID, used for setting columns info
	 *
	 * @param  mixed $post_id
	 * 
	 * @return void
	 * @since 4.6.0
	 */
	private function get_download( $post_id ) {

		/** @var DLM_Download $download */
		$downloads = download_monitor()->service( 'download_repository' )->retrieve(
			array(
				'p'           => absint( $post_id ),
				'post_status' => array( 'any', 'trash' ),
			),
			1
		);

		if ( 0 === count( $downloads ) ) {
			return;
		}

		return $downloads[0];
	}


	/**
	 * Defaults the primary column name to 'download_title'
	 *
	 * @access public
	 *
	 * @param string $column_name
	 *
	 * @return string
	 * @since 4.6.0
	 */
	public function set_primary_column_name( $column_name, $context ){

		if ( 'edit-dlm_product' === $context ) {

			return 'product_title';
		}

		return $column_name;
	}
}