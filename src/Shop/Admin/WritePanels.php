<?php

namespace WPChill\DownloadMonitor\Shop\Admin;

use WPChill\DownloadMonitor\Shop\Product\Product;
use WPChill\DownloadMonitor\Shop\Services\Services;
use WPChill\DownloadMonitor\Shop\Util\PostType;

class WritePanels {

	/**
	 * Setup the actions
	 */
	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'dlm_product_save', array( $this, 'save_meta_boxes' ), 1, 2 );
	}

	/**
	 * Add the meta boxes
	 */
	public function add_meta_box() {

		// We remove the Publish metabox and add to our queue
		remove_meta_box( 'submitdiv', 'dlm_download', 'side' );

		$meta_boxes = apply_filters(
			'dlm_product_metaboxes',
			array(
				array(
					'id'       => 'submitdiv',
					'title'    => esc_html__( 'Publish' ),
					'callback' => 'post_submit_meta_box',
					'screen'   => PostType::KEY,
					'context'  => 'side',
					'priority' => 1,
				),
				array(
					'id'       => 'download-monitor-product-information',
					'title'    => esc_html__( 'Product Information', 'download-monitor' ),
					'callback' => array( $this, 'download_product_information' ),
					'screen'   => PostType::KEY,
					'context'  => 'side',
					'priority' => 5,
				),
				array(
					'id'       => 'download-monitor-product-info',
					'title'    => esc_html__( 'Product Information', 'download-monitor' ),
					'callback' => array( $this, 'display_product_information' ),
					'screen'   => PostType::KEY,
					'context'  => 'normal',
					'priority' => 20,
				),
			)
		);

		uasort( $meta_boxes, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );

		foreach ( $meta_boxes as $metabox ) {
			// Priority is left out as we prioritise based on our sorting function
			add_meta_box( $metabox['id'], $metabox['title'], $metabox['callback'], $metabox['screen'], $metabox['context'], 'high' );
		}
	}

	/**
	 * save_post function.
	 *
	 * @access public
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		// validate nonce
		// phpcs:ignore
		if ( empty( $_POST['dlm_product_nonce'] ) || ! wp_verify_nonce( $_POST['dlm_product_nonce'], 'save_meta_data' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type != PostType::KEY ) {
			return;
		}

		// unset nonce because it's only valid of 1 post
		unset( $_POST['dlm_product_nonce'] );

		do_action( 'dlm_product_save', $post_id, $post );
	}

	/**
	 * save function.
	 *
	 * @access public
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 *
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {

		/**
		 * Fetch old download object
		 * There are certain props we don't need to manually persist here because WP does this automatically for us.
		 * These props are:
		 * - Product Title
		 * - Product Status
		 * - Product Author
		 * - Product Description & Excerpt
		 *
		 */
		/** @var Product $product */
		try {
			$product = Services::get()->service( 'product_repository' )->retrieve_single( $post_id );
		} catch ( \Exception $e ) {
			// product not found, no point in continuing
			return;
		}
		if( isset( $_POST['_dlm_price'] ) ) {
			$product->set_price_from_user_input( sanitize_text_field( wp_unslash( $_POST['_dlm_price'] ) ) );
		}

		if( isset( $_POST['_dlm_downloads'] ) ) {

			$product->set_download_ids( array_map( 'sanitize_text_field', wp_unslash( $_POST['_dlm_downloads'] ) ) );
		}
		// persist download
		Services::get()->service( 'product_repository' )->persist( $product );
	}

	/**
	 * @param \WP_Post $post
	 */
	public function display_product_information( $post ) {

		try {
			/** @var Product $product */
			$product = Services::get()->service( 'product_repository' )->retrieve_single( $post->ID );
		} catch ( \Exception $e ) {
			$product = Services::get()->service( 'product_factory' )->make();
		}

		$price     = "";
		$taxable   = false;
		$tax_class = "";

		$price = $product->get_price_for_user_input();

		/**
		 * Fetch downloads
		 */
		/** @todo fetch actual downloads */
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( array(
			'orderby' => 'title',
			'order'   => 'ASC'
		) );

		wp_nonce_field( 'save_meta_data', 'dlm_product_nonce' );

		download_monitor()->service( 'view_manager' )->display( 'meta-box/product-information', array(
				'product'              => $product,
				'price'                => $price,
				'taxable'              => $taxable,
				'tax_class'            => $tax_class,
				'downloads'            => $downloads,
				'current_download_ids' => $product->get_download_ids()
			)
		);
	}

	/**
	 * download_product_information function.
	 *
	 * @access public
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function download_product_information( $post ) {

		echo '<div class="dlm_information_panel">';

		if ( $post->ID ) {
			do_action( 'dlm_product_information_start', $post->ID, $post );
			?>
			<div>
				<p><?php echo esc_html__( 'ID', 'download-monitor' ); ?> </p>
				<input type="text" id="dlm-info-id" value="<?php echo esc_attr( $post->ID ); ?>" readonly onfocus="this.select()"/>
				<a href="#" title="<?php esc_attr_e( 'Copy ID', 'download-monitor' ); ?>" class="copy-dlm-button button button-primary dashicons dashicons-format-gallery" data-item="Id" style="width:40px;"></a><span></span>
			</div>
			<div>
				<p><?php echo esc_html__( 'Shortcode', 'download-monitor' ); ?> </p>
				<input type="text" id="dlm-info-id" value='[dlm_buy id="<?php echo esc_attr( $post->ID ); ?>"]' readonly onfocus="this.select()"/>
				<a href="#" title="<?php esc_attr_e( 'Copy shortcode', 'download-monitor' ); ?>" class="copy-dlm-button button button-primary dashicons dashicons-format-gallery" data-item="Shortcode" style="width:40px;"></a><span></span>
			</div>
			<?php
			do_action( 'dlm_product_information_end', $post->ID, $post );
		} else {
			echo '<p>' . esc_html__( 'No information for new products.', 'download-monitor' ) . '</p>';
		}

		echo '</div>';
	}

}