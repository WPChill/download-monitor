<?php

class DLM_Download_Factory {

	/**
	 * @param int $id
	 *
	 * @return DLM_Download | \Never5\DownloadMonitor\Ecommerce\DownloadProduct\DownloadProduct
	 */
	public function make( $id = 0 ) {

		$class_name = 'DLM_Download';

		// check if this is a download product (a download that can be sold), if so create a DownloadProduct instance
		if ( download_monitor()->is_ecommerce_enabled() && $id > 0 ) {
			$is_purchasable = get_post_meta( $id, 'is_purchasable', 1 );

			if ( 1 == $is_purchasable ) {
				$class_name = '\Never5\DownloadMonitor\Ecommerce\DownloadProduct\DownloadProduct';
			}
		}

		// make it filterable
		$class_name = apply_filters( 'dlm_download_factory_class_name', $class_name, $id );

		// check if class exists
		if ( ! class_exists( $class_name ) ) {
			$class_name = 'DLM_Download';
		}


		return new $class_name();
	}

}