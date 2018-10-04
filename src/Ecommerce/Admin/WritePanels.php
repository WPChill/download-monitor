<?php

namespace Never5\DownloadMonitor\Ecommerce\Admin;

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
		add_meta_box( 'download-monitor-ecommerce', __( 'E-Commerce', 'download-monitor' ), array(
			$this,
			'display_ecommerce'
		), 'dlm_download', 'side', 'high' );
	}

	/**
	 * @param \WP_Post $post
	 */
	public function display_ecommerce( $post ) {

		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );
		} catch ( Exception $e ) {
			$download = new \DLM_Download();
		}

		download_monitor()->service( 'view_manager' )->display( 'meta-box/e-commerce', array(
				'download' => $download
			)
		);
	}


}