<?php

use Never5\DownloadMonitor\Shop\Services\Services;

class DLM_Admin_Settings {

	/**
	 * Get settings URL
	 *
	 * @return string
	 */
	public static function get_url() {
		return admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' );
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

				$option_group = "dlm_" . $tab_key . "_" . $section_key;

				// Check to see if $section['fields'] is set, we could be using it for upsells
				if ( isset( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
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
			'general'              => array(
				'title'    => __( 'General', 'download-monitor' ),
				'sections' => array(
					'general' => array(
						'title'  => __( 'Download', 'download-monitor' ),
						'fields' => array(
							array(
								'name'    => 'dlm_default_template',
								'std'     => '',
								'label'   => __( 'Default Template', 'download-monitor' ),
								'desc'    => __( 'Choose which template is used for <code>[download]</code> shortcodes by default (this can be overridden by the <code>format</code> argument).', 'download-monitor' ),
								'type'    => 'select',
								'options' => download_monitor()->service( 'template_handler' )->get_available_templates()
							),
							array(
								'name'  => 'dlm_custom_template',
								'type'  => 'text',
								'std'   => '',
								'label' => __( 'Custom Template', 'download-monitor' ),
								'desc'  => __( 'Leaving this blank will use the default <code>content-download.php</code> template file. If you enter, for example, <code>button</code>, the <code>content-download-button.php</code> template will be used instead. You can add custom templates inside your theme folder.', 'download-monitor' )
							),
							array(
								'name'     => 'dlm_shop_enabled',
								'std'      => '',
								'label'    => __( 'Shop Enabled', 'download-monitor' ),
								'cb_label' => __( 'Enable Shop', 'download-monitor' ),
								'desc'     => __( 'If enabled, allows you to sell your downloads via Download Monitor.', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_xsendfile_enabled',
								'std'      => '',
								'label'    => __( 'X-Accel-Redirect / X-Sendfile', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => __( 'If supported, <code>X-Accel-Redirect</code> / <code>X-Sendfile</code> can be used to serve downloads instead of PHP (server requires <code>mod_xsendfile</code>).', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_hotlink_protection_enabled',
								'std'      => '',
								'label'    => __( 'Prevent hotlinking', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => __( 'If enabled, the download handler will check the PHP referer to see if it originated from your site and if not, redirect them to the homepage.', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_allow_x_forwarded_for',
								'std'      => '0',
								'label'    => __( 'Allow Proxy IP Override', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => __( 'If enabled, Download Monitor will use the X_FORWARDED_FOR HTTP header set by proxies as the IP address. Note that anyone can set this header, making it less secure.', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_wp_search_enabled',
								'std'      => '',
								'label'    => __( 'Include in Search', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => __( "If enabled, downloads will be included in the site's internal search results.", 'download-monitor' ),
								'type'     => 'checkbox'
							),
						)
					),
				)
			),
			'advanced'             => array(
				'title'    => __( 'Advanced', 'download-monitor' ),
				'sections' => array(
					'page_setup' => array(
						'title'  => __( 'Pages Setup', 'download-monitor' ),
						'fields' => array(
							array(
								'name'        => 'dlm_download_endpoint',
								'type'        => 'text',
								'std'         => 'download',
								'placeholder' => __( 'download', 'download-monitor' ),
								'label'       => __( 'Download Endpoint', 'download-monitor' ),
								'desc'        => sprintf( __( 'Define what endpoint should be used for download links. By default this will be <code>%s</code>.', 'download-monitor' ), home_url( '/download/' ) )
							),
							array(
								'name'    => 'dlm_download_endpoint_value',
								'std'     => 'ID',
								'label'   => __( 'Endpoint Value', 'download-monitor' ),
								'desc'    => sprintf( __( 'Define what unique value should be used on the end of your endpoint to identify the downloadable file. e.g. ID would give a link like <code>%s</code>', 'download-monitor' ), home_url( '/download/10/' ) ),
								'type'    => 'select',
								'options' => array(
									'ID'   => __( 'Download ID', 'download-monitor' ),
									'slug' => __( 'Download slug', 'download-monitor' )
								)
							),
							array(
								'name'    => 'dlm_no_access_page',
								'std'     => '',
								'label'   => __( 'No Access Page', 'download-monitor' ),
								'desc'    => __( "Choose what page is displayed when the user has no access to a file. Don't forget to add the <code>[dlm_no_access]</code> shortcode to the page.", 'download-monitor' ),
								'type'    => 'lazy_select',
								'options' => array()
							),
						),
					),
					'access'     => array(
						'title'  => __( 'Access', 'download-monitor' ),
						'fields' => array(
							array(
								'name'        => 'dlm_no_access_error',
								'std'         => sprintf( __( 'You do not have permission to access this download. %sGo to homepage%s', 'download-monitor' ), '<a href="' . home_url() . '">', '</a>' ),
								'placeholder' => '',
								'label'       => __( 'No access message', 'download-monitor' ),
								'desc'        => __( "The message that will be displayed to visitors when they don't have access to a file.", 'download-monitor' ),
								'type'        => 'textarea'
							),
							array(
								'name'        => 'dlm_ip_blacklist',
								'std'         => '192.168.0.0/24',
								'label'       => __( 'Blacklist IPs', 'download-monitor' ),
								'desc'        => __( 'List IP Addresses to blacklist, 1 per line. Use IP/CIDR netmask format for ranges. IPv4 examples: <code>198.51.100.1</code> or <code>198.51.100.0/24</code>. IPv6 examples: <code>2001:db8::1</code> or <code>2001:db8::/32</code>.', 'download-monitor' ),
								'placeholder' => '',
								'type'        => 'textarea'
							),
							array(
								'name'        => 'dlm_user_agent_blacklist',
								'std'         => 'Googlebot',
								'label'       => __( 'Blacklist user agents', 'download-monitor' ),
								'desc'        => __( 'List browser user agents to blacklist, 1 per line.  Partial matches are sufficient. Regex matching is allowed by surrounding the pattern with forward slashes, e.g. <code>/^Mozilla.+Googlebot/</code>', 'download-monitor' ),
								'placeholder' => '',
								'type'        => 'textarea'
							),
						)
					),
					'hash'       => array(
						'title'  => __( 'Hashes', 'download-monitor' ),
						'fields' => array(
							array(
								'name' => 'dlm_hash_desc',
								'text' => sprintf( __( 'Hashes can optionally be output via shortcodes, but may cause performance issues with large files. %sYou can read more about hashes here%s', 'download-monitor' ), '<a href="https://www.download-monitor.com/kb/download-hashes/" target="_blank">', '</a>' ),
								'type' => 'desc'
							),
							array(
								'name'     => 'dlm_generate_hash_md5',
								'std'      => '0',
								'label'    => __( 'MD5 hashes', 'download-monitor' ),
								'cb_label' => __( 'Generate MD5 hash for uploaded files', 'download-monitor' ),
								'desc'     => '',
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_generate_hash_sha1',
								'std'      => '0',
								'label'    => __( 'SHA1 hashes', 'download-monitor' ),
								'cb_label' => __( 'Generate SHA1 hash for uploaded files', 'download-monitor' ),
								'desc'     => '',
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_generate_hash_sha256',
								'std'      => '0',
								'label'    => __( 'SHA256 hashes', 'download-monitor' ),
								'cb_label' => __( 'Generate SHA256 hash for uploaded files', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_generate_hash_crc32b',
								'std'      => '0',
								'label'    => __( 'CRC32B hashes', 'download-monitor' ),
								'cb_label' => __( 'Generate CRC32B hash for uploaded files', 'download-monitor' ),
								'type'     => 'checkbox'
							)
						)
					),
					'logging'    => array(
						'title'  => __( 'Logging', 'download-monitor' ),
						'fields' => array(
							array(
								'name'     => 'dlm_enable_logging',
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'std'      => '1',
								'label'    => __( 'Download Log', 'download-monitor' ),
								'desc'     => __( 'Log download attempts, IP addresses and more.', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'    => 'dlm_logging_ip_type',
								'std'     => '',
								'label'   => __( 'IP Address Logging', 'download-monitor' ),
								'desc'    => __( 'Define if and how you like to store IP addresses of users that download your files in your logs.', 'download-monitor' ),
								'type'    => 'select',
								'options' => array(
									'full'       => __( 'Store full IP address', 'download-monitor' ),
									'anonymized' => __( 'Store anonymized IP address (remove last 3 digits)', 'download-monitor' ),
									'none'       => __( 'Store no IP address', 'download-monitor' )
								)
							),
							array(
								'name'     => 'dlm_logging_ua',
								'std'      => '1',
								'label'    => __( 'User Agent Logging', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => __( 'If enabled, the user agent (browser) the user uses to download the file will be stored in your logs.', 'download-monitor' ),
								'type'     => 'checkbox'
							),
							array(
								'name'     => 'dlm_count_unique_ips',
								'std'      => '',
								'label'    => __( 'Count unique IPs only', 'download-monitor' ),
								'cb_label' => __( 'Enable', 'download-monitor' ),
								'desc'     => sprintf( __( 'If enabled, the counter for each download will only increment and create a log entry once per IP address. Note that this option only works if %s is set to %s.', 'download-monitor' ), '<strong>' . __( 'IP Address Logging', 'download-monitor' ) . '</strong>', '<strong>' . __( 'Store full IP address', 'download-monitor' ) . '</strong>' ),
								'type'     => 'checkbox'
							),
						)
					),
					'misc' => array(
						'title' => __( 'Miscellaneous', 'download-monitor' ),
						'fields' => array(
							array(
								'name'  => 'dlm_clear_transients',
								'std'   => '0',
								'label' => __( 'Clear all transients', 'download-monitor' ),
								'desc'  => __( 'Remove all Download Monitor transients, this can solve version caching issues.', 'download-monitor' ),
								'type'  => 'action_button',
								'link'  => self::get_url() . '&tab=advanced&section=misc'
							),
						),
					)
				)
			),
			'lead_generation'      => array(
				'title'    => esc_html__( 'Lead Generation', 'download-monitor' ),
				'sections' => array()
			),
			'external_hosting'     => array(
				'title'    => esc_html__( 'External Hosting', 'download-monitor' ),
				'sections' => array()
			),
			'integration'          => array(
				'title'    => esc_html__( 'Integration', 'download-monitor' ),
				'sections' => array()
			),
			'email_notification'   => array(
				'title'    => esc_html__( 'Emails', 'download-monitor' ),
				'sections' => array()
			),
			'terns_and_conditions' => array(
				'title'    => esc_html__( 'Terms and Conditions', 'download-monitor' ),
				'sections' => array()
			)
		);

		if ( dlm_is_shop_enabled() ) {

			$settings['general']['sections']['shop'] = array(
				'title'  => __( 'Shop', 'download-monitor' ),
				'fields' => array(
					array(
						'name'    => 'dlm_base_country',
						'std'     => 'US',
						'label'   => __( 'Base Country', 'download-monitor' ),
						'desc'    => __( 'Where is your store located?', 'download-monitor' ),
						'type'    => 'select',
						'options' => Services::get()->service( "country" )->get_countries()
					),
					array(
						'name'    => 'dlm_currency',
						'std'     => 'USD',
						'label'   => __( 'Currency', 'download-monitor' ),
						'desc'    => __( 'In what currency are you selling?', 'download-monitor' ),
						'type'    => 'select',
						'options' => $this->get_currency_list_with_symbols()
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
							'right_space' => sprintf( __( 'Right with space (%s)', 'download-monitor' ), Services::get()->service( 'format' )->money( 9.99, array( 'currency_position' => 'right_space' ) ) )
						)
					),
					array(
						'name'  => 'dlm_decimal_separator',
						'type'  => 'text',
						'std'   => '.',
						'label' => __( 'Decimal Separator', 'download-monitor' ),
						'desc'  => __( 'The decimal separator of displayed prices.', 'download-monitor' )
					),
					array(
						'name'  => 'dlm_thousand_separator',
						'type'  => 'text',
						'std'   => ',',
						'label' => __( 'Thousand Separator', 'download-monitor' ),
						'desc'  => __( 'The thousand separator of displayed prices.', 'download-monitor' )
					),
					array(
						'name'     => 'dlm_disable_cart',
						'std'      => '',
						'label'    => __( 'Disable Cart', 'download-monitor' ),
						'cb_label' => __( 'Disable', 'download-monitor' ),
						'desc'     => __( 'If checked, your customers will be send to your checkout page directly.', 'download-monitor' ),
						'type'     => 'checkbox'
					),
				)
			);

			$settings['advanced']['sections']['page_setup']['fields'][] = array(
				'name'    => 'dlm_page_cart',
				'std'     => '',
				'label'   => __( 'Cart page', 'download-monitor' ),
				'desc'    => __( 'Your cart page, make sure it has the <code>[dlm_cart]</code> shortcode.', 'download-monitor' ),
				'type'    => 'lazy_select',
				'options' => array()
			);

			$settings['advanced']['sections']['page_setup']['fields'][] = array(
				'name'    => 'dlm_page_checkout',
				'std'     => '',
				'label'   => __( 'Checkout page', 'download-monitor' ),
				'desc'    => __( 'Your checkout page, make sure it has the <code>[dlm_checkout]</code> shortcode.', 'download-monitor' ),
				'type'    => 'lazy_select',
				'options' => array()
			);

			$settings['payments'] = array(
				'title'    => __( 'Payment Methods', 'download-monitor' ),
				'sections' => $this->get_payment_methods_sections()
			);
		}

		// this is here to maintain backwards compatibility, use 'dlm_settings' instead
		$old_settings = apply_filters( 'download_monitor_settings', array() );

		// This is the correct filter
		$settings = apply_filters( 'dlm_settings', $settings );

		// Backwards compatibility for 4.3 and 4.4.4
		$settings = $this->backwards_compatibility_settings( $old_settings, $settings );

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
	 * @param array $options
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
			'terns_and_conditions',
			'twitter_lock',
			'page_addon'
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
							'title' => esc_html__( 'Emails', 'download-monitor' )
						);

					}

					if ( 'terns_and_conditions' == $tab_key ) {
						$tab_parent = 'terns_and_conditions';
						$tab_title  = true;
					}

					if ( isset( $tab[0] ) && ! $tab_title ) {

						$settings[ $tab_parent ]['sections'][ $tab_section ] = array(
							'title'    => $tab[0],
							'sections' => array()
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
		$pages = array( array( 'key' => 0, 'lbl' => __( 'Select Page', 'download-monitor' ) ) );

		// get pages from db
		$db_pages = get_pages();

		// check and loop
		if ( count( $db_pages ) > 0 ) {
			foreach ( $db_pages as $db_page ) {
				$pages[] = array( 'key' => $db_page->ID, 'lbl' => $db_page->post_title );
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

		/** @var \Never5\DownloadMonitor\Shop\Helper\Currency $currency_helper */
		$currency_helper = Services::get()->service( "currency" );

		$currencies = $currency_helper->get_available_currencies();

		//get_currency_symbol

		if ( ! empty( $currencies ) ) {
			foreach ( $currencies as $k => $v ) {
				$currencies[ $k ] = $v . " (" . $currency_helper->get_currency_symbol( $k ) . ")";
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
				'title'  => __( 'Overview', 'download-monitor' ),
				'fields' => array(
					array(
						'name'     => '',
						'std'      => 'USD',
						'label'    => __( 'Enabled Gateways', 'download-monitor' ),
						'desc'     => __( 'Check all payment methods you want to enable on your webshop.', 'download-monitor' ),
						'type'     => 'gateway_overview',
						'gateways' => $gateways
					),
					array(
						'name'    => 'dlm_default_gateway',
						'std'     => 'paypal',
						'label'   => __( 'Default Gateway', 'download-monitor' ),
						'desc'    => __( 'This payment method will be pre-selected on your checkout page.', 'download-monitor' ),
						'type'    => 'select',
						'options' => $gateways_formatted
					),
				)
			)
		);

		/** Generate sections for all gateways */
		if ( ! empty( $gateways ) ) {
			/** @var \Never5\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway $gateway */
			foreach ( $gateways as $gateway ) {

				// all gateways have an 'enabled' option by default
				$fields = array(
					array(
						'name'     => 'dlm_gateway_' . esc_attr( $gateway->get_id() ) . '_enabled',
						'std'      => '0',
						'label'    => __( 'Enabled', 'download-monitor' ),
						'cb_label' => __( 'Enable Gateway', 'download-monitor' ),
						'desc'     => __( 'Check this to allow your customers to use this payment method to pay at your checkout page.', 'download-monitor' ),
						'type'     => 'checkbox'
					)
				);

				$gateway_settings = $gateway->get_settings();
				if ( ! empty( $gateway_settings ) ) {
					$escaped_id = esc_attr( $gateway->get_id() );
					foreach ( $gateway_settings as $gw ) {
						$prefixed_field = $gw;

						$prefixed_field['name'] = 'dlm_gateway_' . $escaped_id . '_' . $prefixed_field['name'];

						$fields[] = $prefixed_field;
					}

				}

				//dlm_gateway_paypal_

				$sections[ $gateway->get_id() ] = array(
					'title'  => $gateway->get_title(),
					'fields' => $fields
				);
			}
		}

		return $sections;
	}

}