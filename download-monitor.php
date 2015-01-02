<?php
/*
	Plugin Name: Download Monitor
	Plugin URI: https://www.download-monitor.com
	Description: A full solution for managing downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
	Version: 1.6.0-beta1
	Author: Barry Kooij & Mike Jolley
	Author URI: http://www.barrykooij.com
	Requires at least: 3.8
	Tested up to: 4.0.1

	License: GPL v3

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WP_DLM class.
 *
 * Main Class which inits the CPT and plugin
 */
class WP_DLM {

	private $_inline_js;

	/**
	 * Get the plugin file
	 *
	 * @static
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
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

		// Define constants
		define( 'DLM_VERSION', '1.6.0-beta1' );

		// Setup autoloader
		self::setup_autoloader();

		// Table for logs
		$wpdb->download_log = $wpdb->prefix . 'download_log';

		// Setup admin classes
		if ( is_admin() ) {

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
		}

		// Setup AJAX handler if doing AJAX
		if ( defined( 'DOING_AJAX' ) ) {
			new DLM_Ajax_Handler();
		}

		// Functions
		include_once( 'includes/download-functions.php' );

		// Setup DLM Download Handler
		$download_handler = new DLM_Download_Handler();
		$download_handler->setup();

		// Setup shortcodes
		$dlm_shortcodes = new DLM_Shortcodes();
		$dlm_shortcodes->setup();

		/**
		 * @todo move all activation triggers to separate fle
		 */

