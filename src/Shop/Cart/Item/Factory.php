<?php

namespace Never5\DownloadMonitor\Shop\Cart\Item;

class Factory {


	/**
	 * Make Item for given Download ID
	 *
	 * @param int $download_id
	 *
	 * @return Item
	 * @throws \Exception
	 */
	public function make( $download_id ) {

		/**
		 * Fetch the download
		 *
		 * @var \Never5\DownloadMonitor\Shop\DownloadProduct\DownloadProduct $download
		 */
		$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

		// don't continue if this download isn't purchasable
		if ( ! $download->is_purchasable() ) {
			throw new \Exception( 'Download not purchasable' );
		}

		// build item
		$item = new Item();
		$item->set_download_id( $download_id );
		$item->set_qty( 1 );
		$item->set_label( $download->get_title() );
		$item->set_subtotal( $download->get_price() );
		$item->set_tax_total( 0 ); /** @todo [TAX] Implement taxes */
		$item->set_total( $download->get_price() );

		return $item;
	}

}