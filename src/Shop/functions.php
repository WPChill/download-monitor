<?php

/**
 * Formats given price in cents into a pretty price that can be displayed to users.
 *
 * @param int $price
 * @param array $args (see \Never5\DownloadMonitor\Shop\Helper\Format)
 *
 * @return string
 */
function dlm_format_money( $price, $args=array() ) {
	return \Never5\DownloadMonitor\Shop\Services\Services::get()->service( 'format' )->money( $price, $args );
}

function dlm_checkout_fields() {
	\Never5\DownloadMonitor\Shop\Services\Services::get()->service('checkout_field')->output_all_fields();
}