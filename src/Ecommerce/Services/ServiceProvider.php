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

		$container['currency'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Helper\Currency();
		};

		$container['country'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Helper\Country();
		};

		$container['session_repository'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\WordPressRepository();
		};

		$container['session_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\Factory();
		};

		$container['session_item_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\Item\Factory();
		};

		$container['session'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Session\Manager();
		};

		$container['tax_class_manager'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Tax\TaxClassManager();
		};

		$container['cart'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Cart\Manager();
		};

		$container['cart_item_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Cart\Item\Factory();
		};

		$container['page'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Util\Page();
		};

		$container['redirect'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Util\Redirect();
		};

		$container['checkout_field'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Checkout\Field();
		};

		$container['payment_gateway'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\Manager();
		};

		$container['order'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Order\Manager();
		};

		$container['order_factory'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Order\Factory();
		};

		$container['order_repository'] = function ( $c ) {
			return new \Never5\DownloadMonitor\Ecommerce\Order\WordPressRepository();
		};
	}


}