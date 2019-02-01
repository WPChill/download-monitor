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
			'currency'           => 'USD',
			'currency_pos'       => 'left',
			'decimal_separator'  => '.',
			'thousand_separator' => ','
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

}