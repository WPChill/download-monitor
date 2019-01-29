<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

abstract class PaymentGateway {

	/** @var string */
	private $id;

	/** @var string */
	private $title;

	/** @var string */
	private $description;

	/** @var bool */
	private $enabled;

	/** @var array */
	private $settings = array();

	/**
	 * PaymentGateway constructor.
	 */
	public function __construct() {
		$this->setup_settings();
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * @return bool
	 */
	public function is_enabled() {
		/** @todo remove this later, for testing: */
		return true;

		return $this->enabled;
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * @param array $settings
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param bool $enabled
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = $enabled;
	}

	/**
	 * This is the place to setup all things related to your gateway.
	 * Need to capture an event? Set up the listener here.
	 * Want to add an extra page? This is the place.
	 * Add an extra endpoint? Set it up here.
	 *
	 * This method is triggered for every *enabled* gateway, on init (should still be safe to redirect as well at this point)
	 */
	public function setup_gateway() {
		/** Override in gateway */
	}

	/**
	 * Get the success URL for given order
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_success_url( $order_id ) {
		return add_query_arg( 'order_id', $order_id, Services::get()->service( 'page' )->get_checkout_url( 'complete' ) );
	}

	/**
	 * Get the failed URL for given order
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_failed_url( $order_id ) {
		return add_query_arg( 'order_id', $order_id, Services::get()->service( 'page' )->get_checkout_url( 'failed' ) );
	}

	/**
	 * Get the success URL for given order
	 *
	 * @param $order_id
	 *
	 * @return string
	 */
	public function get_cancel_url( $order_id ) {
		return add_query_arg( 'order_id', $order_id, Services::get()->service( 'page' )->get_checkout_url( 'cancelled' ) );
	}

	/**
	 * Setup settings for this payment gateway
	 * Default setting is if the gateway is enabled
	 */
	protected function setup_settings() {
		$this->set_settings( array(
			'enabled' => array(
				'type'        => 'checkbox',
				'title'       => '',
				'description' => '',
				'default'     => false
			)
		) );
	}

	/**
	 * Get value for given payment option key
	 *
	 * @param $option
	 *
	 * @return string
	 */
	protected function get_option( $option ) {
		return '';
	}

	/**
	 * @param $order_id
	 *
	 * @return Result
	 */
	abstract public function process( $order_id );
}