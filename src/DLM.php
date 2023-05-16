<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use \WPChill\DownloadMonitor\Util;

/**
 * WP_DLM class.
 *
 * Main plugin class
 */
class WP_DLM {

	private $services = null;

	/**
	 * Get the plugin file
	 *
	 * @return String
	 */
	public function get_plugin_file() {
		return DLM_PLUGIN_FILE;
	}

	/**
	 * Get plugin path
	 *
	 * @return string
	 */
	public function get_plugin_path() {
		return plugin_dir_path( $this->get_plugin_file() );
	}

	/**
	 * Get plugin URL
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return plugins_url( basename( plugin_dir_path( $this->get_plugin_file() ) ), basename( $this->get_plugin_file() ) );
	}

	/**
	 * Return requested service
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function service( $key ) {
		return $this->services->get( $key );
	}

	/**
	 * __construct function.
	 *z
	 * @access public
	 */
	public function __construct() {
		global $wpdb;

		// Setup Services
		$this->services = new DLM_Services();

		// Load plugin text domain.
		$this->load_textdomain();

		// Table for Download Infos.
		$wpdb->download_log = "{$wpdb->prefix}download_log";
		// New Table for reports.
		$wpdb->dlm_reports = "{$wpdb->prefix}dlm_reports_log";
		// New Table for individual Downloads.
		$wpdb->dlm_downloads = "{$wpdb->prefix}dlm_downloads";

		// Setup admin classes.
		if ( is_admin() ) {

			// check if multisite and needs to create DB table

			// Setup admin scripts
			$admin_scripts = new DLM_Admin_Scripts();
			$admin_scripts->setup();

			// Setup Main Admin Class
			$dlm_admin = new DLM_Admin();
			$dlm_admin->setup();

			// setup custom labels
			$custom_labels = new DLM_Custom_Labels();
			$custom_labels->setup();

			// setup custom columns
			$custom_columns = new DLM_Custom_Columns();
			$custom_columns->setup();

			// setup custom actions
			$custom_actions = new DLM_Custom_Actions();
			$custom_actions->setup();

			// Admin Write Panels
			new DLM_Admin_Writepanels();

			// Admin Media Browser
			new DLM_Admin_Media_Browser();

			// Admin Media Insert
			new DLM_Admin_Media_Insert();

			// Upgrade Manager
			$upgrade_manager = new DLM_Upgrade_Manager();
			$upgrade_manager->setup();

			// DLM Welcome page
			DLM_Welcome_Page::get_instance();

			// Legacy Upgrader
			$lu_page = new DLM_LU_Page();
			$lu_page->setup();

			$lu_ajax = new DLM_LU_Ajax();
			$lu_ajax->setup();

			// Onboarding
			// Delete this after new welcome banner.
		/*	$onboarding = new Util\Onboarding();
			$onboarding->setup();*/

			// Admin Download Page Options Upsells
			new DLM_Admin_OptionsUpsells();

			if( class_exists('DLM_Download_Duplicator') ) {
				deactivate_plugins( 'dlm-download-duplicator/dlm-download-duplicator.php' );
			}

			// The beta testers class
			/*if ( defined( 'DLM_BETA' ) && DLM_BETA && class_exists( 'DLM_Beta_Testers') ) {
				new DLM_Beta_Testers();
			}*/

			new DLM_Review();
			// Show the shop discontinued notice.
			add_action( 'admin_notices', array( $this, 'shop_discontinued_notice' ), 8 );
		}

		// Set the DB Upgrader class to see if we need to upgrade the table or not.
		// This is mainly to move to version 4.6.x from 4.5.x and below.
		$upgrader = DLM_DB_Upgrader::get_instance();

		// Set Reports. We set them here in order to also create the REST Api calls.
		$reports = DLM_Reports::get_instance();

		// Setup AJAX handler if doing AJAX
		if ( defined( 'DOING_AJAX' ) ) {
			new DLM_Ajax_Handler();
		}

		// Setup new AJAX handler
		$ajax_manager = new DLM_Ajax_Manager();
		$ajax_manager->setup();

		// Functions
		require_once( $this->get_plugin_path() . 'includes/download-functions.php' );

		// Deprecated
		require_once( $this->get_plugin_path() . 'includes/deprecated.php' );

		// Setup DLM Download Handler
		$download_handler = new DLM_Download_Handler();
		$download_handler->setup();

		// setup no access page endpoints
		$no_access_page_endpoint = new DLM_Download_No_Access_Page_Endpoint();
		$no_access_page_endpoint->setup();

		// Setup shortcodes
		$dlm_shortcodes = new DLM_Shortcodes();
		$dlm_shortcodes->setup();

		// Setup Widgets
		$widget_manager = new DLM_Widget_Manager();
		$widget_manager->setup();

		// Setup Taxonomies
		$taxonomy_manager = new DLM_Taxonomy_Manager();
		$taxonomy_manager->setup();

		// Setup Post Types
		$post_type_manager = new DLM_Post_Type_Manager();
		$post_type_manager->setup();

		// Setup Log Filters
		$log_filters = new DLM_Log_Filters();
		$log_filters->setup();

		// Setup actions
		$this->setup_actions();

		// Setup Search support
		$search = new DLM_Search();
		$search->setup();

		// Setup Gutenberg
		$gutenberg = new DLM_Gutenberg();
		$gutenberg->setup();

		// Setup Gutenberg Download Preview
		$gb_download_preview = new DLM_DownloadPreview_Preview();
		$gb_download_preview->setup();

		// Backwards Compatibility.
		$dlm_backwards_compatibility = DLM_Backwards_Compatibility::get_instance();

		// Setup integrations
		$this->setup_integrations();

		// check if we need to bootstrap E-Commerce
		if ( apply_filters( 'dlm_shop_load_bootstrap', true ) ) {
			require_once( $this->get_plugin_path() . 'src/Shop/bootstrap.php' );
		}

		// Fix to whitelist our function for PolyLang.
		add_filter( 'pll_home_url_white_list', array( $this, 'whitelist_polylang' ), 15, 1 );
		// Generate attachment URL as Download link for protected files. Adding this here because we need it both in admin and in front.
		add_filter( 'wp_get_attachment_url', array( $this, 'generate_attachment_url' ), 15, 2 );
	}

