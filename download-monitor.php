<?php
/*
Plugin Name: Download Monitor
Plugin URI: http://mikejolley.com/projects/download-monitor/
Description: A full solution for managing downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
Version: 1.5.1
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.8
Tested up to: 3.9

	Copyright: © 2014 Mike Jolley.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_DLM class.
 *
 * Main Class which inits the CPT and plugin
 */
class WP_DLM {

	private $plugin_url;
	private $plugin_path;
	private $_inline_js;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		// Define constants
		define( 'DLM_VERSION', '1.5.0' );

		// Table for logs
		$wpdb->download_log = $wpdb->prefix . 'download_log';

		// Include required files
		if ( is_admin() ) {
			include_once( 'includes/admin/class-dlm-admin.php' );
		}

		if ( defined('DOING_AJAX') ) {
			include_once( 'includes/class-dlm-ajax-handler.php' );
		}

		if ( get_option( 'dlm_enable_logging' ) == 1 ) {
			include_once( 'includes/class-dlm-logging.php' );
		}

		include_once( 'includes/download-functions.php' );
		include_once( 'includes/class-dlm-download.php' );
		include_once( 'includes/class-dlm-download-version.php' );
		include_once( 'includes/class-dlm-download-handler.php' );
		include_once( 'includes/class-dlm-shortcodes.php' );

