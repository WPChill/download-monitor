<?php

/**
 * Formats given price in cents into a pretty price that can be displayed to users.
 *
 * @param int $price
 *
 * @return string
 */
function dlm_format_money( $price ) {
	return \Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service( 'format' )->money( $price );
}

function dlm_checkout_fields() {
	\Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service('checkout_field')->output_all_fields();
}