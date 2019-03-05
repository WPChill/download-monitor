<?php

namespace Never5\DownloadMonitor\Shop\Admin;

class WritePanels {

	/**
	 * Setup the actions
	 */
	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add the meta boxes
	 */
	public function add_meta_box() {
		add_meta_box( 'download-monitor-shop', __( 'Shop', 'download-monitor' ), array(
			$this,
			'display_shop'
		), 'dlm_download', 'side', 'high' );

		add_meta_box( 'download-monitor-product-info', __( 'Product Information', 'download-monitor' ), array(
			$this,
			'display_product_information'
		), 'dlm_download_product', 'normal', 'high' );
	}

	/**
	 * @param \WP_Post $post
	 */
	public function display_shop( $post ) {

		try {
			/** @var \Never5\DownloadMonitor\Shop\DownloadProduct\DownloadProduct $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );
		} catch ( \Exception $e ) {
			$download = new \DLM_Download();
		}

		$price     = "";
		$taxable   = false;
		$tax_class = "";
		if ( $download->is_purchasable() ) {

			$price     = $download->get_price_for_user_input();
			$taxable   = $download->is_taxable();
			$tax_class = $download->get_tax_class();
		}

		download_monitor()->service( 'view_manager' )->display( 'meta-box/shop', array(
				'download'  => $download,
				'price'     => $price,
				'taxable'   => $taxable,
				'tax_class' => $tax_class
			)
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function display_product_information( $post ) {

		try {
			/** @var \Never5\DownloadMonitor\Shop\DownloadProduct\DownloadProduct $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );
		} catch ( \Exception $e ) {
			$download = new \DLM_Download();
		}

		$price     = "";
		$taxable   = false;
		$tax_class = "";


		$price     = $download->get_price_for_user_input();

		download_monitor()->service( 'view_manager' )->display( 'meta-box/product-information', array(
				'download'  => $download,
				'price'     => $price,
				'taxable'   => $taxable,
				'tax_class' => $tax_class
			)
		);
	}


}