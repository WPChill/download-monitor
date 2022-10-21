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

		// Actions done to Media Library files in order to create Downloads and protect files
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_protect_button' ), 15, 2 );
		add_action( 'wp_ajax_dlm_protect_file', array( $this, 'protect_file' ), 15 );
		add_action( 'wp_ajax_dlm_unprotect_file', array( $this, 'unprotect_file' ), 15 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'add_visual_indicator' ), 10, 2 );
		add_filter( 'manage_upload_columns', array( $this, 'dlm_ml_column' ), 15, 1 );
		add_action( 'manage_media_custom_column', array( $this, 'manage_dlm_ml_column' ), 0, 2 );
		add_filter( 'bulk_actions-upload', array( $this, 'dlm_ml_bulk_actions' ), 15 );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'dlm_ml_handle_bulk' ),15 , 3 );
		add_filter( 'admin_init', array( $this, 'dlm_ml_do_bulk' ), 15 );
		// End Actions to Media Library files
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

	/**
	 * Add a Protect Download button in the Attachment details view
	 *
	 * @param $fields
	 * @param $post
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function add_protect_button( $fields, $post ) {
		// Let's check if this is not already set.
		if ( ! isset( $fields['dlm_protect_file'] ) ) {

			$button_text = __( 'Protect', 'download-monitor' );
			$action      = 'protect_file';
			if ( '1' === get_post_meta( $post->ID, 'dlm_protected_file', true) ) {
				$button_text = __( 'Unprotect', 'download-monitor' );
				$action = 'unprotect_file';
			}
			$html = '<button id="dlm-protect-file" class="button button-primary" data-action="' . esc_attr( $action ) . '" data-post_id="' . absint( $post->ID ) . '" data-nonce="' . wp_create_nonce( 'dlm_protect_file' ) . '" data-title="' . esc_attr( $post->title ) . '" data-user_id="' . get_current_user_id() . '" data-file="' . esc_url( wp_get_attachment_url( $post->ID ) ) . '" >' . esc_html( $button_text ) . '</button><p class="description">' . esc_html__( 'Creates a Download based on this file.', 'download-monitor' ) . '</p>';

			// Add our button
			$fields['dlm_protect_file'] = array(
				'label' => __( 'DLM protect file', 'download-monitor' ),
				'input' => 'html',
				'html'  => $html,

			);
		}

		return $fields;
	}

	/**
	 * Function used to create new Downloads directly from the Media Library
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function protect_file() {
		// Check if nonce is transmitted
		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( 'No nonce' );
		}
		// Check if nonce is correct
		check_ajax_referer( 'dlm_protect_file', '_ajax_nonce' );
		// Get the data so we can create the download
		$file = $_POST;
		// Move the file
		download_monitor()->service( 'file_manager' )->move_file_to_dlm_uploads( $file['attachment_id'] );
		// Create the download or update existing one
		$current_url = $this->create_download( $file );
		// Send the response
		$data = array(
			'url'  => $current_url,
			'text' => esc_html__( 'File protected. Download created', 'download-monitor' )
		);
		wp_send_json_success( $data );
	}

	/**
	 * Function used to unprotect Media Library file
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function unprotect_file() {
		// Check if nonce is transmitted
		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( 'No nonce' );
		}
		// Check if nonce is correct
		check_ajax_referer( 'dlm_protect_file', '_ajax_nonce' );
		// Get the data so we can create the download
		$file = $_POST;
		// For the moment we don't know the version id or if it exists
		$version_id = false;
		// Now make the move to Download Monitor's protected folder dlm_uploads
		download_monitor()->service( 'file_manager' )->move_file_back( $file['attachment_id'] );
		// Get the currently protected download so that we can update its files
		$known_download = get_post_meta( $file['attachment_id'], 'dlm_download', true );
		if ( ! empty( $known_download ) ) {
			$version_id    = json_decode( $known_download, true )['version_id'];
		}
		// Delete set metas when the file was protected.
		delete_post_meta( $file['attachment_id'], 'dlm_protected_file' );
		// Get current URL so we can update the Version files.
		$current_url = wp_get_attachment_url( $file['attachment_id'] );
		// Get secure path and update the file path in the Download
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $current_url, 'relative' );

		if ( $version_id ) {
			// Update the Version meta.
			update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
		}

		// Send the response
		$data = array(
			'url'  => $current_url,
			'text' => esc_html__( 'File unprotected.', 'download-monitor' )
		);

		wp_send_json_success( $data );
	}

	/**
	 * Create new Download and its version
	 *
	 * @param $file
	 *
	 * @return string URL of the new Download
	 * @since 4.7.2
	 */
	public function create_download( $file ) {

		// Get new path
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( wp_get_attachment_url( $file['attachment_id'] ), 'relative' );

		// Check if the file has been previously protected
		$known_download = get_post_meta( $file['attachment_id'], 'dlm_download', true );
		// If not, protect and add the corresponding meta, Download & Version
		if ( empty( $known_download ) ) {
			$download_title = ( empty( $file['title'] ) ) ? DLM_Utils::basename( $file['file'] ) : $file['title'];
			// Create the Download object.
			$download = array(
				'post_title'   => $download_title,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => absint( $file['user_id'] ),
				'post_type'    => 'dlm_download'
			);
			// Insert the Download. We need its ID to create the Download Version.
			$download_id = wp_insert_post( $download );
			// Create the Version object
			$version = array(
				'post_title'   => 'Download #' . $download_title . 'File Version',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => absint( $file['user_id'] ),
				'post_type'    => 'dlm_download_version',
				'post_parent'  => $download_id
			);
			// Insert the Version.
			$version_id = wp_insert_post( $version );
			// Update the Version meta.
			update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
			// Set a meta option to know what Download is using this file and what Version.
			$attachment_meta = json_encode(
				array(
					'download_id' => $download_id,
					'version_id'  => $version_id
				)
			);
			update_post_meta( $file['attachment_id'], 'dlm_download', $attachment_meta );
		} else { // Use the current Download and Version
			$download_id = json_decode( $known_download, true )['download_id'];
			$version_id  = json_decode( $known_download, true )['version_id'];
		}

		// Update the Version meta.
		update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
		update_post_meta( $version_id, '_version', '' );
		$transient_name   = 'dlm_file_version_ids_' . $download_id;
		$transient_name_2 = 'dlm_file_version_ids_' . $version_id;
		// Set a meta option to know that this file is protected by Download Monitor.
		update_post_meta( $file['attachment_id'], 'dlm_protected_file', '1' );
		// Update the file's URL with the Download Monitor's URL.
		// First we need to retrieve the newly created Download
		try {
			/** @var DLM_Download $download */
			$download   = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $download_id ) );
			$attachment = array(
				'ID' => $file['attachment_id'],
			);
			wp_update_post( $attachment );
			// Delete transient as it won't be able to find the versions if not.
			delete_transient( $transient_name );
			delete_transient( $transient_name_2 );

			$url = $download->get_the_download_link();
			// Set version also to the URL as the user might add another version to that Download that could download another file
			if ( $version_id ) {
				$url = add_query_arg( 'v', $version_id, $url );
			}

			return $url;

		} catch ( Exception $exception ) {
			// no download found, don't do anything.
		}

		return false;
	}

	/**
	 * Displays a visual indicator for Media Library files that are protected by DLM
	 *
	 * @param $response
	 * @param $attachment
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function add_visual_indicator( $response, $attachment ) {

		if ( '1' === get_post_meta( $attachment->ID, 'dlm_protected_file', true ) ) {
			$response['customClass'] = 'dlm-ml-protected-file';
		}

		return $response;
	}

	public function dlm_ml_column( $columns ) {
		$columns['dlm_protection'] = __( 'Download Monitor', 'download-monitor' );

		return $columns;
	}

	public function manage_dlm_ml_column( $column_name, $id ) {

		if ( $column_name == 'dlm_protection' ) {

			if ( '1' === get_post_meta( $id, 'dlm_protected_file', true ) ) {
				?>
				<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI4IDE0QzI4IDYuMjY4MDEgMjEuNzMyIDAgMTQgMEM2LjI2ODAxIDAgMCA2LjI2ODAxIDAgMTRDMCAyMS43MzIgNi4yNjgwMSAyOCAxNCAyOEMyMS43MzIgMjggMjggMjEuNzMyIDI4IDE0WiIgZmlsbD0idXJsKCNwYWludDBfbGluZWFyXzM2XzM5KSIvPgo8cGF0aCBkPSJNMTcuNjE1NCAxMi41NjI1SDE3LjM3NVY5LjUxNTYyQzE3LjM3NSA4LjU4MzIyIDE2Ljk5NTEgNy42ODkwMSAxNi4zMTg5IDcuMDI5N0MxNS42NDI3IDYuMzcwNCAxNC43MjU1IDYgMTMuNzY5MiA2QzEyLjgxMjkgNiAxMS44OTU4IDYuMzcwNCAxMS4yMTk2IDcuMDI5N0MxMC41NDM0IDcuNjg5MDEgMTAuMTYzNSA4LjU4MzIyIDEwLjE2MzUgOS41MTU2MlYxMi41NjI1SDkuOTIzMDhDOS40MTMwNSAxMi41NjI1IDguOTIzOSAxMi43NiA4LjU2MzI2IDEzLjExMTdDOC4yMDI2MSAxMy40NjMzIDggMTMuOTQwMiA4IDE0LjQzNzVWMTkuMTI1QzggMTkuNjIyMyA4LjIwMjYxIDIwLjA5OTIgOC41NjMyNiAyMC40NTA4QzguOTIzOSAyMC44MDI1IDkuNDEzMDUgMjEgOS45MjMwOCAyMUgxNy42MTU0QzE4LjEyNTQgMjEgMTguNjE0NiAyMC44MDI1IDE4Ljk3NTIgMjAuNDUwOEMxOS4zMzU5IDIwLjA5OTIgMTkuNTM4NSAxOS42MjIzIDE5LjUzODUgMTkuMTI1VjE0LjQzNzVDMTkuNTM4NSAxMy45NDAyIDE5LjMzNTkgMTMuNDYzMyAxOC45NzUyIDEzLjExMTdDMTguNjE0NiAxMi43NiAxOC4xMjU0IDEyLjU2MjUgMTcuNjE1NCAxMi41NjI1VjEyLjU2MjVaTTExLjEyNSA5LjUxNTYyQzExLjEyNSA4LjgzMTg2IDExLjQwMzYgOC4xNzYxMSAxMS44OTk1IDcuNjkyNjJDMTIuMzk1NCA3LjIwOTEyIDEzLjA2NzkgNi45Mzc1IDEzLjc2OTIgNi45Mzc1QzE0LjQ3MDUgNi45Mzc1IDE1LjE0MzEgNy4yMDkxMiAxNS42MzkgNy42OTI2MkMxNi4xMzQ5IDguMTc2MTEgMTYuNDEzNSA4LjgzMTg2IDE2LjQxMzUgOS41MTU2MlYxMi41NjI1SDExLjEyNVY5LjUxNTYyWk0xNC4yNSAxNy45NTMxQzE0LjI1IDE4LjA3NzQgMTQuMTk5MyAxOC4xOTY3IDE0LjEwOTIgMTguMjg0NkMxNC4wMTkgMTguMzcyNSAxMy44OTY3IDE4LjQyMTkgMTMuNzY5MiAxOC40MjE5QzEzLjY0MTcgMTguNDIxOSAxMy41MTk0IDE4LjM3MjUgMTMuNDI5MyAxOC4yODQ2QzEzLjMzOTEgMTguMTk2NyAxMy4yODg1IDE4LjA3NzQgMTMuMjg4NSAxNy45NTMxVjE1LjYwOTRDMTMuMjg4NSAxNS40ODUxIDEzLjMzOTEgMTUuMzY1OCAxMy40MjkzIDE1LjI3NzlDMTMuNTE5NCAxNS4xOSAxMy42NDE3IDE1LjE0MDYgMTMuNzY5MiAxNS4xNDA2QzEzLjg5NjcgMTUuMTQwNiAxNC4wMTkgMTUuMTkgMTQuMTA5MiAxNS4yNzc5QzE0LjE5OTMgMTUuMzY1OCAxNC4yNSAxNS40ODUxIDE0LjI1IDE1LjYwOTRWMTcuOTUzMVoiIGZpbGw9IndoaXRlIi8+CjxkZWZzPgo8bGluZWFyR3JhZGllbnQgaWQ9InBhaW50MF9saW5lYXJfMzZfMzkiIHgxPSItNy41NDY4NyIgeTE9Ii00LjM3NSIgeDI9IjI1LjU5MzciIHkyPSIyOC43NjU2IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CjxzdG9wIG9mZnNldD0iMC4xMTAxMTMiIHN0b3AtY29sb3I9IiM1RERFRkIiLz4KPHN0b3Agb2Zmc2V0PSIwLjQ0MzU2OCIgc3RvcC1jb2xvcj0iIzQxOUJDQSIvPgo8c3RvcCBvZmZzZXQ9IjAuNjM2MTIyIiBzdG9wLWNvbG9yPSIjMDA4Q0Q1Ii8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzAyNUVBMCIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo=">
				<?php
			} else {
				?>
				<span class="dashicons dashicons-no"
				      style="color:red"></span><?php echo esc_html__( 'Un-Protected', 'download-monitor' ) ?>
				<?php
			}

		}
	}

	/**
	 * Add bulk actions to Media Library table
	 *
	 * @param $bulk_actions
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function dlm_ml_bulk_actions( $bulk_actions ) {
		$bulk_actions['dlm_protect_files'] = __( 'Download Monitor protect', 'download-monitor' );

		return $bulk_actions;
	}

	/**
	 * Handle our bulk actions
	 *
	 * @param $location
	 * @param $doaction
	 * @param $post_ids
	 *
	 * @return string
	 * @since 4.7.2
	 */
	public function dlm_ml_handle_bulk( $location, $doaction, $post_ids ) {

		global $pagenow;
		if ( 'dlm_protect_files' === $doaction ) {
			return admin_url(
				add_query_arg(
					array(
						'dlm_action' => $doaction,
						'posts'      => $post_ids
					), '/upload.php' ) );
		}

		return $location;
	}

	/**
	 * Bulk action for protecting files
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function dlm_ml_do_bulk(){
		// If there's no action or posts, bail
		if ( ! isset( $_GET['dlm_action'] ) || ! isset( $_GET['posts'] ) ) {
			return;
		}

		$action = $_GET['dlm_action'];
		$posts  = $_GET['posts'];

		if ( 'dlm_protect_files' === $action ) {
			foreach ( $posts as $post_id ) {
				// If it's not an attachment or is already protected, skip it
				if ( 'attachment' !== get_post_type( $post_id ) || ( '1' === get_post_meta( $post_id, 'dlm_protected_file', true ) ) ) {
					continue;
				}
				// Create the file object
				$file = array(
					'attachment_id' => $post_id,
					'user_id' => get_current_user_id(),
					'title' => get_the_title( $post_id ),
				);
				// Move the file
				download_monitor()->service( 'file_manager' )->move_file_to_dlm_uploads( $file['attachment_id'] );
				// Create the Download
				$this->create_download( $file );
			}
		}
		// Redirect to the media library when finished.
		wp_redirect( admin_url( 'upload.php' ) );
		exit;
	}
}