	/**
	 * Load Textdomain
	 *
	 * @since 4.7.72
	 */
	private function load_textdomain() {
		$dlm_lang = dirname( DLM_FILE ) . '/languages/';

		if ( get_user_locale() !== get_locale() ) {

			unload_textdomain( 'download-monitor' );
			$locale = apply_filters( 'plugin_locale', get_user_locale(), 'download-monitor' );

			$lang_ext  = sprintf( '%1$s-%2$s.mo', 'download-monitor', $locale );
			$lang_ext1 = WP_LANG_DIR . "/download-monitor/download-monitor-{$locale}.mo";
			$lang_ext2 = WP_LANG_DIR . "/plugins/download-monitor/{$lang_ext}";

			if ( file_exists( $lang_ext1 ) ) {
				load_textdomain( 'download-monitor', $lang_ext1 );

			} elseif ( file_exists( $lang_ext2 ) ) {
				load_textdomain( 'download-monitor', $lang_ext2 );

			} else {
				load_plugin_textdomain( 'download-monitor', false, $dlm_lang );
			}
		} else {
			load_plugin_textdomain( 'download-monitor', false, $dlm_lang );
		}
	}

	/**
	 * Setup actions
	 */
	private function setup_actions() {

		add_filter( 'plugin_action_links_' . plugin_basename( DLM_PLUGIN_FILE ), array( $this, 'plugin_links' ) );
		add_action( 'init', array( $this, 'register_globals' ) );
		add_action( 'after_setup_theme', array( $this, 'compatibility' ), 20 );
		add_action( 'the_post', array( $this, 'setup_download_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// Get the correct download link in archive pages or when retrieving download permalink
		add_filter( 'post_type_link', array( $this, 'archive_filter_download_link' ), 20, 2 );

		// setup product manager
		DLM_Product_Manager::get()->setup();
	}

	/**
	 * Setup 3rd party integrations
	 */
	private function setup_integrations() {
		$yoast = new DLM_Integrations_YoastSEO();
		$yoast->setup();

		$post_types_order = new DLM_Integrations_PostTypesOrder();
		$post_types_order->setup();
	}

	/**
	 * Add Theme Compatibility
	 *
	 * @access public
	 * @return void
	 */
	public function compatibility() {

		// Post thumbnail support
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
			remove_post_type_support( 'post', 'thumbnail' );
			remove_post_type_support( 'page', 'thumbnail' );
		} else {

			// Get current supported
			$current_support = get_theme_support( 'post-thumbnails' );

			// fix current support for some themes
			if ( is_array( $current_support ) && is_array( $current_support[0] ) ) {
				$current_support = $current_support[0];
			}

			// This can be a bool or array. If array we merge our post type in, if bool ignore because it's like a global theme setting.
			if ( is_array( $current_support ) ) {
				add_theme_support( 'post-thumbnails', array_merge( $current_support, array( 'dlm_download' ) ) );
			}

			add_post_type_support( 'download', 'thumbnail' );
		}
	}

	/**
	 * Add links to admin plugins page.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . DLM_Admin_Settings::get_url() . '">' . __( 'Settings', 'download-monitor' ) . '</a>'
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		if ( apply_filters( 'dlm_frontend_scripts', true ) ) {
			wp_register_style( 'dlm-frontend', $this->get_plugin_url() . '/assets/css/frontend.min.css', array(), DLM_VERSION );
		}

		// only enqueue preview stylesheet when we're in the preview.
		if ( isset( $_GET['dlm_gutenberg_download_preview'] ) ) {
			// Enqueue admin css
			wp_enqueue_style(
				'dlm_preview',
				plugins_url( '/assets/css/preview.min.css', $this->get_plugin_file() ),
				array(),
				DLM_VERSION
			);
		}

		// Leave this filter here in case XHR is problematic and needs to be disabled.
		if ( self::do_xhr() ) {
			wp_enqueue_script(
				'dlm-xhr',
				plugins_url( '/assets/js/dlm-xhr' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', $this->get_plugin_file() ),
				array('jquery'),
				DLM_VERSION, true
			);

			// Add dashicons on the front if popup modal for no access is used.
			if ( '1' === get_option( 'dlm_no_access_modal', 0 ) ) {
				wp_enqueue_style( 'dashicons' );
			}
			// @todo: delete the xhr_links attribute in the future as it will not be needed. It's only here for backwards
			// compatibility as extensions might using it. Used prior to 4.7.72.
			$dlm_xhr_data = apply_filters(
				'dlm_xhr_data',
				array(
					'xhr_links'          => array(
						'class' => array(
							'download-link',
							'download-button'
						)
					),
					'prevent_duplicates' => '1' === get_option( 'dlm_enable_window_logging' )
				)
			);

			$dlm_xhr_security_data = array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			);

			$xhr_data = array_merge( $dlm_xhr_data, $dlm_xhr_security_data );
			// Let's create the URL pointer for the download link. It will be used as a global variable in the xhr.js file
			// and will be used to check if is a true download request or not.
			$scheme            = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
			$endpoint          = get_option( 'dlm_download_endpoint' );
			$endpoint          = $endpoint ? $endpoint : 'download';
			$wpml_options      = get_option( 'icl_sitepress_settings', false );
			$is_dlm_translated = false;
			if ( $wpml_options && isset( $wpml_options['custom_posts_sync_option'] ) && in_array( 'dlm_download', $wpml_options['custom_posts_sync_option'] ) ) {
				$is_dlm_translated = true;
			}

			if ( $is_dlm_translated ) {
				add_filter( 'wpml_get_home_url', array( 'DLM_Utils', 'wpml_download_link' ), 15, 2 );
			}

			if ( get_option( 'permalink_structure' ) ) {
				// Fix for translation plugins that modify the home_url.
				$download_pointing_url = get_home_url( null, '', $scheme );
				$download_pointing_url = $download_pointing_url . '/' . $endpoint . '/';
			} else {
				$download_pointing_url = add_query_arg( $endpoint, '', home_url( '', $scheme ) );
			}

			// Now we can remove the filter as the link is generated.
			//@todo: If Downloads will be made translatable in the future then this should be removed.
			if ( $is_dlm_translated ) {
				remove_filter( 'wpml_get_home_url', array( 'DLM_Utils', 'wpml_download_link' ), 15, 2 );
			}

			wp_add_inline_script( 'dlm-xhr', 'const dlmXHR = ' . json_encode( $xhr_data ) . '; dlmXHRinstance = {}; const dlmXHRGlobalLinks = "' . esc_url( $download_pointing_url ) . '"; dlmXHRgif = "' . esc_url( includes_url( '/images/spinner.gif' ) ) .'"', 'before' );
			wp_localize_script( 'dlm-xhr', 'dlmXHRtranslations', array(
				'error' => __( 'An error occurred while trying to download the file. Please try again.', 'download-monitor' )
			) );
		}

		do_action( 'dlm_frontend_scripts_after' );

	}

	/**
	 * Register environment globals
	 *
	 * @access private
	 * @return void
	 */
	public function register_globals() {
		$GLOBALS['dlm_download'] = null;
	}

	/**
	 * When the_post is called, get download data too
	 *
	 * @access public
	 *
	 * @param mixed $post
	 *
	 * @return void
	 */
	public function setup_download_data( $post ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( $post->post_type !== 'dlm_download' ) {
			return;
		}

		try {
			$download                = $this->service( 'download_repository' )->retrieve_single( $post->ID );
			$GLOBALS['dlm_download'] = $download;
		} catch ( Exception $e ) {

		}
	}

	/** Deprecated methods **************************************************/

	/**
	 * get_template_part function.
	 *
	 * @deprecated 1.6.0
	 *
	 * @access public
	 *
	 * @param mixed $slug
	 * @param string $name (default: '')
	 *
	 * @return void
	 */
	public function get_template_part( $slug, $name = '', $custom_dir = '' ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// Load template part
		$template_handler = new DLM_Template_Handler();
		$template_handler->get_template_part( $slug, $name, $custom_dir );
	}

	/**
	 * Get the plugin url
	 *
	 * @deprecated 1.6.0
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		return $this->get_plugin_url();
	}

	/**
	 * Get the plugin path
	 *
	 * @deprecated 1.6.0
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		return $this->get_plugin_path();
	}

	/**
	 * Returns a listing of all files in the specified folder and all subdirectories up to 100 levels deep.
	 * The depth of the recursiveness can be controlled by the $levels param.
	 *
	 * @deprecated 1.6.0
	 *
	 * @access public
	 *
	 * @param string $folder (default: '')
	 *
	 * @return array|bool
	 */
	public function list_files( $folder = '' ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->list_files( $folder );
	}

	/**
	 * Parse a file path and return the new path and whether or not it's remote
	 *
	 * @deprecated 1.6.0
	 *
	 * @param  string $file_path
	 *
	 * @return array
	 */
	public function parse_file_path( $file_path ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->parse_file_path( $file_path );
	}

	/**
	 * Gets the filesize of a path or URL.
	 *
	 * @deprecated 1.6.0
	 *
	 * @param string $file_path
	 *
	 * @access public
	 * @return string size on success, -1 on failure
	 */
	public function get_filesize( $file_path ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->get_file_size( $file_path );
	}

	/**
	 * Gets md5, sha1 and crc32 hashes for a file and store it.
	 *
	 * @deprecated 1.6.0
	 *
	 * @string $file_path
	 *
	 * @access public
	 * @return array of sizes
	 */
	public function get_file_hashes( $file_path ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// Return files
		return $this->service( 'hasher' )->get_file_hashes( $file_path );
	}

	/**
	 * Encode files for storage
	 *
	 * @deprecated 1.6.0
	 *
	 * @param  array $files
	 *
	 * @return string
	 */
	public function json_encode_files( $files ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->json_encode_files( $files );
	}

	/**
	 * Fallback for PHP < 5.4 where JSON_UNESCAPED_UNICODE does not exist.
	 *
	 * @deprecated 1.6.0
	 *
	 * @param  array $matches
	 *
	 * @return string
	 */
	public function json_unscaped_unicode_fallback( $matches ) {

		// Deprecated
		DLM_Debug_Logger::deprecated( __METHOD__ );

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->json_unscaped_unicode_fallback( $matches );
	}

	/**
	 * Filter the download link so that it retrieves the corresponding download link in archive pages
	 *
	 * @param $post_link
	 * @param $post
	 *
	 * @return mixed|String
	 *
	 * @since 4.4.5
	 */
	public function archive_filter_download_link( $post_link, $post ) {

		// We exclude the search because there is a specific option for this
		if ( 'dlm_download' == $post->post_type && ! is_search() ) {
			if ( ! isset( $GLOBALS['dlm_download'] ) ) {
				// fetch download object
				try {
					/** @var DLM_Download $download */
					$download                = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );
					$GLOBALS['dlm_download'] = $download;

					return $download->get_the_download_link();
				} catch ( Exception $e ) {
				}
			} else {
				return $GLOBALS['dlm_download']->get_the_download_link();
			}
		}

		return $post_link;
	}

