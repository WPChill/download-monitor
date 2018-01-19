<?php

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
		foreach ( $settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) ) {
					add_option( $option['name'], $option['std'] );
				}
				register_setting( 'download-monitor', $option['name'] );
			}
		}

		// register option for tab navigation :: 'dlm_settings_tab_saved'
		add_option( 'dlm_settings_tab_saved', 'general' );
		register_setting( 'download-monitor', 'dlm_settings_tab_saved' );

	}

	/**
	 * Method that return all Download Monitor Settings
	 *
	 * @access public
	 * @return array
	 */
	public function get_settings() {

		return apply_filters( 'download_monitor_settings',
			array(
				'general'   => array(
					__( 'General', 'download-monitor' ),
					array(
						array(
							'name'    => 'dlm_default_template',
							'std'     => '',
							'label'   => __( 'Default Template', 'download-monitor' ),
							'desc'    => __( 'Choose which template is used for <code>[download]</code> shortcodes by default (this can be overridden by the <code>format</code> argument).', 'download-monitor' ),
							'type'    => 'select',
							'options' => array(
								''             => __( 'Default - Title and count', 'download-monitor' ),
								'button'       => __( 'Button - CSS styled button showing title and count', 'download-monitor' ),
								'box'          => __( 'Box - Box showing thumbnail, title, count, filename and filesize.', 'download-monitor' ),
								'filename'     => __( 'Filename - Filename and download count', 'download-monitor' ),
								'title'        => __( 'Title - Shows download title only', 'download-monitor' ),
								'version-list' => __( 'Version list - Lists all download versions in an unordered list', 'download-monitor' ),
								'custom'       => __( 'Custom template', 'download-monitor' )
							)
						),
						array(
							'name'  => 'dlm_custom_template',
							'type'  => 'text',
							'std'   => '',
							'label' => __( 'Custom Template', 'download-monitor' ),
							'desc'  => __( 'Leaving this blank will use the default <code>content-download.php</code> template file. If you enter, for example, <code>button</code>, the <code>content-download-button.php</code> template will be used instead. You can add custom templates inside your theme folder.', 'download-monitor' )
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
					),
				),
				'endpoints' => array(
					__( 'Endpoint', 'download-monitor' ),
					array(
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
						)
					)
				),
				'hash'      => array(
					__( 'Hashes', 'download-monitor' ),
					array(
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
							'desc'     => __( 'Hashes can optionally be output via shortcodes, but may cause performance issues with large files.', 'download-monitor' ),
							'type'     => 'checkbox'
						),
						array(
							'name'     => 'dlm_generate_hash_crc32b',
							'std'      => '0',
							'label'    => __( 'CRC32B hashes', 'download-monitor' ),
							'cb_label' => __( 'Generate CRC32B hash for uploaded files', 'download-monitor' ),
							'desc'     => __( 'Hashes can optionally be output via shortcodes, but may cause performance issues with large files.', 'download-monitor' ),
							'type'     => 'checkbox'
						)
					)
				),
				'logging'   => array(
					__( 'Logging', 'download-monitor' ),
					array(
						array(
							'name'     => 'dlm_enable_logging',
							'cb_label' => __( 'Enable', 'download-monitor' ),
							'std'      => '1',
							'label'    => __( 'Download Log', 'download-monitor' ),
							'desc'     => __( 'Log download attempts, IP addresses and more.', 'download-monitor' ),
							'type'     => 'checkbox'
						),
						array(
							'name'     => 'dlm_count_unique_ips',
							'std'      => '',
							'label'    => __( 'Count unique IPs only', 'download-monitor' ),
							'cb_label' => __( 'Enable', 'download-monitor' ),
							'desc'     => __( 'If enabled, the counter for each download will only increment and create a log entry once per IP address.', 'download-monitor' ),
							'type'     => 'checkbox'
						),
					)
				),
				'access'    => array(
					__( 'Access', 'download-monitor' ),
					array(
						array(
							'name'    => 'dlm_no_access_page',
							'std'     => '',
							'label'   => __( 'No Access Page', 'download-monitor' ),
							'desc'    => __( "Choose what page is displayed when the user has no access to a file. Don't forget to add the <code>[dlm_no_access]</code> shortcode to the page.", 'download-monitor' ),
							'type'    => 'lazy_select',
							'options' => array()
						),
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
				'misc'      => array(
					__( 'Misc', 'download-monitor' ),
					array(
						array(
							'name'     => 'dlm_clean_on_uninstall',
							'std'      => '0',
							'label'    => __( 'Remove Data on Uninstall?', 'download-monitor' ),
							'cb_label' => __( 'Enable', 'download-monitor' ),
							'desc'     => __( 'Check this box if you would like to completely remove all Download Monitor data when the plugin is deleted.', 'download-monitor' ),
							'type'     => 'checkbox'
						),
						array(
							'name'  => 'dlm_clear_transients',
							'std'   => '0',
							'label' => __( 'Clear all transients', 'download-monitor' ),
							'desc'  => __( 'Remove all Download Monitor transients, this can solve version caching issues.', 'download-monitor' ),
							'type'  => 'action_button',
							'link'  => self::get_url() . '#settings-misc'
						),
					),
				)
			)
		);
	}

	/**
	 * Register lazy load setting fields callbacks
	 */
	public function register_lazy_load_callbacks() {
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

}