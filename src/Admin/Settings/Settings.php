<?php

use WPChill\DownloadMonitor\Shop\Services\Services;

class DLM_Admin_Settings {

	/**
	 * Array used for preloading shortcodes to required pages
	 *
	 * @var array
	 *
	 * @since 4.9.6
	 */
	public $page_preloaders = array();

	/**
	 * Get settings URL
	 *
	 * @return string
	 */
	public static function get_url() {
		return admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' );
	}

	public function __construct() {
		// Add shortcodes to required pages
		$this->preload_shortcodes();
	}

	/**
	 * register_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {
		$settings = $this->get_settings();

		// register our options and settings
		foreach ( $settings as $tab_key => $tab ) {
			foreach ( $tab['sections'] as $section_key => $section ) {
				$option_group = 'dlm_' . $tab_key . '_' . $section_key;

				// Check to see if $section['fields'] is set, we could be using it for upsells
				if ( isset( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
						if ( $field['type'] == 'group' ) {
							foreach ( $field['options'] as $group_field ) {
								if ( ! empty( $group_field['name'] ) ) {
									if ( isset( $group_field['std'] ) ) {
										add_option( $group_field['name'], $group_field['std'] );
									}
									register_setting( $option_group, $group_field['name'] );
								}
							}
							continue;
						}

						if ( ! empty( $field['name'] ) && ! in_array( $field['type'], apply_filters( 'dlm_settings_display_only_fields', array( 'action_button' ) ) ) ) {
							if ( isset( $field['std'] ) ) {
								add_option( $field['name'], $field['std'] );
							}
							register_setting( $option_group, $field['name'] );
						}
					}
				}

				// on the overview page, we also register the enabled setting for every gateway. This makes the checkboxes to enable gateways work.
				if ( 'overview' == $section_key ) {
					$gateways = Services::get()->service( 'payment_gateway' )->get_all_gateways();
					if ( ! empty( $gateways ) ) {
						foreach ( $gateways as $gateway ) {
							register_setting( $option_group, 'dlm_gateway_' . esc_attr( $gateway->get_id() ) . '_enabled' );
						}
					}
				}
			}
		}
	}

	/**
	 * Method that return all Download Monitor Settings
	 *
	 * @access public
	 * @return array
	 */
	public function get_settings() {
		$settings = array(
			'general'            => array(
				'title'    => __( 'General', 'download-monitor' ),
				'sections' => array(
					'general' => array(
						'title'  => __( 'General settings', 'download-monitor' ),
						'fields' => array(
							array(
								'name'        => 'dlm_download_endpoint',
								'type'        => 'text',
								'std'         => 'download',
								'placeholder' => __( 'download', 'download-monitor' ),
								'label'       => __( 'Download Endpoint', 'download-monitor' ),
								'desc'        => sprintf( __( 'Define what endpoint should be used for download links. By default this will be %s( %s ).', 'download-monitor' ), '<strong>download</strong>', esc_url( home_url() ) . '<strong>/download/</strong>' ),
							),
							array(
								'name'    => 'dlm_download_endpoint_value',
								'std'     => 'ID',
								'label'   => __( 'Endpoint Value', 'download-monitor' ),
								'desc'    => sprintf( __( 'Define what unique value should be used on the end of your endpoint to identify the downloadable file. e.g. ID would give a link like <strong>10</strong> ( %s%s )', 'download-monitor' ), home_url( '/download/' ), '<strong>10/</strong>' ),
								'type'    => 'select',
								'options' => array(
									'ID'   => __( 'Download ID', 'download-monitor' ),
									'slug' => __( 'Download slug', 'download-monitor' ),
								),
							),
							array(
								'name'     => 'dlm_default_template',
								'std'      => '',
								'label'    => __( 'Default Template', 'download-monitor' ),
								'desc'     => __( 'Choose which template is used for <strong>[download]</strong> shortcodes by default (this can be overridden by the <strong>format</strong> argument).', 'download-monitor' ),
								'type'     => 'select',
								'options'  => download_monitor()->service( 'template_handler' )->get_available_templates(),
								'priority' => 10,
							),
							array(
								'name'     => 'dlm_custom_template',
								'type'     => 'text',
								'std'      => '',
								'label'    => __( 'Custom Template', 'download-monitor' ),
								'desc'     => __( 'Leaving this blank will use the default <strong>content-download.php</strong> template file. If you enter, for example, <strong>button</strong>, the <strong>content-download-button.php</strong> template will be used instead. You can add custom templates inside your theme folder.', 'download-monitor' ),
								'priority' => 10,
							),
							array(
								'name'     => 'dlm_wp_search_enabled',
								'std'      => '',
								'label'    => __( 'Include in Search', 'download-monitor' ),
								'cb_label' => '',
								'desc'     => __( "If enabled, downloads will be included in the site's internal search results.", 'download-monitor' ),
								'type'     => 'checkbox',
							),
							array(
								'name'     => 'dlm_turn_off_file_browser',
								'std'      => '',
								'label'    => __( 'Disable file browser', 'download-monitor' ),
								'cb_label' => '',
								'desc'     => __( 'Disables the directory file browser.', 'download-monitor' ),
								'type'     => 'checkbox',
								'priority' => 60,
							),
						),
					),
				),
				'priority' => 10,
			),
			'advanced'           => array(
				'title'    => __( 'Advanced', 'download-monitor' ),
				'sections' => array(
					'page_setup' => array(
						'title'  => __( 'Pages', 'download-monitor' ),
						'fields' => array(
							array(
								'name'    => 'dlm_no_access_page',
								'std'     => '',
								'label'   => __( 'No Access Page', 'download-monitor' ),
								'desc'    => __( "Choose what page is displayed when the user has no access to a file. Don't forget to add the <strong>[dlm_no_access]</strong> shortcode to the page.", 'download-monitor' ),
								'type'    => 'lazy_select',
								'options' => array(),
							),
							array(
								'name'     => 'dlm_no_access_modal',
								'std'      => '0',
								'label'    => __( 'No Access Modal', 'download-monitor' ),
								'cb_label' => '',
								'desc'     => __( 'Open no access message in a modal (pop-up) window.', 'download-monitor' ),
								'type'     => 'checkbox',
							),
							array(
								'name'     => 'dlm_use_default_modal',
								'std'      => '1',
								'label'    => __( 'Use default modal', 'download-monitor' ),
								'cb_label' => '',
								'desc'     => __( 'When enabled, the content of the "No Access page" option will be displayed in the no access modal. If disabled, the modal will show content specific to each extension.', 'download-monitor' ),
								'type'     => 'checkbox',
							),
						),
					),
					'access'     => array(
						'title'  => __( 'Access', 'download-monitor' ),
						'fields' => array(
							array(
								'name'        => 'dlm_no_access_error',
								'std'         => sprintf( __( 'You do not have permission to access this download. %1$sGo to homepage%2$s', 'download-monitor' ), '<a href="' . home_url() . '">', '</a>' ),
								'placeholder' => '',
								'label'       => __( 'No access message', 'download-monitor' ),
								'desc'        => __( "The message that will be displayed to visitors when they don't have access to a file.", 'download-monitor' ),
								'type'        => 'textarea',
							),
							array(
								'name'        => 'dlm_ip_blacklist',
								'std'         => '192.168.0.0/24',
								'label'       => __( 'Blacklist IPs', 'download-monitor' ),
								'desc'        => __( 'List IP Addresses to blacklist, 1 per line. Use IP/CIDR netmask format for ranges. IPv4 examples: <strong>198.51.100.1</strong> or <strong>198.51.100.0/24</strong>. IPv6 examples: <strong>2001:db8::1</strong> or <strong>2001:db8::/32</strong>.', 'download-monitor' ),
								'placeholder' => '',
								'type'        => 'textarea',
							),
							array(
								'name'        => 'dlm_user_agent_blacklist',
								'std'         => 'Googlebot',
								'label'       => __( 'Blacklist user agents', 'download-monitor' ),
								'desc'        => __( 'List browser user agents to blacklist, 1 per line.  Partial matches are sufficient. Regex matching is allowed by surrounding the pattern with forward slashes, e.g. <strong>/^Mozilla.+Googlebot/</strong>', 'download-monitor' ),
								'placeholder' => '',
								'type'        => 'textarea',
							),
						),
					),
					'logging'    => array(
						'title'  => __( 'Reports', 'download-monitor' ),
						'fields' => array(
							array(
								'name'    => 'dlm_logging_ip_type',
								'std'     => '',
								'label'   => __( 'IP Address Logging', 'download-monitor' ),
								'desc'    => __( 'Define if and how you like to store IP addresses of users that download your files in your logs.', 'download-monitor' ),
								'type'    => 'select',
								'options' => array(
									'full'       => __( 'Store full IP address', 'download-monitor' ),
									'anonymized' => __( 'Store anonymized IP address (remove last 3 digits)', 'download-monitor' ),
									'none'       => __( 'Store no IP address', 'download-monitor' ),
								),
							),
							array(
								'name'     => 'dlm_count_unique_ips',
								'std'      => '',
								'label'    => __( 'Count unique IPs only', 'download-monitor' ),
								'cb_label' => '',
								'desc'     => sprintf( __( 'If enabled, the counter for each download will only increment and create a log entry once per IP address. Note that this option only works if %1$s is set to %2$s.', 'download-monitor' ), '<strong>' . __( 'IP Address Logging', 'download-monitor' ) . '</strong>', '<strong>' . __( 'Store full IP address', 'download-monitor' ) . '</strong>' ),
								'type'     => 'checkbox',
							),
						),
					),
				),
				'priority' => 20,
			),
			'lead_generation'    => array(
				'title'    => esc_html__( 'Content Locking', 'download-monitor' ),
				'badge'    => true,
				'sections' => array(
					'email_lock'    => array(
						'title'    => esc_html__( 'Email Lock', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
					'ninja_forms'   => array(
						'title'    => esc_html__( 'Ninja Forms', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
					'gravity_forms' => array(
						'title'    => esc_html__( 'Gravity Forms', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
					'cf7_lock' => array(
						'title'    => esc_html__( 'Contact Form 7', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
					'wpforms_lock' => array(
						'title'    => esc_html__( 'WP Forms', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
				),
				'priority' => 30,
			),
			'external_hosting'   => array(
				'title'    => esc_html__( 'External Hosting', 'download-monitor' ),
				'badge'    => true,
				'sections' => array(
					'amazon_s3'    => array(
						'title'    => esc_html__( 'Amazon S3', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
					'google_drive' => array(
						'title'    => esc_html__( 'Google Drive', 'download-monitor' ),
						'fields'   => array(),
						'sections' => array(),
						'badge'    => true,
					),
				),
				'priority' => 40,
			),
			'integration'        => array(
				'title'    => esc_html__( 'Integration', 'download-monitor' ),
				'badge'    => true,
				'sections' => array(),
				'priority' => 50,
			),
			'email_notification' => array(
				'title'    => esc_html__( 'Emails', 'download-monitor' ),
				'badge'    => true,
				'sections' => array(),
				'priority' => 60,
			),
			'license' => array(
				'title'    => esc_html__( 'License', 'download-monitor' ),
				'badge'    => true,
				'sections' => array(),
				'priority' => 100,
			),
		);

		$settings['shop'] = array(
			'title'    => __( 'Shop', 'download-monitor' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'General', 'download-monitor' ),
					'fields' => array(
						array(
							'name'     => 'dlm_shop_enabled',
							'std'      => '',
							'label'    => __( 'Shop', 'download-monitor' ),
							'cb_label' => '',
							'desc'     => __( 'If enabled, allows you to sell your downloads via Download Monitor.', 'download-monitor' ),
							'type'     => 'checkbox',
							'priority' => 20,
						),
					),
				),
			),
			'priority' => 15,
		);

		$settings['shop']['sections']['general']['fields'] = array_merge(
			$settings['shop']['sections']['general']['fields'],
			array(
				array(
					'name'  => 'dlm_invoice_prefix',
					'type'  => 'text',
					'std'   => '',
					'label' => __( 'Invoice Prefix', 'download-monitor' ),
					'desc'  => __( 'This prefix is added to the invoice ID. Enter an unique prefix here.', 'download-monitor' ),
				),
				array(
					'name'    => 'dlm_base_country',
					'std'     => 'US',
					'label'   => __( 'Base Country', 'download-monitor' ),
					'desc'    => __( 'Where is your store located?', 'download-monitor' ),
					'type'    => 'select',
					'options' => Services::get()->service( 'country' )->get_countries(),
				),
				array(
					'name'    => 'dlm_currency',
					'std'     => 'USD',
					'label'   => __( 'Currency', 'download-monitor' ),
					'desc'    => __( 'In what currency are you selling?', 'download-monitor' ),
					'type'    => 'select',
					'options' => $this->get_currency_list_with_symbols(),
				),
				array(
					'name'    => 'dlm_currency_pos',
					'std'     => 'left',
					'label'   => __( 'Currency Position', 'download-monitor' ),
					'desc'    => __( 'The position of the currency symbol.', 'download-monitor' ),
					'type'    => 'select',
					'options' => array(
						'left'        => sprintf( __( 'Left (%s)', 'download-monitor' ), Services::get()->service( 'format' )->money( 9.99, array( 'currency_position' => 'left' ) ) ),
						'right'       => sprintf( __( 'Right (%s)', 'download-monitor' ), Services::get()->service( 'format' )->money( 9.99, array( 'currency_position' => 'right' ) ) ),
						'left_space'  => sprintf( __( 'Left with space (%s)', 'download-monitor' ), Services::get()->service( 'format' )->money( 9.99, array( 'currency_position' => 'left_space' ) ) ),
						'right_space' => sprintf( __( 'Right with space (%s)', 'download-monitor' ), Services::get()->service( 'format' )->money( 9.99, array( 'currency_position' => 'right_space' ) ) ),
					),
				),
				array(
					'name'  => 'dlm_decimal_separator',
					'type'  => 'text',
					'std'   => '.',
					'label' => __( 'Decimal Separator', 'download-monitor' ),
					'desc'  => __( 'The decimal separator of displayed prices.', 'download-monitor' ),
				),
				array(
					'name'  => 'dlm_thousand_separator',
					'type'  => 'text',
					'std'   => ',',
					'label' => __( 'Thousand Separator', 'download-monitor' ),
					'desc'  => __( 'The thousand separator of displayed prices.', 'download-monitor' ),
				),
				array(
					'name'     => 'dlm_disable_cart',
					'std'      => '',
					'label'    => __( 'Disable Cart', 'download-monitor' ),
					'cb_label' => '',
					'desc'     => __( 'If enabled, your customers will be sent to your checkout page directly.', 'download-monitor' ),
					'type'     => 'checkbox',
				),
				array(
					'name'  => '',
					'type'  => 'title',
					'title' => __( 'Pages', 'download-monitor' ),
				),
				array(
					'name'    => 'dlm_page_cart',
					'std'     => '',
					'label'   => __( 'Cart page', 'download-monitor' ),
					'desc'    => __( 'Your cart page, make sure it has the <strong>[dlm_cart]</strong> shortcode.', 'download-monitor' ),
					'type'    => 'lazy_select',
					'options' => array(),
				),
				array(
					'name'    => 'dlm_page_checkout',
					'std'     => '',
					'label'   => __( 'Checkout page', 'download-monitor' ),
					'desc'    => __( 'Your checkout page, make sure it has the <strong>[dlm_checkout]</strong> shortcode.', 'download-monitor' ),
					'type'    => 'lazy_select',
					'options' => array(),
				),
			)
		);

		$settings['shop']['sections'] = array_merge( $settings['shop']['sections'], $this->get_payment_methods_sections() );

		// this is here to maintain backwards compatibility, use 'dlm_settings' instead
		$old_settings = apply_filters( 'download_monitor_settings', array() );

		// This is the correct filter
		$settings = apply_filters( 'dlm_settings', $settings );

		// Backwards compatibility for 4.3 and 4.4.4
		$settings = $this->backwards_compatibility_settings( $old_settings, $settings );

		// Let's sort the fields by priority
		foreach ( $settings as $key => $setting ) {
			// Check if we have sections
			if ( ! empty( $setting['sections'] ) ) {
				foreach ( $setting['sections'] as $s_key => $section ) {
					// Check if we have fields
					if ( ! empty( $section['fields'] ) ) {
						// Sort the fields by priority
						uasort(
							$settings[ $key ]['sections'][ $s_key ]['fields'],
							array(
								'DLM_Admin_Helper',
								'sort_data_by_priority',
							)
						);
					}
				}
			}
		}

		// If upsells are not removed, we need to remove empty tabs/sections
		if ( apply_filters( 'dlm_remove_upsells', false ) ) {
			// Cycle through all settings and unset tabs/sections that have no fields
			foreach ( $settings as $key => $setting ) {
				// If there are no sections, unset the tab
				if ( empty( $setting['sections'] ) ) {
					unset( $settings[ $key ] );
				} else {
					foreach ( $setting['sections'] as $s_key => $section ) {
						// IF there are no fields, unset the section
						if ( empty( $section['fields'] ) ) {
							unset( $settings[ $key ]['sections'][ $s_key ] );
						}
					}
				}

				if ( empty( $settings[ $key ]['sections'] ) ) {
					unset( $settings[ $key ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Register lazy load setting fields callbacks
	 */
	public function register_lazy_load_callbacks() {
		add_filter( 'dlm_settings_lazy_select_dlm_page_cart', array( $this, 'lazy_select_dlm_no_access_page' ) );
		add_filter( 'dlm_settings_lazy_select_dlm_page_checkout', array( $this, 'lazy_select_dlm_no_access_page' ) );
		add_filter( 'dlm_settings_lazy_select_dlm_no_access_page', array( $this, 'lazy_select_dlm_no_access_page' ) );
	}

	/**
	 * Fetch and returns pages on lazy select for dlm_no_access_page option
	 *
	 * @param  array  $options
	 *
	 * @return array
	 */
	public function lazy_select_dlm_no_access_page( $options ) {
		return $this->get_pages();
	}

	/**
	 * Backwards compatibility for settings
	 *
	 * @param $old_settings
	 * @param $settings
	 *
	 * @return mixed
	 * @since 4.4.5
	 */
	public function backwards_compatibility_settings( $old_settings, $settings ) {
		// First we check if there is info in $old_settings
		if ( empty( $old_settings ) ) {
			return $settings;
		}

		$compatibility_tabs = array(
			'access',
			'amazon_s3',
			'captcha',
			'downloading_page',
			'email_lock',
			'email_notification',
			'gravity_forms',
			'ninja_forms',
			'page_addon',
		);

		foreach ( $old_settings as $tab_key => $tab ) {
			// $tab[1] contains the fields inside the setting, not being set means it doesn't have any fields
			if ( ! empty( $tab[1] ) ) {
				if ( in_array( $tab_key, $compatibility_tabs ) ) {
					$tab_title   = false;
					$tab_section = $tab_key;

					if ( 'access' == $tab_key ) {
						$tab_parent = 'advanced';
					}

					if ( 'amazon_s3' == $tab_key ) {
						$tab_parent = 'external_hosting';
					}

					if ( 'captcha' == $tab_key ) {
						$tab_parent = 'integration';
					}

					if ( 'downloading_page' == $tab_key || 'page_addon' == $tab_key ) {
						$tab_parent  = 'advanced';
						$tab_section = 'page_setup';
						$tab_title   = true;
					}

					if ( 'email_lock' == $tab_key || 'twitter_lock' == $tab_key || 'gravity_forms' == $tab_key || 'ninja_forms' == $tab_key ) {
						$tab_parent = 'lead_generation';
					}

					if ( 'email_notification' == $tab_key ) {
						$tab_parent = 'email_notification';
						$tab_title  = true;

						// Reassign the title because the extension overwrittens it
						$settings['email_notification'] = array(
							'title' => esc_html__( 'Emails', 'download-monitor' ),
						);
					}

					if ( isset( $tab[0] ) && ! $tab_title ) {
						$settings[ $tab_parent ]['sections'][ $tab_section ] = array(
							'title'    => $tab[0],
							'sections' => array(),
						);
					}

					// Let's check if there are sections or fields so we can add other fields and not overwrite them
					if ( isset( $settings[ $tab_parent ]['sections'] ) && isset( $settings[ $tab_parent ]['sections'][ $tab_section ]['fields'] ) ) {
						$settings[ $tab_parent ]['sections'][ $tab_section ]['fields'] = array_merge( $settings[ $tab_parent ]['sections'][ $tab_section ]['fields'], $tab[1] );
					} else {
						$settings[ $tab_parent ]['sections'][ $tab_section ]['fields'] = $tab[1];
					}

					// Unset the previous used tab - new tabs are already provided thanks to upsells
					if ( 'email_notification' != $tab_key && 'terns_and_conditions' != $tab_key ) {
						unset( $settings[ $tab_key ] );
					}
				} else {
					foreach ( $tab[1] as $other_tab_fields ) {
						$settings['other']['sections']['other']['fields'][] = $other_tab_fields;
					}
				}
			}
		}

		// Check to see if there is any info in Other tab
		if ( isset( $settings['other'] ) ) {
			$settings['other']['title'] = esc_html__( 'Other', 'download-monitor' );
		}

		return $settings;
	}


	/**
	 * Return pages with ID => Page title format
	 *
	 * @return array
	 */
	private function get_pages() {
		// pages
		$pages = array(
			array(
				'key' => 0,
				'lbl' => __( 'Select Page', 'download-monitor' ),
			),
		);

		// get pages from db
		$db_pages = get_pages();

		// check and loop
		if ( count( $db_pages ) > 0 ) {
			foreach ( $db_pages as $db_page ) {
				$pages[] = array(
					'key' => $db_page->ID,
					'lbl' => $db_page->post_title,
				);
			}
		}

		// return pages
		return $pages;
	}

	/**
	 * Returns the list of all available currencies and add the symbol to the label
	 *
	 * @return array
	 */
	private function get_currency_list_with_symbols() {
		/** @var \WPChill\DownloadMonitor\Shop\Helper\Currency $currency_helper */
		$currency_helper = Services::get()->service( "currency" );

		$currencies = $currency_helper->get_available_currencies();

		// get_currency_symbol

		if ( ! empty( $currencies ) ) {
			foreach ( $currencies as $k => $v ) {
				$currencies[ $k ] = $v . ' (' . $currency_helper->get_currency_symbol( $k ) . ')';
			}
		}

		return $currencies;
	}

	/**
	 * Generate payment method sections for settings
	 *
	 * @return array
	 */
	private function get_payment_methods_sections() {
		$gateways = Services::get()->service( 'payment_gateway' )->get_all_gateways();

		// formatted array of gateways with id=>title map (used in select fields)
		$gateways_formatted = array();
		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $gateway ) {
				$gateways_formatted[ $gateway->get_id() ] = $gateway->get_title();
			}
		}

		/** Generate the overview sections */
		$sections = array(
			'overview' => array(
				'title'  => __( 'Payment Gateways', 'download-monitor' ),
				'fields' => array(
					array(
						'name'     => '',
						'std'      => 'USD',
						'label'    => __( 'Enabled Gateways', 'download-monitor' ),
						'desc'     => __( 'Check all payment methods you want to enable on your webshop.', 'download-monitor' ),
						'type'     => 'gateway_overview',
						'gateways' => $gateways,
					),
					array(
						'name'    => 'dlm_default_gateway',
						'std'     => 'paypal',
						'label'   => __( 'Default Gateway', 'download-monitor' ),
						'desc'    => __( 'This payment method will be pre-selected on your checkout page.', 'download-monitor' ),
						'type'    => 'select',
						'options' => $gateways_formatted,
					),
				),
			),
		);

		/** Generate sections for all gateways */
		if ( ! empty( $gateways ) ) {
			/** @var \WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway $gateway */
			foreach ( $gateways as $gateway ) {
				// Option to enable gateways already exists in the Payment Gateways. We should not add it again.
				$fields = array();

				$gateway_settings = $gateway->get_settings();
				if ( ! empty( $gateway_settings ) ) {
					$escaped_id = esc_attr( $gateway->get_id() );
					foreach ( $gateway_settings as $gw ) {
						$prefixed_field = $gw;

						$prefixed_field['name'] = 'dlm_gateway_' . $escaped_id . '_' . $prefixed_field['name'];

						$fields[] = $prefixed_field;
					}
				}

				// dlm_gateway_paypal_

				$sections[ $gateway->get_id() ] = array(
					'title'  => $gateway->get_title(),
					'fields' => $fields,
				);
			}
		}

		return $sections;
	}

	/**
	 * Preload shortcodes to required pages
	 *
	 * @return void
	 */
	private function preload_shortcodes() {
		/**
		 * Filter the shortcodes to preload to the required pages
		 *
		 * Array should consist of key => value pairs where the key is the option and the value is the shortcode to preload
		 *
		 * @hook  dlm_preload_shortcodes
		 *
		 * @param  array  $page_preloaders  The array of page preloaders.
		 *
		 * @return array
		 * @since 4.9.6
		 */
		$this->page_preloaders = apply_filters(
			'dlm_preload_shortcodes',
			array(
				'dlm_no_access_page' => '[dlm_no_access]',
				'dlm_page_cart'      => '[dlm_cart]',
				'dlm_page_checkout'  => '[dlm_checkout]',
			)
		);
		if ( ! empty( $this->page_preloaders ) ) {
			foreach ( $this->page_preloaders as $option => $shortcode ) {
				add_action( 'update_option_' . $option, array( $this, 'preload_shortcode_to_page' ), 15, 3 );
			}
		}
	}

	/**
	 * Add the required shortcode to the page content
	 *
	 * @param  mixed   $old     The old page ID.
	 * @param  mixed   $new     The new page ID.
	 * @param  string  $option  The option name.
	 *
	 * @return void
	 * @since 4.9.6
	 */
	public function preload_shortcode_to_page( $old, $new, $option ) {
		$page_id = absint( $new );
		if ( 0 === $page_id ) {
			return;
		}
		// 1. Get the unformatted post(page) content.
		$page = get_post( $page_id );

		// 2. Search the content for the existence of our shortcode.
		if ( false !== strpos( $page->post_content, $this->page_preloaders[ $option ] ) ) {
			// The page has the no access shortcode, return;
			return;
		}

		// 3. If we got here it means we need to add our shortcode to the page's content.
		$page->post_content .= $this->page_preloaders[ $option ];

		// 4. Finally, we update the post.
		wp_update_post( $page );
	}
}
