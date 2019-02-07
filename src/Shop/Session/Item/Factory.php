<?php

namespace Never5\DownloadMonitor\Shop\Session\Item;

class Factory {

	/**
	 * Generate key
	 *
	 * @return string
	 */
	private function generate_key() {
		return md5( uniqid( 'dlm_ecommerce_session_item_key', true ) . mt_rand( 0, 99 ) );
	}

	/**
	 * @param int $download_id
	 * @param int $qty
	 *
	 * @return Item
	 */
	public function make( $download_id, $qty ) {
		$item = new Item();

		$item->set_key( $this->generate_key() );
		$item->set_download_id( $download_id );
		$item->set_qty( $qty );

		return $item;
	}
}