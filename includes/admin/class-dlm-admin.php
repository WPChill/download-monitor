<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * DLM_Admin class.
 */
class DLM_Admin {

	private $settings;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		include_once( 'class-dlm-admin-writepanels.php' );
		include_once( 'class-dlm-admin-media-browser.php' );
		include_once( 'class-dlm-admin-cpt.php' );
		include_once( 'class-dlm-admin-insert.php' );

		// Directory protection
		add_filter( 'mod_rewrite_rules', array( $this, 'ms_files_protection' ) );
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'export_logs' ) );
		add_action( 'admin_init', array( $this, 'delete_logs' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'admin_dashboard' ) );
	}

	/**
	 * ms_files_protection function.
	 *
	 * @access public
	 * @param mixed $rewrite
	 * @return void
	 */
	public function ms_files_protection( $rewrite ) {
	    global $wp_rewrite;

	    if ( ! is_multisite() )
	    	return $rewrite;

		$rule  = "\n# DLM Rules - Protect Files from ms-files.php\n\n";
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
	 * @param mixed $pathdata
	 * @return void
	 */
	public function upload_dir( $pathdata ) {

		if ( isset( $_POST['type'] ) && $_POST['type'] == 'dlm_download' ) {
			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/dlm_uploads';
				$pathdata['url']    = $pathdata['url']. '/dlm_uploads';
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
	 * init_settings function.
	 *
	 * @access private
	 * @return void
	 */
	private function init_settings() {
		$this->settings = apply_filters( 'download_monitor_settings',
			array(
				'general' => array(
					__( 'General', 'download_monitor' ),
					array(
						array(
							'name' 		=> 'dlm_default_template',
							'std' 		=> '',
							'label' 	=> __( 'Default Template', 'download_monitor' ),
							'desc'		=> __( 'Choose which template is used for <code>[download]</code> shortcodes by default (this can be overridden by the <code>format</code> argument).', 'download_monitor' ),
							'type'      => 'select',
							'options'   => array(
								''             => __( 'Default - Title and count', 'download_monitor' ),
								'button'       => __( 'Button - CSS styled button showing title and count', 'download_monitor' ),
								'box'          => __( 'Box - Box showing thumbnail, title, count, filename and filesize.', 'download_monitor' ),
								'filename'     => __( 'Filename - Filename and download count', 'download_monitor' ),
								'title'        => __( 'Title - Shows download title only', 'download_monitor' ),
								'version-list' => __( 'Version list - Lists all download versions in an unordered list', 'download_monitor' ),
								'custom'       => __( 'Custom template', 'download_monitor' )
							)
						),
						array(
							'name' 		=> 'dlm_custom_template',
							'std' 		=> '',
							'label' 	=> __( 'Custom Template', 'download_monitor' ),
							'desc'		=> __( 'Leaving this blank will use the default <code>content-download.php</code> template file. If you enter, for example, <code>image</code>, the <code>content-download-image.php</code> template will be used instead. You can add custom templates inside your theme folder.', 'download_monitor' )
						),
					),
				),
				'endpoints' => array(
					__( 'Endpoint', 'download_monitor' ),
					array(
						array(
							'name' 		=> 'dlm_download_endpoint',
							'std' 		=> 'download',
							'placeholder'	=> __( 'download', 'download_monitor' ),
							'label' 	=> __( 'Download Endpoint', 'download_monitor' ),
							'desc'		=> sprintf( __( 'Define what endpoint should be used for download links. By default this will be <code>%s</code>.', 'download_monitor' ), home_url( '/download/' ) )
						),
						array(
							'name' 		=> 'dlm_download_endpoint_value',
							'std' 		=> 'ID',
							'label' 	=> __( 'Endpoint Value', 'download_monitor' ),
							'desc'		=> sprintf( __( 'Define what unique value should be used on the end of your endpoint to identify the downloadable file. e.g. ID would give a link like <code>%s</code>', 'download_monitor' ), home_url( '/download/10/' ) ),
							'type'      => 'select',
							'options'   => array(
								'ID'   => __( 'Download ID', 'download_monitor' ),
								'slug' => __( 'Download slug', 'download_monitor' )
							)
						),
						array(
							'name' 		=> 'dlm_xsendfile_enabled',
							'std' 		=> '',
							'label' 	=> __( 'X-Accel-Redirect / X-Sendfile', 'download_monitor' ),
							'cb_label'  => __( 'Enable', 'download_monitor' ),
							'desc'		=> __( 'If supported, <code>X-Accel-Redirect</code> / <code>X-Sendfile</code> can be used to serve downloads instead of PHP (server requires <code>mod_xsendfile</code>).', 'download_monitor' ),
							'type'      => 'checkbox'
						)
					)
				),
				'logging' => array(
					__( 'Logging', 'download_monitor' ),
					array(
						array(
							'name' 		=> 'dlm_enable_logging',
							'cb_label'  => __( 'Enable', 'download_monitor' ),
							'std' 		=> '1',
							'label' 	=> __( 'Download Log', 'download_monitor' ),
							'desc'		=> __( 'Log download attempts, IP addresses and more.', 'download_monitor' ),
							'type' 		=> 'checkbox'
						),
						array(
							'name' 			=> 'dlm_ip_blacklist',
							'std' 			=> '192.168.0.*',
							'label' 		=> __( 'Blacklist IPs', 'download_monitor' ),
							'desc'			=> __( 'List IP Addresses to blacklist, 1 per line. Use <code>*</code> for a wildcard.', 'download_monitor' ),
							'placeholder' 	=> '',
							'type' 			=> 'textarea'
						),
						array(
							'name' 		=> 'dlm_user_agent_blacklist',
							'std' 		=> 'Googlebot',
							'label' 	=> __( 'Blacklist user agents', 'download_monitor' ),
							'desc'		=> __( 'List browser user agents to blacklist, 1 per line.', 'download_monitor' ),
							'placeholder' => '',
							'type' 			=> 'textarea'
						),
					)
				)
			)
		);
	}

	/**
	 * register_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {
		$this->init_settings();

		foreach ( $this->settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) )
					add_option( $option['name'], $option['std'] );
				register_setting( 'download_monitor', $option['name'] );
			}
		}
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $download_monitor, $post;

		wp_enqueue_style( 'download_monitor_menu_css', $download_monitor->plugin_url() . '/assets/css/menu.css' );

		if ( $hook == 'index.php' )
			wp_enqueue_style( 'download_monitor_dashboard_css', $download_monitor->plugin_url() . '/assets/css/dashboard.css' );

		$enqueue = false;

		if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' )
			if ( ( ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'dlm_download' ) || ( ! empty( $post->post_type ) && 'dlm_download' === $post->post_type ) )
				$enqueue = true;

		if ( strstr( $hook, 'dlm_download_page' ) )
			$enqueue = true;

		if ( $hook == 'edit-tags.php' && strstr( $_GET['taxonomy'], 'dlm_download' ) )
			$enqueue = true;

        if ( ! $enqueue )
        	return;

		wp_enqueue_script( 'jquery-blockui', $download_monitor->plugin_url() . '/assets/js/blockui.min.js', array( 'jquery' ), '2.61' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-style', (is_ssl()) ? 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' : 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'download_monitor_admin_css', $download_monitor->plugin_url() . '/assets/css/admin.css', array( 'dashicons' ) );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		if ( get_option( 'dlm_enable_logging' ) == 1 )
			add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Logs', 'download_monitor' ), __( 'Logs', 'download_monitor' ), 'manage_options', 'download-monitor-logs', array( $this, 'log_viewer' ) );

		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Settings', 'download_monitor' ), __( 'Settings', 'download_monitor' ), 'manage_options', 'download-monitor-settings', array( $this, 'settings_page' ) );

		if ( apply_filters( 'dlm_show_addons_page', true ) )
			add_submenu_page(  'edit.php?post_type=dlm_download', __( 'Download Monitor Add-ons', 'download_monitor' ),  __( 'Add-ons', 'download_monitor' ) , 'manage_options', 'dlm-addons', array( $this, 'addons_page' ) );
	}

	/**
	 * Output addons page
	 */
	public function addons_page() {
		$addons = include( 'class-dlm-addons.php' );
		$addons->output();
	}

	/**
	 * settings_page function.
	 *
	 * @access public
	 * @return void
	 */
	public function settings_page() {
		global $download_monitor;

		$this->init_settings();
		?>
		<div class="wrap">
			<form method="post" action="options.php">

				<?php settings_fields( 'download_monitor' ); ?>
				<?php screen_icon(); ?>

			    <h2 class="nav-tab-wrapper">
			    	<?php
			    		foreach ( $this->settings as $key => $section ) {
			    			echo '<a href="#settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
			    		}
			    	?>
			    </h2><br/>

				<?php
					if ( ! empty( $_GET['settings-updated'] ) ) {
						flush_rewrite_rules();
						echo '<div class="updated fade"><p>' . __( 'Settings successfully saved', 'download_monitor' ) . '</p></div>';
					}

					foreach ( $this->settings as $key => $section ) {

						echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';

						echo '<table class="form-table">';

						foreach ( $section[1] as $option ) {

							$placeholder = ( ! empty( $option['placeholder'] ) ) ? 'placeholder="' . $option['placeholder'] . '"' : '';

							echo '<tr valign="top"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';

							if ( ! isset( $option['type'] ) ) $option['type'] = '';

							$value = get_option( $option['name'] );

							switch ( $option['type'] ) {

								case "checkbox" :

									?><label><input id="setting-<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php checked( '1', $value ); ?> /> <?php echo $option['cb_label']; ?></label><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
								case "textarea" :

									?><textarea id="setting-<?php echo $option['name']; ?>" class="large-text" cols="50" rows="3" name="<?php echo $option['name']; ?>" <?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
								case "select" :

									?><select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $option['name']; ?>"><?php
										foreach( $option['options'] as $key => $name )
											echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
									?></select><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
								default :

									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo $placeholder; ?> /><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;

							}

							echo '</td></tr>';
						}

						echo '</table></div>';

					}
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'download_monitor' ); ?>" />
				</p>
		    </form>
		</div>
		<?php

		$download_monitor->add_inline_js("
			jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			jQuery('#setting-dlm_default_template').change(function(){
				if ( jQuery(this).val() == 'custom' ) {
					jQuery('#setting-dlm_custom_template').closest('tr').show();
				} else {
					jQuery('#setting-dlm_custom_template').closest('tr').hide();
				}
			}).change();

			jQuery('.nav-tab-wrapper a:first').click();
		");
	}

	/**
	 * log_viewer function.
	 *
	 * @access public
	 * @return void
	 */
	function log_viewer() {
		if ( ! class_exists( 'WP_List_Table' ) )
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

		require_once( 'class-dlm-logging-list-table.php' );

	    $DLM_Logging_List_Table = new DLM_Logging_List_Table();
	    $DLM_Logging_List_Table->prepare_items();
	    ?>
	    <div class="wrap">
	        <div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

	        <h2><?php _e( 'Download Logs', 'download_monitor' ); ?> <a href="<?php echo add_query_arg( 'dlm_download_logs', 'true' ); ?>" class="add-new-h2"><?php _e( 'Export CSV', 'download_monitor' ); ?></a> <a href="<?php echo wp_nonce_url( add_query_arg( 'dlm_delete_logs', 'true' ), 'delete_logs' ); ?>" class="add-new-h2"><?php _e( 'Delete Logs', 'download_monitor' ); ?></a></h2><br/>
	        <form id="dlm_logs">
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

		if ( empty( $_GET['dlm_delete_logs'] ) )
			return;

		check_admin_referer( 'delete_logs' );

		$wpdb->query( "DELETE FROM {$wpdb->download_log};" );
	}

	/**
	 * export_logs function
	 */
	public function export_logs() {
		global $wpdb;

		if ( empty( $_GET['dlm_download_logs'] ) )
			return;

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
        $row[]  = __( 'Download ID', 'download_monitor' );
        $row[]  = __( 'Version ID', 'download_monitor' );
        $row[]  = __( 'Filename', 'download_monitor' );
        $row[]  = __( 'User ID', 'download_monitor' );
        $row[]  = __( 'User Login', 'download_monitor' );
        $row[]  = __( 'User Email', 'download_monitor' );
        $row[]  = __( 'User IP', 'download_monitor' );
        $row[]  = __( 'User Agent', 'download_monitor' );
        $row[]  = __( 'Date', 'download_monitor' );
        $row[]  = __( 'Status', 'download_monitor' );
        $rows[] = '"' . implode( '","', $row ) . '"';

		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$row    = array();
				$row[]  = $item->download_id;
				$row[]  = $item->version_id;

				$download = new DLM_Download( $item->download_id );
        		$download->set_version( $item->version_id );

        		if ( $download->exists() && $download->get_the_filename() )
        			$row[]  = $download->get_the_filename();
        		else
        			$row[]  = '-';

				$row[]  = $item->user_id;

				if ( $item->user_id )
        			$user = get_user_by( 'id', $item->user_id );

        		if ( ! isset( $user ) || ! $user ) {
	        		$row[]  = '-';
	        		$row[]  = '-';
        		} else {
        			$row[]  = $user->user_login;
	        		$row[]  = $user->user_email;
        		}

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
		include_once( 'class-dlm-admin-dashboard.php' );
	}
}

new DLM_Admin();