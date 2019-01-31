<?php

namespace Never5\DownloadMonitor\Ecommerce\Services;

use Never5\DownloadMonitor\Ecommerce\Libs\Pimple;
use Never5\DownloadMonitor\Ecommerce\Libs\Pimple\Container;
use Never5\DownloadMonitor\Ecommerce;

class ServiceProvider implements Pimple\ServiceProviderInterface {

	/**
	 * Register our DLM E-Commerce services
	 *
	 * @param Container $container
	 */
	public function register( Container $container ) {

		$container['currency'] = function ( $c ) {
			return new Ecommerce\Helper\Currency();
		};

		$container['country'] = function ( $c ) {
			return new Ecommerce\Helper\Country();
		};

		$container['format'] = function ( $c ) {
			return new Ecommerce\Helper\Format();
		};

		$container['session_repository'] = function ( $c ) {
			return new Ecommerce\Session\WordPressRepository();
		};

		$container['session_factory'] = function ( $c ) {
			return new Ecommerce\Session\Factory();
		};

		$container['session_item_factory'] = function ( $c ) {
			return new Ecommerce\Session\Item\Factory();
		};

		$container['session'] = function ( $c ) {
			return new Ecommerce\Session\Manager();
		};

		$container['tax_class_manager'] = function ( $c ) {
			return new Ecommerce\Tax\TaxClassManager();
		};

		$container['cart'] = function ( $c ) {
			return new Ecommerce\Cart\Manager();
		};

		$container['cart_item_factory'] = function ( $c ) {
			return new Ecommerce\Cart\Item\Factory();
		};

		$container['page'] = function ( $c ) {
			return new Ecommerce\Util\Page();
		};

		$container['redirect'] = function ( $c ) {
			return new Ecommerce\Util\Redirect();
		};

		$container['checkout_field'] = function ( $c ) {
			return new Ecommerce\Checkout\Field();
		};

		$container['payment_gateway'] = function ( $c ) {
			return new Ecommerce\Checkout\PaymentGateway\Manager();
		};

		$container['order'] = function ( $c ) {
			return new Ecommerce\Order\Manager();
		};

		$container['order_factory'] = function ( $c ) {
			return new Ecommerce\Order\Factory();
		};

		$container['order_repository'] = function ( $c ) {
			return new Ecommerce\Order\WordPressRepository();
		};

		$container['order_status'] = function ( $c ) {
			return new Ecommerce\Order\Status\Manager();
		};

		$container['order_status_factory'] = function ( $c ) {
			return new Ecommerce\Order\Status\Factory();
		};

		$container['order_transaction_factory'] = function ( $c ) {
			return new Ecommerce\Order\Transaction\Factory();
		};
	}


}