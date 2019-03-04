<?php

/**
 * Formats given price in cents into a pretty price that can be displayed to users.
 *
 * @param int $price
 * @param array $args (see \Never5\DownloadMonitor\Shop\Helper\Format)
 *
 * @return string
 */
function dlm_format_money( $price, $args = array() ) {
	return \Never5\DownloadMonitor\Shop\Services\Services::get()->service( 'format' )->money( $price, $args );
}

/**
 * @param array $values ([ 'option_key' => 'option_value' ])
 */
function dlm_checkout_fields( $values = array() ) {
	\Never5\DownloadMonitor\Shop\Services\Services::get()->service( 'checkout_field' )->output_all_fields( $values );
}

/**
 * Return if the shop functionality of Download Monitor is enabled
 *
 * @return bool
 */
function dlm_is_shop_enabled() {
	return ( 1 == download_monitor()->service( 'settings' )->get_option( 'shop_enabled' ) );
}