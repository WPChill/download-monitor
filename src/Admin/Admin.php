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

		// Dashboard
		add_action( 'wp_dashboard_setup', array( $this, 'admin_dashboard' ) );
		// Admin Footer Text
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		// flush rewrite rules on shutdown
		add_action( 'shutdown', array( $this, 'maybe_flush_rewrites' ) );
		// filter attachment thumbnails in media library for files in dlm_uploads
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'filter_thumbnails_protected_files_grid' ), 10, 1 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'filter_thumbnails_protected_files_list' ), 10, 1 );
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
		// Do not make sub-sizes for images uploaded in dlm_uploads
		add_filter( 'file_is_displayable_image', array( $this, 'no_image_subsizes' ), 15, 2 );
		add_filter( 'ajax_query_attachments_args', array( $this, 'no_media_library_display' ), 15 );
		// Add a Media Library filter to list view so that we can filter out dlm_uploads
		add_action( 'restrict_manage_posts', array( $this, 'add_dlm_uploads_filter' ), 15, 2 );
		// Set query vars for dlm_uploads filter
		add_action( 'pre_get_posts', array( $this, 'media_library_filter' ), 15 );
		// Add DLM Uploads file as a mime type
		add_filter( 'post_mime_types', array( $this, 'add_mime_types' ), 15, 1 );
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
	 * filter attachment thumbnails in media library grid view for files in dlm_uploads
	 *
	 * @param array $response
	 *
	 * @return array
	 */
	public function filter_thumbnails_protected_files_grid( $response ) {
		if ( apply_filters( 'dlm_filter_thumbnails_protected_files', true ) ) {
			$upload_dir = wp_upload_dir();

			if ( strpos( $response['url'], $upload_dir['baseurl'] . '/dlm_uploads' ) !== false ) {
				if ( ! empty( $response['sizes'] ) ) {
					$dlm_protected_thumb = download_monitor()->get_plugin_url() . '/assets/images/protected-file-thumbnail.png';
					foreach ( $response['sizes'] as $rs_key => $rs_val ) {
						$rs_val['url']                = $dlm_protected_thumb;
						$response['sizes'][ $rs_key ] = $rs_val;
					}
				}
			}
		}

		return $response;
	}

	/**
	 * filter attachment thumbnails in media library list view for files in dlm_uploads
	 *
	 * @param bool|array $image
	 *
	 * @return bool|array
	 */
	public function filter_thumbnails_protected_files_list( $image ) {
		if ( apply_filters( 'dlm_filter_thumbnails_protected_files', true ) ) {
			if ( $image ) {

				$upload_dir = wp_upload_dir();

				if ( strpos( $image[0], $upload_dir['baseurl'] . '/dlm_uploads' ) !== false ) {
					$image[0] = $dlm_protected_thumb = download_monitor()->get_plugin_url() . '/assets/images/protected-file-thumbnail.png';
					$image[1] = 60;
					$image[2] = 60;
				}
			}

		}

		return $image;
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;

		wp_enqueue_style( 'download_monitor_menu_css', download_monitor()->get_plugin_url() . '/assets/css/menu.min.css', array(), DLM_VERSION );

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

	/**
	 * Don't display or create sub-sizes for DLM uploads
	 *
	 * @param $result
	 * @param $path
	 *
	 * @return false|mixed
	 * @since 4.6.0
	 */
	public function no_image_subsizes( $result, $path ) {

		$upload_dir = wp_upload_dir();

		if ( strpos( $path, $upload_dir['basedir'] . '/dlm_uploads' ) !== false ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Don't display DLM Uploads in Media Library
	 *
	 * @param array $query Query parameters.
	 *
	 * @return array
	 * @since 4.6.0
	 */
	public function no_media_library_display( $query ) {

		//Check for the added temporary mime_type so that we can filter the Media Library contents
		if ( ! isset( $query['post_mime_type'] ) || 'dlm_uploads_files' !== $query['post_mime_type'] ) {
			if ( ! isset( $query['meta_query'] ) ) {
				$query['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => '_wp_attached_file',
						'compare' => 'NOT LIKE',
						'value'   => 'dlm_uploads'
					)
				);
			} else {
				$query['meta_query'][] = array(
					'key'     => '_wp_attached_file',
					'compare' => 'NOT LIKE',
					'value'   => 'dlm_uploads'
				);
			}
		} else {
			unset($query['post_mime_type']);
			$query['meta_key'] = '_wp_attached_file';
			$query['meta_query'][] = array(
				'key'     => '_wp_attached_file',
				'compare' => 'LIKE',
				'value'   => 'dlm_uploads'
			);
		}

		return $query;
	}

	/**
	 * Add Media Library filters for DLM Downloads
	 *
	 * @param $screen
	 * @param $which
	 *
	 * @return void
	 * @since 4.6.4
	 */
	public function add_dlm_uploads_filter( $screen, $which ) {
		// Add a filter to the Media Library page so that we can filter regular uploads and Download Monitor's uploads
		if ( $screen === 'attachment' ) {
			$views = apply_filters( 'dlm_media_views', array(
				'uploads_folder'     => __( 'Uploads folder', 'download-monitor' ),
				'dlm_uploads_folder' => __( 'Download Monitor', 'download-monitor' )
			) );

			$applied_filter = isset( $_GET['dlm_upload_folder_type'] ) ? sanitize_text_field( wp_unslash( $_GET['dlm_upload_folder_type'] ) ) : 'all';
			?>
			<select name="dlm_upload_folder_type">
				<?php
				foreach ( $views as $key => $view ) {
					echo '<option value="' . $key . '" ' . selected( $key, $applied_filter ) . '>' . $view . '</option>';
				}
				?>
			</select>
			<?php
		}
	}

	/**
	 * Filter the media library query to wether show DLM uploads or not
	 *
	 * @param $query
	 *
	 * @return void
	 * @since 4.6.4
	 */
	public function media_library_filter( $query ) {

		if ( ! is_admin() || false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin/upload.php' ) ) {
			return;
		}
		// If users views uploads folder then we don't need to show DLM uploads.
		$compare = 'NOT LIKE';
		// If user views the DLM Uploads folder then we need to show DLM uploads.
		if ( isset( $_GET['dlm_upload_folder_type'] ) && 'dlm_uploads_folder' === $_GET['dlm_upload_folder_type'] ) {
			$compare = 'LIKE';
		}
		// Set the meta query for the corresponding request.
		$query->set( 'meta_key', '_wp_attached_file' );
		$query->set( 'meta_query', array(
			'key'     => '_wp_attached_file',
			'compare' => $compare,
			'value'   => 'dlm_uploads'
		) );
	}

	/**
	 * Add temporary dlm_uploads_files mime type to help us filter the media library
	 *
	 * @param $mimes
	 *
	 * @return mixed
	 * @since 4.6.4
	 */
	public function add_mime_types($mimes){

		if ( ! function_exists( 'get_current_screen' ) ) {
			return $mimes;
		}

		$screen = get_current_screen();
		// If we are not on the Media Library page or editing the Download then we don't need to add the mime types.
		if ( ! is_admin() || ( 'upload' !== $screen->base && 'attachment' !== $screen->post_type && 'dlm_download' !== $screen->post_type ) ) {
			return $mimes;
		}

		// Create temp mime_type that will only be available on Media Library page and edit Download page.
		// We need this to proper filter the Media Library contents and show only DLM uploads or regular uploads.
		$mimes['dlm_uploads_files'] = array(
			'Download Monitor Files',
			'Manage DLM Files',
			array(
				'dlm_uploads',
				'else',
				'singular' => 'DLM File',
				'plural'   => 'DLM Files',
				'content'  => null,
				'domain'   => null
			)
		);

		return $mimes;
	}
}
