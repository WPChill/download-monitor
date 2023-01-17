<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin class.
 *
 */
class DLM_Admin {

	/**
	 * Variable indicating if rewrites need a flush
	 *
	 * @var bool
	 */
	private $need_rewrite_flush = false;

	/**
	 * Setup actions etc.
	 */
	public function setup() {

		// Directory protection
		add_filter( 'mod_rewrite_rules', array( $this, 'ms_files_protection' ) );
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'init', array( $this, 'required_classes' ), 30 );

		// Remove admin notices from DLM pages
		add_action( 'admin_notices', array(  $this, 'remove_admin_notices' ), 9 );

		// Admin menus
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );

		// setup settings
		$settings = new DLM_Admin_Settings();
		add_action( 'admin_init', array( $settings, 'register_settings' ) );

		$settings->register_lazy_load_callbacks();

		// setup settings page
		$settings_page = new DLM_Settings_Page();
		$settings_page->setup();

		// setup report
		$reports_page = new DLM_Reports_Page();
		$reports_page->setup();

		// Handle all functinality that involves Media Library
		$dlm_media_library = DLM_Media_Library::get_instance();

		// Dashboard
		add_action( 'wp_dashboard_setup', array( $this, 'admin_dashboard' ) );
		// Admin Footer Text
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		// flush rewrite rules on shutdown
		add_action( 'shutdown', array( $this, 'maybe_flush_rewrites' ) );

		// Legacy Upgrader
		$lu_check = new DLM_LU_Checker();
		if ( $lu_check->needs_upgrading() ) {
			$lu_message = new DLM_LU_Message();
			$lu_message->display();
		}
		// Sets the rewrite rule option if dlm_download_endpoint option is changed.
		add_filter( 'pre_update_option_dlm_download_endpoint', array( $this, 'set_rewrite_rules_flag_on_endpoint_change'), 15, 2 );
		// Checks and flushes rewrite rule if rewrite flag option is set.
		add_action( 'admin_init', array( $this, 'check_rewrite_rules') );

	}

	/**
	 * ms_files_protection function.
	 *
	 * @access public
	 *
	 * @param mixed $rewrite
	 *
	 * @return string
	 */
	public function ms_files_protection( $rewrite ) {

		if ( ! is_multisite() ) {
			return $rewrite;
		}

		$rule = "\n# DLM Rules - Protect Files from ms-files.php\n\n";
		$rule .= "<IfModule mod_rewrite.c>\n";
		$rule .= "RewriteEngine On\n";
		$rule .= "RewriteCond %{QUERY_STRING} file=dlm_uploads/ [NC]\n";
		$rule .= "RewriteRule /ms-files.php$ - [F]\n";
		$rule .= "</IfModule>\n\n";

		return $rule . $rewrite;
	}

	/**
	 * upload_dir function.
	 *
	 * @access public
	 *
	 * @param mixed $pathdata
	 *
	 * @return array
	 */
	public function upload_dir( $pathdata ) {

		// We don't process form we just modify the upload path for our custom post type.
		// phpcs:ignore
		if ( isset( $_POST['type'] ) && 'dlm_download' === $_POST['type'] ) { 
			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/dlm_uploads';
				$pathdata['url']    = $pathdata['url'] . '/dlm_uploads';
				$pathdata['subdir'] = '/dlm_uploads';
			} else {
				$new_subdir = '/dlm_uploads' . $pathdata['subdir'];

				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
			}
		}

		return $pathdata;
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;

		wp_enqueue_style( 'download_monitor_others', download_monitor()->get_plugin_url() . '/assets/css/others.min.css', array(), DLM_VERSION );

		if ( $hook == 'index.php' ) {
			wp_enqueue_style( 'download_monitor_dashboard_css', download_monitor()->get_plugin_url() . '/assets/css/dashboard.min.css', array(), DLM_VERSION );
		}

		$enqueue = false;

		if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' || 'term.php' == $hook ) {
			if (
				( ! empty( $_GET['post_type'] ) && in_array( $_GET['post_type'], array(
						'dlm_download',
						\WPChill\DownloadMonitor\Shop\Util\PostType::KEY
					) ) )
				||
				( ! empty( $post->post_type ) && in_array( $post->post_type, array(
						'dlm_download',
						\WPChill\DownloadMonitor\Shop\Util\PostType::KEY
					) ) )
			) {
				$enqueue = true;
			}
		}

		if ( strstr( $hook, 'dlm_download_page' ) ) {
			$enqueue = true;
		}

		if ( 'edit-tags.php' == $hook && isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], array( 'dlm_download_category', 'dlm_download_tag' ) ) ) {
			$enqueue = true;
		}

		if ( isset( $_GET['page'] ) && 'download-monitor-orders' === $_GET['page'] ) {
			$enqueue = true;
		}

		if ( 'dlm_product' === get_current_screen()->id ) {
			$enqueue = true;
		}

		if ( ! $enqueue ) {
			return;
		}

		wp_enqueue_script( 'jquery-blockui', download_monitor()->get_plugin_url() . '/assets/js/blockui.min.js', array( 'jquery' ), '2.61' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-style', download_monitor()->get_plugin_url() . '/assets/css/jquery-ui.min.css', array(), DLM_VERSION );
		wp_enqueue_style( 'download_monitor_admin_css', download_monitor()->get_plugin_url() . '/assets/css/admin.min.css', array( 'dashicons' ), DLM_VERSION );
	}

	/**
	 * Add the admin menu on later hook so extensions can be add before this menu item
	 */
	public function admin_menu() {

		/**
		 * Hook for menu link
		 *
		 * @hooked DLM_Settings_Page add_settings_page() - 30
		 * @hooked DLM_Admin_Extensions extensions_pages() - 30
		 * @hooked DLM_Reports_Page add_admin_menu() - 30
		 * @hooked Orders orders_menu() - 30
		 *
		 */
		$links = apply_filters(
			'dlm_admin_menu_links',
			array()
		);

		uasort( $links, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );
		// Extensions page

		if ( ! empty( $links ) ) {
			foreach ( $links as $link ) {
				add_submenu_page( 'edit.php?post_type=dlm_download', $link['page_title'], $link['menu_title'], $link['capability'], $link['menu_slug'], $link['function'], $link['priority'] );
			}
		}

	}

	/**
	 * Load our classes
	 */
	public function required_classes() {

		// Loads the DLM Admin Extensions class
		// Add Extensions pages
		DLM_Admin_Extensions::get_instance();

		// Load the DLM Admin Helper class
		DLM_Admin_Helper::get_instance();

		// Load the DLM Uninstall class
		DLM_Uninstall::get_instance();

	}


	/**
	 * admin_dashboard function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_dashboard() {
		new DLM_Admin_Dashboard();
	}

	/**
	 * Change the admin footer text on Download Monitor admin pages
	 *
	 * @since  1.7
	 *
	 * @param  string $footer_text
	 *
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		$current_screen = get_current_screen();

		$dlm_page_ids = array(
			'edit-dlm_download',
			'dlm_download',
			'edit-dlm_download_category',
			'edit-dlm_download_tag',
			'dlm_download_page_download-monitor-logs',
			'dlm_download_page_download-monitor-settings',
			'dlm_download_page_download-monitor-reports',
			'dlm_download_page_dlm-extensions'
		);

		// Check to make sure we're on a Download Monitor admin page
		if ( isset( $current_screen->id ) && apply_filters( 'dlm_display_admin_footer_text', in_array( $current_screen->id, $dlm_page_ids ) ) ) {
			// Change the footer text
			$footer_text = sprintf( __( 'If you like %sDownload Monitor%s please leave us a %s★★★★★%s rating. A huge thank you from us in advance!', 'download-monitor' ), '<strong>', '</strong>', '<a href="https://wordpress.org/support/view/plugin-reviews/download-monitor?filter=5#postform" target="_blank">', '</a>' );
		}

		return $footer_text;
	}

	/**
	 * Maybe flush rewrite rules
	 */
	public function maybe_flush_rewrites() {
		if ( true == $this->need_rewrite_flush ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Check if endpoint has changed, and if so let's flush the rules
	 *
	 * 
	 * @since 4.5.6
	 */
	public function check_rewrite_rules(){
		$flush = get_transient( 'dlm_download_endpoints_rewrite' );
		if ( $flush ) {
			flush_rewrite_rules(false);
			delete_transient( 'dlm_download_endpoints_rewrite' );
		}
	}

	/**
	 * Check if the endpoint has changed and set a transient if so
	 *
	 * @param String $new_value The new value for dlm_download_endpoint
	 * @param String $old_value The old value of dlm_download_endpoint
	 * @return String
	 * 
	 * @since 4.5.6
	 */
	public function set_rewrite_rules_flag_on_endpoint_change( $new_value, $old_value ) {

		if ( $new_value === $old_value ) {
			return $new_value;
		}

		set_transient( 'dlm_download_endpoints_rewrite', true, HOUR_IN_SECONDS );
		return $new_value;
	}

	/**
	 * Remove all notices that are in DLM's pages
	 *
	 * @return void
	 * @since 4.5.95
	 */
	public function remove_admin_notices() {
		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && 'dlm_download' === $screen->post_type ) {
			remove_all_actions( 'admin_notices' );
		}

	}

}
