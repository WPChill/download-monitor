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
		$registered_extensions = apply_filters( 'dlm_extensions', array() );

		// Check if we've got extensions
		if ( count( $registered_extensions ) > 0 ) {

			// Don't block local requests
			add_filter( 'block_local_requests', '__return_false' );

			// Load products
			$this->load_products( $registered_extensions );

		}
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

				// Setup plugin actions and filters
				add_action( 'pre_set_site_transient_update_plugins', array( $product, 'check_for_updates' ) );
				add_filter( 'plugins_api', array( $product, 'plugins_api' ), 10, 3 );
				add_action( 'after_plugin_row_' . $product->get_plugin_name(), array( $product, 'after_plugin_row' ), 10, 2 );

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
}