<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway;

class Result {

	/** @var bool */
	private $success;

	/** @var string */
	private $redirect = null;

	/**
	 * Result constructor.
	 *
	 * @param bool $success
	 * @param string $redirect
	 */
	public function __construct( $success, $redirect ) {
		$this->success  = $success;
		$this->redirect = $redirect;
	}

	/**
	 * @return bool
	 */
	public function is_success() {
		return $this->success;
	}

	/**
	 * @param bool $success
	 */
	public function set_success( $success ) {
		$this->success = $success;
	}

	/**
	 * @return string
	 */
	public function get_redirect() {
		return $this->redirect;
	}

	/**
	 * @param string $redirect
	 */
	public function set_redirect( $redirect ) {
		$this->redirect = $redirect;
	}

}