		// Activation
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'init_user_roles' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'init_taxonomy' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'install_tables' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'directory_protection' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $GLOBALS['DLM_Download_Handler'], 'add_endpoint' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), 'flush_rewrite_rules', 11 );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'register_globals' ) );
		add_action( 'init', array( $this, 'init_taxonomy' ) );
		add_action( 'after_setup_theme', array( $this, 'compatibility' ) );
		add_action( 'the_post', array( $this, 'setup_download_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'wp_footer', array( $this, 'output_inline_js' ), 25 );
		add_action( 'admin_footer', array( $this, 'output_inline_js' ), 25 );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Add links to admin plugins page.
	 * @param  array $links
	 * @return array
	 */
	public function plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings' ) . '">' . __( 'Settings', 'download-monitor' ) . '</a>',
			'<a href="http://mikejolley.com/projects/download-monitor/add-ons/">' . __( 'Add-ons', 'download-monitor' ) . '</a>',
			'<a href="https://github.com/mikejolley/download-monitor/wiki">' . __( 'Docs', 'download-monitor' ) . '</a>',
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
		wp_enqueue_style( 'dlm-frontend', $this->plugin_url() . '/assets/css/frontend.css' );
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
	 * @param mixed $post
	 * @return void
	 */
	public function setup_download_data( $post ) {
		if ( is_int( $post ) )
			$post = get_post( $post );

		if ( $post->post_type !== 'dlm_download' )
			return;

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
			if( ! empty( $wpdb->charset ) )
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			if( ! empty( $wpdb->collate ) )
				$collate .= " COLLATE $wpdb->collate";
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

		if ( class_exists('WP_Roles') && ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();

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

		if ( post_type_exists( "dlm_download" ) )
			return;
		/**
		 * Taxonomies
		 */
		register_taxonomy( 'dlm_download_category',
	        array( 'dlm_download' ),
	        apply_filters( 'dlm_download_category_args', array(
	            'hierarchical' 			=> true,
	            'update_count_callback' => '_update_post_term_count',
	            'label' 				=> __( 'Categories', 'download-monitor'),
	            'labels' => array(
	                    'name' 				=> __( 'Categories', 'download-monitor'),
	                    'singular_name' 	=> __( 'Download Category', 'download-monitor'),
	                    'search_items' 		=> __( 'Search Download Categories', 'download-monitor'),
	                    'all_items' 		=> __( 'All Download Categories', 'download-monitor'),
	                    'parent_item' 		=> __( 'Parent Download Category', 'download-monitor'),
	                    'parent_item_colon' => __( 'Parent Download Category:', 'download-monitor'),
	                    'edit_item' 		=> __( 'Edit Download Category', 'download-monitor'),
	                    'update_item' 		=> __( 'Update Download Category', 'download-monitor'),
	                    'add_new_item' 		=> __( 'Add New Download Category', 'download-monitor'),
	                    'new_item_name' 	=> __( 'New Download Category Name', 'download-monitor')
	            	),
	            'show_ui' 				=> true,
	            'query_var' 			=> true,
	            'capabilities'			=> array(
	            	'manage_terms' 		=> 'manage_downloads',
	            	'edit_terms' 		=> 'manage_downloads',
	            	'delete_terms' 		=> 'manage_downloads',
	            	'assign_terms' 		=> 'manage_downloads',
	            ),
	            'rewrite' 				=> false,
	            'show_in_nav_menus'     => false
	        ) )
	    );

		register_taxonomy( 'dlm_download_tag',
	        array( 'dlm_download' ),
	        apply_filters( 'dlm_download_tag_args', array(
	            'hierarchical' 			=> false,
	            'label' 				=> __( 'Tags', 'download-monitor'),
	            'labels' => array(
	                    'name' 				=> __( 'Tags', 'download-monitor'),
	                    'singular_name' 	=> __( 'Download Tag', 'download-monitor'),
	                    'search_items' 		=> __( 'Search Download Tags', 'download-monitor'),
	                    'all_items' 		=> __( 'All Download Tags', 'download-monitor'),
	                    'parent_item' 		=> __( 'Parent Download Tag', 'download-monitor'),
	                    'parent_item_colon' => __( 'Parent Download Tag:', 'download-monitor'),
	                    'edit_item' 		=> __( 'Edit Download Tag', 'download-monitor'),
	                    'update_item' 		=> __( 'Update Download Tag', 'download-monitor'),
	                    'add_new_item' 		=> __( 'Add New Download Tag', 'download-monitor'),
	                    'new_item_name' 	=> __( 'New Download Tag Name', 'download-monitor')
	            	),
	            'show_ui' 				=> true,
	            'query_var' 			=> true,
	            'capabilities'			=> array(
	            	'manage_terms' 		=> 'manage_downloads',
	            	'edit_terms' 		=> 'manage_downloads',
	            	'delete_terms' 		=> 'manage_downloads',
	            	'assign_terms' 		=> 'manage_downloads',
	            ),
	            'rewrite' 				=> false,
	            'show_in_nav_menus'     => false
	        ) )
	    );

	    /**
		 * Post Types
		 */
		register_post_type( "dlm_download",
			apply_filters( 'dlm_cpt_dlm_download_args', array(
				'labels' => array(
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
				'description' => __( 'This is where you can create and manage downloads for your site.', 'download-monitor' ),
				'public' 				=> false,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'capabilities' => array(
					'publish_posts' 		=> 'manage_downloads',
					'edit_posts' 			=> 'manage_downloads',
					'edit_others_posts' 	=> 'manage_downloads',
					'delete_posts' 			=> 'manage_downloads',
					'delete_others_posts'	=> 'manage_downloads',
					'read_private_posts'	=> 'manage_downloads',
					'edit_post' 			=> 'manage_downloads',
					'delete_post' 			=> 'manage_downloads',
					'read_post' 			=> 'manage_downloads'
				),
				'publicly_queryable' 	=> false,
				'exclude_from_search' 	=> true,
				'hierarchical' 			=> false,
				'rewrite' 				=> false,
				'query_var' 			=> false,
				'supports' 				=> apply_filters( 'dlm_cpt_dlm_download_supports', array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ) ),
				'has_archive' 			=> false,
				'show_in_nav_menus' 	=> false
			) )
		);

		register_post_type( "dlm_download_version",
			apply_filters( 'dlm_cpt_dlm_download_version_args', array(
				'public' 				=> false,
				'show_ui' 				=> false,
				'publicly_queryable' 	=> false,
				'exclude_from_search' 	=> true,
				'hierarchical' 			=> false,
				'rewrite' 				=> false,
				'query_var'				=> false,
				'show_in_nav_menus' 	=> false
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
	 * get_template_part function.
	 *
	 * @access public
	 * @param mixed $slug
	 * @param string $name (default: '')
	 * @return void
	 */
	public function get_template_part( $slug, $name = '', $custom_dir = '' ) {
		$template = '';

		// Look in yourtheme/slug-name.php and yourtheme/download-monitor/slug-name.php
		if ( $name )
			$template = locate_template( array ( "{$slug}-{$name}.php", "download-monitor/{$slug}-{$name}.php" ) );

		// Get default slug-name.php
		if ( ! $template && $name && file_exists( $this->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
			$template = $this->plugin_path() . "/templates/{$slug}-{$name}.php";

		// If a custom path was defined, check that next
		if ( ! $template && $custom_dir && file_exists( trailingslashit( $custom_dir ) . "{$slug}-{$name}.php" ) )
			$template = trailingslashit( $custom_dir ) . "{$slug}-{$name}.php";

		// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/download-monitor/slug.php
		if ( ! $template )
			$template = locate_template( array( "{$slug}.php", "download-monitor/{$slug}.php" ) );

		// If a custom path was defined, check that next
		if ( ! $template && $custom_dir && file_exists( trailingslashit( $custom_dir ) . "{$slug}-{$name}.php" ) )
			$template = trailingslashit( $custom_dir ) . "{$slug}.php";

		// Get default slug-name.php
		if ( ! $template && file_exists( $this->plugin_path() . "/templates/{$slug}.php" ) )
			$template = $this->plugin_path() . "/templates/{$slug}.php";

		if ( $template )
			load_template( $template, false );
	}

	/**
	 * Get the plugin url
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_url() {
		if ( $this->plugin_url )
			return $this->plugin_url;

		return $this->plugin_url = plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	/**
	 * Get the plugin path
	 *
	 * @access public
	 * @return string
	 */
	public function plugin_path() {
		if ( $this->plugin_path )
			return $this->plugin_path;

		return $this->plugin_path = plugin_dir_path( __FILE__ );
	}

	/**
	 * Enqueue JS to be added to the footer.
	 *
	 * @access public
	 * @param mixed $code
	 * @return void
	 */
	public function add_inline_js( $code ) {
		$this->_inline_js .= "\n" . $code . "\n";
	}

	/**
	 * Output enqueued JS
	 *
	 * @access public
	 * @return void
	 */
	public function output_inline_js() {
		if ( $this->_inline_js ) {
			echo "<!-- Download Monitor JavaScript-->\n<script type=\"text/javascript\">\njQuery(document).ready(function($) {";
			echo $this->_inline_js;
			echo "});\n</script>\n";
			$this->_inline_js = '';
		}
	}

	/**
	 * Returns a listing of all files in the specified folder and all subdirectories up to 100 levels deep.
	 * The depth of the recursiveness can be controlled by the $levels param.
	 *
	 * @access public
	 * @param string $folder (default: '')
	 * @return void
	 */
	function list_files( $folder = '' ) {
		if ( empty($folder) )
			return false;

		$files = array();
		if ( $dir = @opendir( $folder ) ) {
			while (($file = readdir( $dir ) ) !== false ) {
				if ( in_array($file, array('.', '..') ) )
					continue;
				if ( is_dir( $folder . '/' . $file ) ) {

					$files[] = array(
						'type' 	=> 'folder',
						'path'	=> $folder . '/' . $file
					);

				} else {

					$files[] = array(
						'type' 	=> 'file',
						'path'	=> $folder . '/' . $file
					);

				}
			}
		}
		@closedir( $dir );
		return $files;
	}

	/**
	 * Protect the upload dir on activation.
	 *
	 * @access public
	 * @return void
	 */
	public function directory_protection() {

		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir =  wp_upload_dir();

		$files = array(
			array(
				'base' 		=> $upload_dir['basedir'] . '/dlm_uploads',
				'file' 		=> '.htaccess',
				'content' 	=> 'deny from all'
			),
			array(
				'base' 		=> $upload_dir['basedir'] . '/dlm_uploads',
				'file' 		=> 'index.html',
				'content' 	=> ''
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
	 * Parse a file path and return the new path and whether or not it's remote
	 * @param  string $file_path
	 * @return array
	 */
	public function parse_file_path( $file_path ) {
		$remote_file      = true;
		$parsed_file_path = parse_url( $file_path );
		
		$wp_uploads = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		if ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ) ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {

			/** This is an absolute path */
			$remote_file  = false;

		} elseif( strpos( $file_path, $wp_uploads_url ) !== false ) {

			/** This is a local file given by URL so we need to figure out the path */
			$remote_file  = false;
			$file_path    = str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path );
			$file_path    = realpath( $file_path );

		} elseif( is_multisite() && ( strpos( $file_path, network_site_url( '/', 'http' ) ) !== false || strpos( $file_path, network_site_url( '/', 'https' ) ) !== false ) ) {

			/** This is a local file outside of wp-content so figure out the path */
			$remote_file = false;
			// Try to replace network url
            $file_path   = str_replace( network_site_url( '/', 'https' ), ABSPATH, $file_path );
            $file_path   = str_replace( network_site_url( '/', 'http' ), ABSPATH, $file_path );
            // Try to replace upload URL
            $file_path   = str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path );
            $file_path   = realpath( $file_path );

		} elseif( strpos( $file_path, site_url( '/', 'http' ) ) !== false || strpos( $file_path, site_url( '/', 'https' ) ) !== false ) {

			/** This is a local file outside of wp-content so figure out the path */
			$remote_file = false;
			$file_path   = str_replace( site_url( '/', 'https' ), ABSPATH, $file_path );
			$file_path   = str_replace( site_url( '/', 'http' ), ABSPATH, $file_path );
			$file_path   = realpath( $file_path );

		} elseif ( file_exists( ABSPATH . $file_path ) ) {
			
			/** Path needs an abspath to work */
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;
			$file_path   = realpath( $file_path );
		}

		return array( $file_path, $remote_file );
	}

	/**
	 * Gets the filesize of a path or URL.
	 *
	 * @access public
	 * @return string size on success, -1 on failure
	 */
	public function get_filesize( $file_path ) {
		if ( $file_path ) {
			list( $file_path, $remote_file ) = $this->parse_file_path( $file_path );

			if ( ! empty( $file_path ) ) {
				if ( $remote_file ) {
					$file = wp_remote_head( $file_path );

					if ( ! is_wp_error( $file ) && ! empty( $file['headers']['content-length'] ) ) {
						return $file['headers']['content-length'];
					}
				} else {
					if ( file_exists( $file_path ) && ( $filesize = filesize( $file_path ) ) ) {
						return $filesize;
					}
				}
			}
		}

		return -1;
	}

	/**
	 * Gets md5, sha1 and crc32 hashes for a file and store it.
	 *
	 * @access public
	 * @return array of sizes
	 */
	public function get_file_hashes( $file_path ) {
		$md5   = false;
		$sha1  = false;
		$crc32 = false;

		if ( $file_path ) {
			list( $file_path, $remote_file ) = $this->parse_file_path( $file_path );

			if ( ! empty( $file_path ) ) {
				if ( ! $remote_file || apply_filters( 'dlm_allow_remote_hash_file', false ) ) {
					if ( get_option( 'dlm_generate_hash_md5' ) ) {
						$md5   = hash_file( 'md5', $file_path );
					}
					if ( get_option( 'dlm_generate_hash_sha1' ) ) {
						$sha1  = hash_file( 'sha1', $file_path );
					}
					if ( get_option( 'dlm_generate_hash_crc32b' ) ) {
						$crc32 = hash_file( 'crc32b', $file_path );
					}
				}
			}
		}

		return array( 'md5' => $md5, 'sha1' => $sha1, 'crc32' => $crc32 );
	}

	/**
	 * Encode files for storage
	 * @param  array $files
	 * @return string
	 */
	public function json_encode_files( $files ) {
		if ( version_compare( phpversion(), "5.4.0", ">=" ) ) {
			$files = json_encode( $files, JSON_UNESCAPED_UNICODE );
		} else {
			$files = json_encode( $files );
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$files = preg_replace_callback( '/\\\\u([0-9a-f]{4})/i', array( $this, 'json_unscaped_unicode_fallback' ), $files );
			}
		}
		return $files;
	}

	/**
	 * Fallback for PHP < 5.4 where JSON_UNESCAPED_UNICODE does not exist.
	 * @param  array $matches
	 * @return string
	 */
	public function json_unscaped_unicode_fallback( $matches ) {
		$sym = mb_convert_encoding(
			pack( 'H*', $matches[1] ),
			'UTF-8',
			'UTF-16'
		);
		return $sym;
	}
}

/**
 * Init download_monitor class
 */
$GLOBALS['download_monitor'] = new WP_DLM();