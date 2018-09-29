<?php

namespace Never5\DownloadMonitor\Ecommerce\Services;

use Never5\DownloadMonitor\Ecommerce\Libs\Pimple;

class Services {

	/** @var Services */
	private static $instance = null;

	/** @var Pimple\Container */
	private $container;

	/**
	 * Services constructor.
	 */
	private function __construct() {
		$this->container = new Pimple\Container();
		$provider        = new ServiceProvider();
		$provider->register( $this->container );
	}

	/**
	 * Singleton get method
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return Services
	 */
	public static function get() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get service with $service as key in Pimple container
	 *
	 * @param string $service
	 *
	 * @return mixed
	 */
	public function service( $service ) {
		return $this->container[ $service ];
	}

}