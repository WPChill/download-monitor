<?php

namespace WPChill\DownloadMonitor\Shop\Util;

class Redirect {

	/**
	 * Redirect method
	 *
	 * @param string $url
	 */
	public function redirect( $url ) {
		wp_redirect( $url );
		exit;
	}

}