	/**
	 * Whitelist  class DLM_Download's method get_the_download_link
	 */
	public function whitelist_polylang( $list ) {

		$download = new DLM_Download();
		// We add our download link to polylang's whitelist functions, to be able to retrieve the language in the link
		$list[] = array('function' => 'get_the_download_link' );

		return $list;
	}

	/**
	 * Check if we can do XHR download
	 *
	 * @return mixed|null
	 * @since 4.6.0
	 */
	public static function do_xhr() {
		return apply_filters( 'dlm_do_xhr', true );
	}

	/**
	 * Return the Download Link for a given file if that file was protected by DLM
	 *
	 * @param $url
	 * @param $attachment_id
	 *
	 * @return mixed|String
	 * @since 4.7.2
	 */
	public function generate_attachment_url( $url, $attachment_id ) {
		// Get the Download ID, if exists
		$known_download = get_post_meta( $attachment_id, 'dlm_download', true );
		$protected      = get_post_meta( $attachment_id, 'dlm_protected_file', true );
		// If it doesn't exist, return the original URL
		if ( empty( $known_download ) || '1' !== $protected ) {
			return $url;
		}

		$known_download = json_decode( $known_download, true );
		$download_id    = isset( $known_download['download_id'] ) ? $known_download['download_id'] : false;
		$version_id     = isset( $known_download['version_id'] ) ? $known_download['version_id'] : false;

		if ( ! $download_id ) {
			return $url;
		}

		$download = false;
		// Try to find our Download
		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $download_id ) );
		} catch ( Exception $exception ) {
			return $url;
		}

		$url = $download->get_the_download_link();
		// Set version also to the URL as the user might add another version to that Download that could download another file
		if ( $version_id ) {
			$url = add_query_arg( 'v', $version_id, $url );
		}

		// Return our Download Link instead of the original URL
		return $url;
	}

	/**
	 * Display shop discontinued notice.
	 *
	 * @return void
	 * @since 4.7.76
	 */
	public function shop_discontinued_notice() {

		if ( ! dlm_is_shop_enabled() || 0 != get_option( 'dlm_hide_notice-shop_disabled', 0 ) ) {
			return;
		}

		?>
		<div class="notice notice-error is-dismissible dlm-notice" id="shop_disabled" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dlm_hide_notice-shop_disabled' ) );?>" style="margin-top:30px;">
			<p><?php echo wp_kses_post( __( '<strong>Attention!</strong> Download Monitor shop functionality will no longer be supported begining <strong>April 2023</strong> and will be disabled in a future update.', 'download-monitor' ) ); ?></p>
		</div>
		<?php
	}
}
