<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

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
		/** @todo remove this later, for testing: */ return true;
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