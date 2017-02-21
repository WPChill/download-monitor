<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WP_DLM class.
 *
 * Main plugin class
 */
class WP_DLM {

	/**
	 * Get the plugin file
	 *
	 * @static
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return DLM_PLUGIN_FILE;
	}

	/**
	 * Get plugin path
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_plugin_path() {
		return plugin_dir_path( self::get_plugin_file() );
	}

	/**
	 * Get plugin URL
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function get_plugin_url() {
		return plugins_url( basename( plugin_dir_path( self::get_plugin_file() ) ), basename( self::get_plugin_file() ) );
	}

	/**
	 * A static method that will setup the autoloader
	 */
	private static function setup_autoloader() {
		require_once( plugin_dir_path( self::get_plugin_file() ) . 'includes/class-dlm-autoloader.php' );
		$autoloader = new DLM_Autoloader( plugin_dir_path( self::get_plugin_file() ) . 'includes/' );
		spl_autoload_register( array( $autoloader, 'load' ) );
	}

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		global $wpdb;

		// Setup autoloader
		self::setup_autoloader();

		// Load plugin text domain
		load_textdomain( 'download-monitor', WP_LANG_DIR . '/download-monitor/download_monitor-' . get_locale() . '.mo' );
		load_plugin_textdomain( 'download-monitor', false, dirname( plugin_basename( DLM_PLUGIN_FILE ) ) . '/languages' );

		// Table for logs
		$wpdb->download_log = $wpdb->prefix . 'download_log';

		// Setup admin classes
		if ( is_admin() ) {

			// check if multisite and needs to create DB table

			// Setup admin scripts
			$admin_scripts = new DLM_Admin_Scripts();
			$admin_scripts->setup();

			// Setup Main Admin Class
			$dlm_admin = new DLM_Admin();
			$dlm_admin->setup();

			// Customize Admin CPT views
			new DLM_Admin_CPT();

			// Admin Write Panels
			new DLM_Admin_Writepanels();

			// Admin Media Browser
			new DLM_Admin_Media_Browser();

			// Admin Media Insert
			new DLM_Admin_Media_Insert();

			// Upgrade Manager
			$upgrade_manager = new DLM_Upgrade_Manager();
			$upgrade_manager->setup();
		}

		// Setup AJAX handler if doing AJAX
		if ( defined( 'DOING_AJAX' ) ) {
			new DLM_Ajax_Handler();
		}

		// Functions
		include_once( 'download-functions.php' );

		// Deprecated
		include_once( 'deprecated.php' );

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

		// Setup actions
		$this->setup_actions();
	}

	/**
	 * Setup actions
	 */
	private function setup_actions() {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		add_action( 'init', array( $this, 'register_globals' ) );
		add_action( 'after_setup_theme', array( $this, 'compatibility' ), 20 );
		add_action( 'the_post', array( $this, 'setup_download_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// setup product manager
		DLM_Product_Manager::get()->setup();
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
			if ( is_array( $current_support[0] ) ) {
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
			'<a href="' . admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' ) . '">' . __( 'Settings', 'download-monitor' ) . '</a>',
			'<a href="https://www.download-monitor.com/extensions/">' . __( 'Extensions', 'download-monitor' ) . '</a>',
			'<a href="https://github.com/download-monitor/download-monitor/wiki">' . __( 'Docs', 'download-monitor' ) . '</a>',
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
		wp_enqueue_style( 'dlm-frontend', self::get_plugin_url() . '/assets/css/frontend.css' );
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
	 * When the_post is called, get product data too
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

		$GLOBALS['dlm_download'] = new DLM_Download( $post->ID );
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

		return self::get_plugin_url();
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

		return self::get_plugin_path();
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
	 * @return void
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

		// File Manger
		$file_manager = new DLM_File_Manager();

		// Return files
		return $file_manager->get_file_hashes( $file_path );
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

}