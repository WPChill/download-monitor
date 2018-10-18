<?php

/**
 * Formats given price in cents into a pretty price that can be displayed to users.
 *
 * @param int $price
 *
 * @return string
 */
function dlm_format_money( $price, $include_currency_symbol = true ) {
	$currency_service = \Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service( 'currency' );

	$decimal_sep  = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
	$thousand_sep = ( ( '.' === $decimal_sep ) ? ',' : '.' );

	$new_price = $price / 100;
	$new_price = number_format( $new_price, 2, $decimal_sep, $thousand_sep );

	if ( $include_currency_symbol ) {
		if ( 'right' === $currency_service->get_currency_position() ) {
			$new_price = $new_price . " " . $currency_service->get_currency_symbol();
		} else {
			$new_price = $currency_service->get_currency_symbol() . " " . $new_price;
		}
	}


	return $new_price;
}

function dlm_checkout_fields() {
	\Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service('checkout_field')->output_all_fields();
}