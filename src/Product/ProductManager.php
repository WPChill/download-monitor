<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Product_Manager {

	/**
	 * @var DLM_Product_Manager
	 */
	private static $instance = null;

	/**
	 * @var array<DLM_Product>
	 */
	private $products = array();

	/**
	 * @var DLM_Product_Error_Handler
	 */
	private $error_handler;

	/**
	 * Registered extensions
	 *
	 * @var
	 */
	private $registered_extensions = array();
	
	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->error_handler = DLM_Product_Error_Handler::get();
	}

	/**
	 * Singleton get method
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return DLM_Product_Manager
	 */
	public static function get() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return DLM_Product_Error_Handler
	 */
	public function error_handler() {
		return $this->error_handler;
	}

	/**
	 * Setup Product Manager
	 */
	public function setup() {

		add_action( 'admin_init', array( $this, 'load_extensions' ) );
	}

	/**
	 * Load extensions
	 * @hooked admin_init
	 */
	public function load_extensions() {
		// Load the registered extensions
		$this->registered_extensions = apply_filters( 'dlm_extensions', array() );

		// Check if we've got extensions
		if ( count( $this->registered_extensions ) > 0 ) {

			// Don't block local requests
			add_filter( 'block_local_requests', '__return_false' );

			// Load products
			add_action( 'admin_init', array( $this, 'load_products_wrapper' ),999 );

		}
	}

	/**
	 *
	 */
	public function load_products_wrapper() {

		$this->load_products( $this->registered_extensions );
	}

	/**
	 * Load Products
	 *
	 * @param array $extensions
	 */
	private function load_products( $extensions ) {

		// Check
		if ( count( $extensions ) > 0 ) {

			// Loop
			foreach ( $extensions as $extension ) {

				// backwards compat
				if ( ! is_array( $extension ) ) {
					$extension = array(
						'file'    => $extension,
						'version' => false,
						'name'    => "",
					);
				}

				// Setup new Product
				$product = new DLM_Product( $extension['file'], $extension['version'], $extension['name'] );

				// Set action for each extension
				do_action( 'dlm_extensions_action_' . $extension['file'], $extension );

				// Add product to products property
				$this->products[ $extension['file'] ] = $product;
			}

		}

	}

	/**
	 * Get products
	 *
	 * @return array<DLM_Product>
	 */
	public function get_products() {

		return $this->products;
	}

	/**
	 * Return extensions
	 *
	 * @return mixed
	 */
	public function get_extensions() {

		return $this->registered_extensions;
	}
}