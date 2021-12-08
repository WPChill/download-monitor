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

	private $addons_requirements;

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->error_handler = DLM_Product_Error_Handler::get();

		add_action( 'after_plugin_row_download-monitor/download-monitor.php', array( $this, 'update_addons_notice' ), 999, 2 );

		$this->addons_requirements = apply_filters(
			'dlm_addons_requirements',
			array(
				// Dummy data. After we populate should be removed.
				 /* 'dlm-email-lock'        => array(
					 'version' => '4.2.2',
					 'php'     => '5.4',
				 ),
				'dlm-email-notification' => array(
					'version' => '4.2.2',
					'php'     => '10.4',
				),  */
			)
		);
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
	 *
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
						'name'    => '',
					);
				}

				// Setup new Product
				$product = new DLM_Product( $extension['file'], $extension['version'], $extension['name'] );

				// Remove this after migration.
				if ( apply_filters( "dlm_disable_update_for_{$extension['file']}", true ) ) {
					// Setup plugin actions and filters
					add_action( 'pre_set_site_transient_update_plugins', array( $product, 'check_for_updates' ) );
					add_filter( 'plugins_api', array( $product, 'plugins_api' ), 10, 3 );
					add_action( 'after_plugin_row_' . $product->get_plugin_name(), array( $product, 'after_plugin_row' ), 10, 2 );
				}

				// Set action for each extension
				do_action( 'dlm_extensions_action_' . $extension['file'], $extension, $product );

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
	 * Display update addons notice
	 *
	 * @param [type] $file
	 * @param [type] $plugin_data
	 * @return void
	 */
	public function update_addons_notice( $file, $plugin_data ) {

		$addons = $this->get_products();

		if ( empty( $addons ) || empty( $this->addons_requirements ) ) {
			return;
		}

		$php_version = phpversion();

		$html  = '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange">';
		$html .= '<div class="dlm-plugin-inline-notice">';
		$html .= '<div class="dlm-plugin-inline-notice__header">';
		$html .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">Extension<span></div>';
		$html .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">Requirements</span></div>';
		$html .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">Current</span></div>';
		$html .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">Actions</span></div>';
		$html .= '</div>';

		foreach ( $addons as $slug => $addon ) {

			if ( isset( $this->addons_requirements[ $slug ] ) ) {

				$plugin_slug  = $addon->get_plugin_name();
				$plugin_name  = $addon->get_product_name();
				$requirements = '<div class="dlm-plugin-inline-notice__line">';
				$current      = '<div class="dlm-plugin-inline-notice__line">';
				$actions      = '';

				$html .= '<div class="dlm-plugin-inline-notice__row">';
				$html .= '<div class="dlm-plugin-inline-notice__line">' . $plugin_name . '</div>';

				if ( version_compare( $addon->get_version(), $this->addons_requirements[ $slug ]['version'], '<' ) ) {

					$required_version = $this->addons_requirements[ $slug ]['version'];
					$current_version  = $addon->get_version();

					$requirements .= '<p>' . esc_html__( 'Extension version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__required-version"> ' . $required_version . '</span> ' . esc_html__( ' or higher', 'download-monitor' ) . '</p>';
					$current      .= '<p>' . esc_html__( 'Extension version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__current-version"> ' . $current_version . '</span></p>';

					if ( ! $addon->get_license()->is_active() ) {
						$actions .= '<div class="dlm-plugin-inline-notice__line"><a href="' . esc_url( admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' ) ) . '" target="_blank">Enter your license key</a> or <a href="https://www.download-monitor.com/pricing/" target="_blank">Purchase a new one</a></div>';

					} else {

						$update_link = wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $addon->get_plugin_name() ) ), 'upgrade-plugin_' . $plugin_slug );

						$actions .= '<div class="dlm-plugin-inline-notice__line"><a href="' . esc_url( $update_link ) . '" target="_blank" class="update-link">Update ' . $plugin_name . '</a></div>';
					}
				}

				if ( version_compare( $php_version, $this->addons_requirements[ $slug ]['php'], '<' ) ) {

					$required_php_version = $this->addons_requirements[ $slug ]['php'];

					$requirements .= '<p>' . esc_html__( 'PHP version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__required-version"> ' . $required_php_version . '</span> ' . esc_html__( ' or higher', 'download-monitor' ) . '</p>';
					$current      .= '<p>' . esc_html__( 'PHP version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__current-version"> ' . $php_version . '</span></p>';

				}

				$requirements .= '</div>';
				$current      .= '</div>';

				$html .= $requirements . $current . $actions . '</div>';
			}
		}

		$html .= '</div>';
		$html .= '</td></tr>';

		echo wp_kses_post( $html );
	}
}
