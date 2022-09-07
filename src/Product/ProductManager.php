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
				'dlm-buttons'                 => array(
					'version' => '4.0.6',
				),
				'dlm-captcha'                 => array(
					'version' => '4.2.4',
				),
				'dlm-downloading-page'        => array(
					'version' => '4.0.4',
				),
				'dlm-google-drive'            => array(
					'version' => '4.0.4',
				),
				'dlm-terms-and-conditions'    => array(
					'version' => '4.0.4',
				),
				'dlm-twitter-lock'            => array(
					'version' => '4.1.1',
				),
				'dlm-advanced-access-manager' => array(
					'version' => '4.0.7',
				),
				'dlm-email-notification'      => array(
					'version' => '4.1.9',
				),
				'dlm-csv-exporter'            => array(
					'version' => '4.0.3',
				),
				'dlm-gravity-forms'           => array(
					'version' => '4.0.5',
				),
				'dlm-ninja-forms'             => array(
					'version' => '4.1.2',
				),
				'dlm-email-lock'              => array(
					'version' => '4.3.1',
				),
				'dlm-csv-importer'            => array(
					'version' => '4.1.6',
				),
				'dlm-amazon-s3'               => array(
					'version' => '4.0.6',
				),
				'dlm-mailchimp-lock'          => array(
					'version' => '4.0.4',
				),
				'dlm-page-addon'              => array(
					'version' => '4.1.6',
				),
				'dlm-enhanced-metrics'        => array(
					'version' => '1.0.0',
				),
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

		$php_version    = phpversion();
		$table_header   = ''; // The table header of our markup.
		$table_end      = '</div></td></tr>'; // The ending of the table
		$addons_content = ''; // The inline/row content containing info about each addon specified in the requirements.
		$html           = ''; // General HTML to be shown in plugin inline notification.

		// Create the table header HTML markup.
		$table_header .= '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange">';
		$table_header .= '<div class="dlm-plugin-inline-notice">';
		$table_header .= '<div class="dlm-plugin-inline-notice__header">';
		$table_header .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">' . esc_html__( 'Extension', 'download-monitor' ) . '<span></div>';
		$table_header .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">' . esc_html__( 'Requirements', 'download-monitor' ) . '</span></div>';
		$table_header .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">' . esc_html__( 'Current', 'download-monitor' ) . '</span></div>';
		$table_header .= '<div class="dlm-plugin-inline-notice__line"><span class="dlm-plugin-inline-notice__strong">' . esc_html__( 'Actions', 'download-monitor' ) . '</span></div>';
		$table_header .= '</div>';

		foreach ( $addons as $slug => $addon ) {

			if ( isset( $this->addons_requirements[ $slug ] ) ) {

				$addon_present  = false; // Verify if the addon doesn't meet one or multiple requirements.
				$addon_row_req  = ''; // The addon row HTML markup for requirements info.
				$addon_row_curr = ''; // The addon row HTML markup for current info.
				$actions        = ''; // The actions content for each addon.

				$plugin_slug = $addon->get_plugin_name();
				$plugin_name = $addon->get_product_name();

				if ( version_compare( $addon->get_version(), $this->addons_requirements[ $slug ]['version'], '<' ) ) {

					$addon_present    = true;
					$required_version = $this->addons_requirements[ $slug ]['version'];
					$current_version  = $addon->get_version();

					$addon_row_req .= '<p>' . esc_html__( 'Extension version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__required-version"> ' . esc_html( $required_version ) . '</span> ' . esc_html__( ' or higher', 'download-monitor' ) . '</p>';
					$addon_row_curr      .= '<p>' . esc_html__( 'Extension version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__current-version"> ' . esc_html( $current_version ) . '</span></p>';

					if ( ! $addon->get_license()->is_active() ) {

						$actions .= '<div class="dlm-plugin-inline-notice__line"><a href="' . esc_url( admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' ) ) . '" target="_blank">' . esc_html__( 'Enter your license key', 'download-monitor' ) . '</a> or <a href="https://www.download-monitor.com/pricing/" target="_blank">' . esc_html__( 'Purchase a new one', 'download-monitor' ) . '</a></div>';

					} else {
						$actions .= '<div class="dlm-plugin-inline-notice__line">';

						$update_url = apply_filters( 'dlm_extension_inline_action_' . $plugin_slug, '', $addon );

						if ( ! empty( $update_url ) ) {
							$actions .= '<a href="' . esc_url( $update_url ) . '" target="_blank" class="update-link">' . esc_html__( 'Update', 'download-monitor' ) . ' ' . esc_html( $plugin_name ) . '</a>';
						} else {
							$actions .= '<a href="https://www.download-monitor.com/my-account/" target="_blank">' . esc_html__( 'Please update extension', 'download-monitor' ) . '</a>';
						}

						$actions .= '</div>';

					}
				}

				if ( isset( $this->addons_requirements[ $slug ]['php'] ) && version_compare( $php_version, $this->addons_requirements[ $slug ]['php'], '<' ) ) {

					$addon_present = true;

					$required_php_version = $this->addons_requirements[ $slug ]['php'];

					$addon_row_req .= '<p>' . esc_html__( 'PHP version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__required-version"> ' . esc_html( $required_php_version ) . '</span> ' . esc_html__( ' or higher', 'download-monitor' ) . '</p>';
					$addon_row_curr .= '<p>' . esc_html__( 'PHP version:', 'download-monitor' ) . '<span class="dlm-plugin-inline-notice__current-version"> ' . esc_html( $php_version ) . '</span></p>';

				}
			}

			// Now, let's create the addon row info content only if the addon doesn't meet the requirements.
			if ( isset( $addon_present ) && $addon_present ) {

				$addons_content .= '<div class="dlm-plugin-inline-notice__row">';
				$addons_content .= '<div class="dlm-plugin-inline-notice__line">' . $plugin_name . '</div>';
				$addons_content .= '<div class="dlm-plugin-inline-notice__line">' . $addon_row_req . '</div>';
				$addons_content .= '<div class="dlm-plugin-inline-notice__line">' . $addon_row_curr . '</div>';
				$addons_content .= $actions;
				$addons_content .= '</div>';
			}
		}

		// If there is content in the addons_content variable it means there is something to be displayed, so display it.
		if ( ! empty( $addons_content ) ) {
			$html .= $table_header . $addons_content . $table_end;
		}

		echo wp_kses_post( $html );
	}
}