		// Activation
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$this,
			'init_user_roles'
		), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$this,
			'init_taxonomy'
		), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$this,
			'install_tables'
		), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$this,
			'directory_protection'
		), 10 );

		// @todo Remove use of GLOBAL
		$dlm_download_handler = new DLM_Download_Handler();
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array(
			$dlm_download_handler,
			'add_endpoint'
		), 10 );

		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), 'flush_rewrite_rules', 11 );

		// Setup actions
		$this->setup_actions();
	}

	/**
	 * Setup actions
	 *
	 * @todo See what really needs to be in the main class
	 */
	private function setup_actions() {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'register_globals' ) );
		add_action( 'init', array( $this, 'init_taxonomy' ) );
		add_action( 'after_setup_theme', array( $this, 'compatibility' ) );
		add_action( 'the_post', array( $this, 'setup_download_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
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
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_textdomain( 'download-monitor', WP_LANG_DIR . '/download-monitor/download_monitor-' . get_locale() . '.mo' );
		load_plugin_textdomain( 'download-monitor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
			add_theme_support( 'post-thumbnails', array( 'dlm_download' ) );
			add_post_type_support( 'download', 'thumbnail' );
		}
	}

	/**
	 * install_tables function.
	 *
	 * @access public
	 * @return void
	 */
	public function install_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$dlm_tables = "
	CREATE TABLE {$wpdb->download_log} (
	  ID bigint(20) NOT NULL auto_increment,
	  type varchar(200) NOT NULL default 'download',
	  user_id bigint(20) NOT NULL,
	  user_ip varchar(200) NOT NULL,
	  user_agent varchar(200) NOT NULL,
	  download_id bigint(20) NOT NULL,
	  version_id bigint(20) NOT NULL,
	  version varchar(200) NOT NULL,
	  download_date datetime NOT NULL default '0000-00-00 00:00:00',
	  download_status varchar(200) NULL,
	  download_status_message varchar(200) NULL,
	  PRIMARY KEY  (ID),
	  KEY attribute_name (download_id)
	) $collate;
	";
		dbDelta( $dlm_tables );
	}

	/**
	 * Init user roles
	 *
	 * @access public
	 * @return void
	 */
	public function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'administrator', 'manage_downloads' );
		}
	}

	/**
	 * Init taxonomies
	 *
	 * @access public
	 * @return void
	 */
	public function init_taxonomy() {

		if ( post_type_exists( "dlm_download" ) ) {
			return;
		}
		/**
		 * Taxonomies
		 */
		register_taxonomy( 'dlm_download_category',
			array( 'dlm_download' ),
			apply_filters( 'dlm_download_category_args', array(
				'hierarchical'          => true,
				'update_count_callback' => '_update_post_term_count',
				'label'                 => __( 'Categories', 'download-monitor' ),
				'labels'                => array(
					'name'              => __( 'Categories', 'download-monitor' ),
					'singular_name'     => __( 'Download Category', 'download-monitor' ),
					'search_items'      => __( 'Search Download Categories', 'download-monitor' ),
					'all_items'         => __( 'All Download Categories', 'download-monitor' ),
					'parent_item'       => __( 'Parent Download Category', 'download-monitor' ),
					'parent_item_colon' => __( 'Parent Download Category:', 'download-monitor' ),
					'edit_item'         => __( 'Edit Download Category', 'download-monitor' ),
					'update_item'       => __( 'Update Download Category', 'download-monitor' ),
					'add_new_item'      => __( 'Add New Download Category', 'download-monitor' ),
					'new_item_name'     => __( 'New Download Category Name', 'download-monitor' )
				),
				'show_ui'               => true,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_downloads',
					'edit_terms'   => 'manage_downloads',
					'delete_terms' => 'manage_downloads',
					'assign_terms' => 'manage_downloads',
				),
				'rewrite'               => false,
				'show_in_nav_menus'     => false
			) )
		);

		register_taxonomy( 'dlm_download_tag',
			array( 'dlm_download' ),
			apply_filters( 'dlm_download_tag_args', array(
				'hierarchical'      => false,
				'label'             => __( 'Tags', 'download-monitor' ),
				'labels'            => array(
					'name'              => __( 'Tags', 'download-monitor' ),
					'singular_name'     => __( 'Download Tag', 'download-monitor' ),
					'search_items'      => __( 'Search Download Tags', 'download-monitor' ),
					'all_items'         => __( 'All Download Tags', 'download-monitor' ),
					'parent_item'       => __( 'Parent Download Tag', 'download-monitor' ),
					'parent_item_colon' => __( 'Parent Download Tag:', 'download-monitor' ),
					'edit_item'         => __( 'Edit Download Tag', 'download-monitor' ),
					'update_item'       => __( 'Update Download Tag', 'download-monitor' ),
					'add_new_item'      => __( 'Add New Download Tag', 'download-monitor' ),
					'new_item_name'     => __( 'New Download Tag Name', 'download-monitor' )
				),
				'show_ui'           => true,
				'query_var'         => true,
				'capabilities'      => array(
					'manage_terms' => 'manage_downloads',
					'edit_terms'   => 'manage_downloads',
					'delete_terms' => 'manage_downloads',
					'assign_terms' => 'manage_downloads',
				),
				'rewrite'           => false,
				'show_in_nav_menus' => false
			) )
		);

		/**
		 * Post Types
		 */
		register_post_type( "dlm_download",
			apply_filters( 'dlm_cpt_dlm_download_args', array(
				'labels'              => array(
					'all_items'          => __( 'All Downloads', 'download-monitor' ),
					'name'               => __( 'Downloads', 'download-monitor' ),
					'singular_name'      => __( 'Download', 'download-monitor' ),
					'add_new'            => __( 'Add New', 'download-monitor' ),
					'add_new_item'       => __( 'Add Download', 'download-monitor' ),
					'edit'               => __( 'Edit', 'download-monitor' ),
					'edit_item'          => __( 'Edit Download', 'download-monitor' ),
					'new_item'           => __( 'New Download', 'download-monitor' ),
					'view'               => __( 'View Download', 'download-monitor' ),
					'view_item'          => __( 'View Download', 'download-monitor' ),
					'search_items'       => __( 'Search Downloads', 'download-monitor' ),
					'not_found'          => __( 'No Downloads found', 'download-monitor' ),
					'not_found_in_trash' => __( 'No Downloads found in trash', 'download-monitor' ),
					'parent'             => __( 'Parent Download', 'download-monitor' )
				),
				'description'         => __( 'This is where you can create and manage downloads for your site.', 'download-monitor' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'publish_posts'       => 'manage_downloads',
					'edit_posts'          => 'manage_downloads',
					'edit_others_posts'   => 'manage_downloads',
					'delete_posts'        => 'manage_downloads',
					'delete_others_posts' => 'manage_downloads',
					'read_private_posts'  => 'manage_downloads',
					'edit_post'           => 'manage_downloads',
					'delete_post'         => 'manage_downloads',
					'read_post'           => 'manage_downloads'
				),
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => apply_filters( 'dlm_cpt_dlm_download_supports', array(
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'custom-fields'
				) ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false
			) )
		);

		register_post_type( "dlm_download_version",
			apply_filters( 'dlm_cpt_dlm_download_version_args', array(
				'public'              => false,
				'show_ui'             => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_nav_menus'   => false
			) )
		);
	}

	/**
	 * register_widgets function.
	 *
	 * @access public
	 * @return void
	 */
	function register_widgets() {
		include_once( 'includes/widgets/class-dlm-widget-downloads.php' );

		register_widget( 'DLM_Widget_Downloads' );
	}

	/** Helper functions *****************************************************/

	/**
	 * Protect the upload dir on activation.
	 *
	 * @access public
	 * @return void
	 */
	public function directory_protection() {

		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => '.htaccess',
				'content' => 'deny from all'
			),
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => 'index.html',
				'content' => ''
			)
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 *
	 * Deprecated methods below
	 *
	 */

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

function __download_monitor_main() {
	$dlm = new WP_DLM();

	// Backwards compatibility
	$GLOBALS['download_monitor'] = $dlm;
}


add_action( 'plugins_loaded', '__download_monitor_main', 10 );