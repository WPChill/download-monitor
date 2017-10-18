<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin class.
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

		// Admin menus
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_menu', array( $this, 'admin_menu_extensions' ), 20 );

		// setup settings
		$settings = new DLM_Admin_Settings();
		add_action( 'admin_init', array( $settings, 'register_settings' ) );
		$settings->register_lazy_load_callbacks();

		// Logs
		add_action( 'admin_init', array( $this, 'export_logs' ) );
		add_action( 'admin_init', array( $this, 'delete_logs' ) );

		// Dashboard
		add_action( 'wp_dashboard_setup', array( $this, 'admin_dashboard' ) );

		// Admin Footer Text
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		// flush rewrite rules on shutdown
		add_action( 'shutdown', array( $this, 'maybe_flush_rewrites' ) );

        // filter attachment thumbnails in media library for files in dlm_uploads
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'filter_thumbnails_protected_files' ), 10, 1 );
	}

	/**
	 * ms_files_protection function.
	 *
	 * @access public
	 *
	 * @param mixed $rewrite
	 *
	 * @return void
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
	 * @return void
	 */
	public function upload_dir( $pathdata ) {

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
     * filter attachment thumbnails in media library for files in dlm_uploads
     *
	 * @param array $response
	 *
	 * @return array
	 */
	public function filter_thumbnails_protected_files( $response ) {

		if ( apply_filters( 'dlm_filter_thumbnails_protected_files', true ) ) {
			$upload_dir = wp_upload_dir();

			if ( strpos( $response['url'], $upload_dir['baseurl'] . '/dlm_uploads' ) !== false ) {
				if ( ! empty( $response['sizes'] ) ) {
					$dlm_protected_thumb = WP_DLM::get_plugin_url() . '/assets/images/protected-file-thumbnail.png';
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
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;

		wp_enqueue_style( 'download_monitor_menu_css', WP_DLM::get_plugin_url() . '/assets/css/menu.css' );

		if ( $hook == 'index.php' ) {
			wp_enqueue_style( 'download_monitor_dashboard_css', WP_DLM::get_plugin_url() . '/assets/css/dashboard.css' );
		}

		$enqueue = false;

		if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
			if ( ( ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'dlm_download' ) || ( ! empty( $post->post_type ) && 'dlm_download' === $post->post_type ) ) {
				$enqueue = true;
			}
		}

		if ( strstr( $hook, 'dlm_download_page' ) ) {
			$enqueue = true;
		}

		if ( $hook == 'edit-tags.php' && strstr( $_GET['taxonomy'], 'dlm_download' ) ) {
			$enqueue = true;
		}

		if ( ! $enqueue ) {
			return;
		}

		wp_enqueue_script( 'jquery-blockui', WP_DLM::get_plugin_url() . '/assets/js/blockui.min.js', array( 'jquery' ), '2.61' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-style', ( is_ssl() ) ? 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' : 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'download_monitor_admin_css', WP_DLM::get_plugin_url() . '/assets/css/admin.css', array( 'dashicons' ) );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {

		// Logging object
		$logging = new DLM_Logging();

		// Logs page
		if ( $logging->is_logging_enabled() ) {
			add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Logs', 'download-monitor' ), __( 'Logs', 'download-monitor' ), 'dlm_manage_logs', 'download-monitor-logs', array(
				$this,
				'log_viewer'
			) );
		}

		// Settings page
		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Settings', 'download-monitor' ), __( 'Settings', 'download-monitor' ), 'manage_options', 'download-monitor-settings', array(
			$this,
			'settings_page'
		) );

	}

	/**
	 * Add the admin menu on later hook so extensions can be add before this menu item
	 */
	public function admin_menu_extensions() {
		// Extensions page
		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Download Monitor Extensions', 'download-monitor' ), '<span style="color:#419CCB;font-weight:bold;">' . __( 'Extensions', 'download-monitor' ) . '</span>', 'manage_options', 'dlm-extensions', array(
			$this,
			'extensions_page'
		) );
	}

	/**
	 * Output extensions page
	 */
	public function extensions_page() {
		$admin_extensions = new DLM_Admin_Extensions();
		$admin_extensions->output();
	}

	/**
	 * Print global notices
	 */
	private function print_global_notices() {

		// check for nginx
		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false && 1 != get_option( 'dlm_hide_notice-nginx_rules', 0 ) ) {

			// get upload dir
			$upload_dir = wp_upload_dir();

			// replace document root because nginx uses path from document root
			$upload_path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', $upload_dir['basedir'] );

			// form nginx rules
			$nginx_rules = "location " . $upload_path . "/dlm_uploads {<br/>deny all;<br/>return 403;<br/>}";
			echo '<div class="error notice is-dismissible dlm-notice" id="nginx_rules" data-nonce="' . wp_create_nonce( 'dlm_hide_notice-nginx_rules' ) . '">';
			echo '<p>' . __( "Because your server is running on nginx, our .htaccess file can't protect your downloads.", 'download-monitor' );
			echo '<br/>' . sprintf( __( "Please add the following rules to your nginx config to disable direct file access: %s", 'download-monitor' ), '<br/><br/><code>' . $nginx_rules . '</code>' ) . '</p>';
			echo '</div>';
		}

	}

	/**
	 * settings_page function.
	 *
	 * @access public
	 * @return void
	 */
	public function settings_page() {

		// initialize settings
        $admin_settings = new DLM_Admin_Settings();
		$settings = $admin_settings->get_settings();

		// print global notices
		$this->print_global_notices();
		?>
		<div class="wrap">
			<form method="post" action="options.php">

				<?php settings_fields( 'download-monitor' ); ?>
				<?php screen_icon(); ?>

				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $settings as $key => $section ) {
						echo '<a href="#settings-' . sanitize_title( $key ) . '" id="dlm-tab-settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
					}
					?>
				</h2><br/>

				<input type="hidden" id="setting-dlm_settings_tab_saved" name="dlm_settings_tab_saved" value="general" />

				<?php

				if ( ! empty( $_GET['settings-updated'] ) ) {
					$this->need_rewrite_flush = true;
					echo '<div class="updated notice is-dismissible"><p>' . __( 'Settings successfully saved', 'download-monitor' ) . '</p></div>';

					$dlm_settings_tab_saved = get_option( 'dlm_settings_tab_saved', 'general' );

					echo '<script type="text/javascript">var dlm_settings_tab_saved = "' . $dlm_settings_tab_saved . '";</script>';
				}

				foreach ( $settings as $key => $section ) {

					echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';

					echo '<table class="form-table">';

					foreach ( $section[1] as $option ) {
						
						echo '<tr valign="top"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';

						if ( ! isset( $option['type'] ) ) {
							$option['type'] = '';
						}

						// make new field object
						$field = DLM_Admin_Fields_Field_Factory::make( $option );

						// check if factory made a field
						if ( null !== $field ) {
							// render field
							$field->render();

							if ( $option['desc'] ) {
								echo ' <p class="dlm-description">' . $option['desc'] . '</p>';
							}
						}

						echo '</td></tr>';
					}

					echo '</table></div>';

				}
				?>
				<p class="submit">
					<input type="submit" class="button-primary"
					       value="<?php _e( 'Save Changes', 'download-monitor' ); ?>"/>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * log_viewer function.
	 *
	 * @access public
	 * @return void
	 */
	function log_viewer() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		$DLM_Logging_List_Table = new DLM_Logging_List_Table();
		$DLM_Logging_List_Table->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

			<h2><?php _e( 'Download Logs', 'download-monitor' ); ?> <a
					href="<?php echo add_query_arg( 'dlm_download_logs', 'true', admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-logs' ) ); ?>"
					class="add-new-h2"><?php _e( 'Export CSV', 'download-monitor' ); ?></a> <a
					href="<?php echo wp_nonce_url( add_query_arg( 'dlm_delete_logs', 'true', admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-logs' ) ), 'delete_logs' ); ?>"
					class="add-new-h2"><?php _e( 'Delete Logs', 'download-monitor' ); ?></a></h2><br/>

			<form id="dlm_logs" method="post">
				<?php $DLM_Logging_List_Table->display() ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Delete logs
	 */
	public function delete_logs() {
		global $wpdb;

		if ( empty( $_GET['dlm_delete_logs'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_downloads' ) ) {
			wp_die( "You're not allowed to delete logs." );
		}

		check_admin_referer( 'delete_logs' );

		$wpdb->query( "DELETE FROM {$wpdb->download_log};" );
	}

	/**
	 * export_logs function
	 */
	public function export_logs() {
		global $wpdb;

		if ( empty( $_GET['dlm_download_logs'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_downloads' ) ) {
			wp_die( "You're not allowed to export logs." );
		}

		$filter_status = isset( $_REQUEST['filter_status'] ) ? sanitize_text_field( $_REQUEST['filter_status'] ) : '';
		$filter_month  = ! empty( $_REQUEST['filter_month'] ) ? sanitize_text_field( $_REQUEST['filter_month'] ) : '';

		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->download_log}
		    	WHERE type = 'download'
		    	" . ( $filter_status ? "AND download_status = '%s'" : "%s" ) . "
	            " . ( $filter_month ? "AND download_date >= '%s'" : "%s" ) . "
	            " . ( $filter_month ? "AND download_date <= '%s'" : "%s" ) . "
		    	ORDER BY download_date DESC",
				( $filter_status ? $filter_status : "" ),
				( $filter_month ? date( 'Y-m-01', strtotime( $filter_month ) ) : "" ),
				( $filter_month ? date( 'Y-m-t', strtotime( $filter_month ) ) : "" )
			)
		);

		$rows   = array();
		$row    = array();
		$row[]  = __( 'Download ID', 'download-monitor' );
		$row[]  = __( 'Version ID', 'download-monitor' );
		$row[]  = __( 'Filename', 'download-monitor' );
		$row[]  = __( 'User ID', 'download-monitor' );
		$row[]  = __( 'User Login', 'download-monitor' );
		$row[]  = __( 'User Email', 'download-monitor' );
		$row[]  = __( 'User IP', 'download-monitor' );
		$row[]  = __( 'User Agent', 'download-monitor' );
		$row[]  = __( 'Date', 'download-monitor' );
		$row[]  = __( 'Status', 'download-monitor' );
		$rows[] = '"' . implode( '","', $row ) . '"';

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$row   = array();
				$row[] = $item->download_id;
				$row[] = $item->version_id;

				$download = new DLM_Download( $item->download_id );
				$download->set_version( $item->version_id );

				if ( $download->exists() && $download->get_the_filename() ) {
					$row[] = $download->get_the_filename();
				} else {
					$row[] = '-';
				}

				$row[] = $item->user_id;

				if ( $item->user_id ) {
					$user = get_user_by( 'id', $item->user_id );
				}

				if ( ! isset( $user ) || ! $user ) {
					$row[] = '-';
					$row[] = '-';
				} else {
					$row[] = $user->user_login;
					$row[] = $user->user_email;
				}

				unset( $user );

				$row[]  = $item->user_ip;
				$row[]  = $item->user_agent;
				$row[]  = $item->download_date;
				$row[]  = $item->download_status . ( $item->download_status_message ? ' - ' : '' ) . $item->download_status_message;
				$rows[] = '"' . implode( '","', $row ) . '"';
			}
		}

		$log = implode( "\n", $rows );

		header( "Content-type: text/csv" );
		header( "Content-Disposition: attachment; filename=download_log.csv" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Content-Length: " . strlen( $log ) );
		echo $log;
		exit;
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
}