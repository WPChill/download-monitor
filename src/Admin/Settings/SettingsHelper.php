<?php

class DLM_Settings_Helper {

	/** @var array */
	private $defaults;

	/**
	 * DLM_Settings_Helper constructor.
	 */
	public function __construct() {
		$this->setup_defaults();
	}

	/**
	 * Setup the defaults used in the get_option() method
	 */
	private function setup_defaults() {
		$this->defaults = apply_filters( 'dlm_settings_defaults', array(
			'dlm_shop_enabled'          => 0,
			'no_access_page'            => 0,
			'page_cart'                 => 0,
			'page_checkout'             => 0,
			'currency'                  => 'USD',
			'currency_pos'              => 'left',
			'decimal_separator'         => '.',
			'thousand_separator'        => ',',
			'default_gateway'           => 'paypal',
			'disable_cart'              => '0',
			'gateway_paypal_enabled'    => '1',
		) );
	}


	/**
	 * Get option
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function get_option( $key ) {
		// get option from DB
		return apply_filters( 'dlm_get_option', get_option( 'dlm_' . $key, ( isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null ) ), $key );
	}

	/**
	 * Prevent duplicate downloads/logs
	 *
	 * @return bool
	 *
	 * @since 4.9.4
	 */
	public static function no_duplicate_download(): bool {
		/**
		 * Filter to disable the no duplicate download feature
		 *
		 * @hook dlm_no_duplicate_download
		 *
		 * @since 4.9.4
		 */
		return apply_filters( 'dlm_no_duplicate_download', 'production' === wp_get_environment_type() );
	}
}
