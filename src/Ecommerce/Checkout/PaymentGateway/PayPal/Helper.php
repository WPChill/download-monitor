<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\PayPal;

use Never5\DownloadMonitor\Ecommerce\Dependencies\PayPal;

class Helper {

	/**
	 * Returns API context used in all PayPal API calls
	 *
	 * @todo fetch ID & SECRET from options
	 *
	 * @return PayPal\Rest\ApiContext
	 */
	public static function get_api_context() {
		return new PayPal\Rest\ApiContext(
			new PayPal\Auth\OAuthTokenCredential(
				PP_CLIENT_ID,
				PP_CLIENT_SECRET
			)
		);
	}

}