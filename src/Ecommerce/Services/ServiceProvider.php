<?php

namespace Never5\DownloadMonitor\Ecommerce\Services;

use Never5\DownloadMonitor\Ecommerce\Libs\Pimple;
use Never5\DownloadMonitor\Ecommerce\Libs\Pimple\Container;

class ServiceProvider implements Pimple\ServiceProviderInterface {

	/**
	 * Register our DLM E-Commerce services
	 *
	 * @param Container $container
	 */
	public function register( Container $container ) {

		$container['session_repository'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\WordPressRepository();
		};

		$container['session_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\Factory();
		};

		$container['session_item_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\Item\Factory();
		};
	}


}