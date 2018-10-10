<?php

namespace Never5\DownloadMonitor\Ecommerce\Admin;

class Settings {

	/**
	 * Setup filter
	 */
	public function setup() {
		add_filter( 'download_monitor_settings', array( $this, 'add_settings' ), 1, 2 );
	}


	/**
	 * Add E-Commerce settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_settings( $settings ) {

		$service_page = \Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service( 'page' );

		$pages = $service_page->get_pages();

		$settings['pages'] = array(
			__( 'Pages', 'download-monitor' ),
			array(
				array(
					'name'    => 'dlm_page_cart',
					'std'     => '',
					'label'   => __( 'Cart page', 'download-monitor' ),
					'desc'    => __( 'Your cart page, make sure it has the <code>[dlm_cart]</code> shortcode.', 'download-monitor' ),
					'type'    => 'select',
					'options' => $pages
				),
				array(
					'name'    => 'dlm_page_checkout',
					'std'     => '',
					'label'   => __( 'Checkout page', 'download-monitor' ),
					'desc'    => __( 'Your checkout page, make sure it has the <code>[dlm_checkout]</code> shortcode.', 'download-monitor' ),
					'type'    => 'select',
					'options' => $pages
				),
			),
		);

		return $settings;
	}

}