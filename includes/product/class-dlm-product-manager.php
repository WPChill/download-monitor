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
	private $products;

	/**
	 * Private constructor
	 */
	private function __construct() {
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
	 * Load Products
	 *
	 * @param array $extensions
	 */
	public function load_products( $extensions ) {

		// Check
		if ( count( $extensions ) > 0 ) {

			// Loop
			foreach ( $extensions as $extension ) {

				// Setup new Product
				$product = new DLM_Product( $extension );

				// Setup plugin actions and filters
				add_action( 'pre_set_site_transient_update_plugins', array( $product, 'check_for_updates' ) );
				add_filter( 'plugins_api', array( $product, 'plugins_api' ), 10, 3 );

				// Add product to products property
				$this->products[ $extension ] = $product;
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
	 * Handle errors from the API
	 *
	 * @param  array $errors
	 */
	/*
	public function handle_errors( $errors ) {

		if ( ! empty( $errors['no_key'] ) ) {
			$this->add_error( sprintf( 'A licence key for %s could not be found. Maybe you forgot to enter a licence key when setting up %s.', esc_html( $this->plugin_data['Name'] ), esc_html( $this->plugin_data['Name'] ) ) );
		} elseif ( ! empty( $errors['invalid_request'] ) ) {
			$this->add_error( 'Invalid update request' );
		} elseif ( ! empty( $errors['invalid_key'] ) ) {
			$this->add_error( $errors['invalid_key'], 'invalid_key' );
		} elseif ( ! empty( $errors['no_activation'] ) ) {

			// Deactivate license
			RP4WP_Updater_Key_API::deactivate( array(
				'api_product_id' => $this->plugin_slug,
				'licence_key'    => $this->api_key,
			) );

			$this->add_error( $errors['no_activation'] );
		}

	}
	*/
}