<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Ajax_Handler class.
 */
class DLM_Ajax_Handler {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'wp_ajax_download_monitor_remove_file', array( $this, 'remove_file' ) );
		add_action( 'wp_ajax_download_monitor_add_file', array( $this, 'add_file' ) );
		add_action( 'wp_ajax_download_monitor_list_files', array( $this, 'list_files' ) );
		add_action( 'wp_ajax_download_monitor_insert_panel_upload', array( $this, 'insert_panel_upload' ) );
		add_action( 'wp_ajax_dlm_settings_lazy_select', array( $this, 'handle_settings_lazy_select' ) );
		add_action( 'wp_ajax_dlm_dismiss_notice', array( $this, 'dismiss_notice' ) );
		add_action( 'wp_ajax_dlm_update_file_meta', array( $this, 'save_attachment_meta' ) );
		add_action( 'wp_ajax_nopriv_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_no_access_dlm_xhr_download', array( $this, 'xhr_no_access_modal' ), 15 );
		add_action( 'wp_ajax_dlm_forgot_license', array( $this, 'forgot_license' ), 15 );
		// Update the download_column from table download_log from varchar to longtext.
		add_Action( 'wp_ajax_dlm_update_download_category', array( $this, 'upgrade_download_category' ), 15 );
	}

	/**
	 * insert_panel_upload function.
	 *
	 * @access public
	 * @return void
	 */
	public function insert_panel_upload() {

		check_ajax_referer( 'file-upload' );

		// Check user rights
		if ( ! current_user_can( 'manage_downloads' ) ) {
			die();
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$attachment_id = media_handle_upload( 'async-upload', 0 );

		if ( ! is_wp_error( $attachment_id ) ) {
			$attachment_url = wp_get_attachment_url( $attachment_id );

			if ( false !== $attachment_url ) {
				echo esc_url( $attachment_url );
			}
		} else {
			$data = array(
				'error' => $attachment_id->get_error_message()
			);
			wp_send_json_error($data);
		}

		die();
	}

	/**
	 * remove_file function.
	 *
	 * @access public
	 * @return void
	 */
	public function remove_file() {

		check_ajax_referer( 'remove-file', 'security' );

		// Check user rights
		if ( ! current_user_can( 'manage_downloads' ) ) {
			die();
		}

		if ( ! isset( $_POST['file_id'] ) ) {
			die();
		}

		$file = get_post( intval( $_POST['file_id'] ) );

		if ( $file && $file->post_type == "dlm_download_version" ) {
			// clear transient
			download_monitor()->service( 'transient_manager' )->clear_versions_transient( $file->post_parent );

			wp_delete_post( $file->ID );
		}

		die();
	}

	/**
	 * add_file function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_file() {

		// check nonce
		check_ajax_referer( 'add-file', 'security' );

		// Check user rights
		if ( ! current_user_can( 'manage_downloads' ) ) {
			die();
		}

		// get POST data
		$download_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$size        = isset( $_POST['size'] ) ? absint( $_POST['size'] ) : 0;

		/** @var DLM_Download_Version $new_version */
		$new_version = new DLM_Download_Version();

		// set download id
		$new_version->set_download_id( $download_id );

		// set other version data
		$new_version->set_author( get_current_user_id() );
		$new_version->set_date( new DateTime( current_time( 'mysql' ) ) );

		// persist new version
		download_monitor()->service( 'version_repository' )->persist( $new_version );

		// clear download transient
		download_monitor()->service( 'transient_manager' )->clear_versions_transient( $download_id );

		// output new version admin html
		download_monitor()->service( 'view_manager' )->display( 'meta-box/version', array(
			'version_increment'   => $size,
			'file_id'             => $new_version->get_id(),
			'file_version'        => $new_version->get_version(),
			'file_post_date'      => $new_version->get_date(),
			'file_download_count' => $new_version->get_download_count(),
			'file_urls'           => $new_version->get_mirrors(),
			'version'             => $new_version,
			'date_format'         => get_option( 'date_format' ),
			'file_browser'        => get_option( 'dlm_turn_off_file_browser', true ) 
		) );

		die();
	}

	/**
	 * list_files function.
	 *
	 * @access public
	 * @return void
	 */
	public function list_files() {

		// Check Nonce
		check_ajax_referer( 'list-files', 'security' );

		// Check user rights
		if ( ! current_user_can( 'manage_downloads' ) ) {
			die();
		}

		if ( ! isset( $_POST['path'] ) ) {
			die();
		}

		// If searched path is not a child of ABSPATH die - prevents directory traversal
		if ( false === strpos( $_POST['path'], ABSPATH ) ) {
			die();
		}

		$path = sanitize_text_field( wp_unslash( $_POST['path'] ) );

		// List all files
		$files = download_monitor()->service( 'file_manager' )->list_files( $path );

		foreach ( $files as $found_file ) {

			// Multi-byte-safe pathinfo
			$file = download_monitor()->service( 'file_manager' )->mb_pathinfo( $found_file['path'] );

			if ( $found_file['type'] == 'folder' ) {

				echo '<li><a href="#" class="folder" data-path="' . esc_attr( trailingslashit( $file['dirname'] ) ) . esc_attr( $file['basename'] ) . '">' . esc_attr( $file['basename'] ) . '</a></li>';

			} else {

				$filename  = $file['basename'];
				$extension = ( empty( $file['extension'] ) ) ? '' : $file['extension'];

				if ( substr( $filename, 0, 1 ) == '.' ) {
					continue;
				} // Ignore files starting with . like htaccess
				if ( in_array( $extension, array( '', 'php', 'html', 'htm', 'tmp' ) ) ) {
					continue;
				} // Ignored file types

				echo '<li><a href="#" class="file filetype-' . esc_attr( sanitize_title( $extension ) ) . '" data-path="' . esc_attr( trailingslashit( $file['dirname'] ) ) . esc_attr( $file['basename'] ) . '">' . esc_attr( $file['basename'] ) . '</a></li>';

			}

		}

		die();
	}

	/**
	 * Handle notice dismissal
	 */
	public function dismiss_notice() {

		// check notice
		if ( ! isset( $_POST['notice'] ) || empty( $_POST['notice'] ) ) {
			exit;
		}

		// the notice
		$notice = sanitize_text_field( wp_unslash($_POST['notice']) );

		// check nonce
		check_ajax_referer( 'dlm_hide_notice-' . $notice, 'nonce' );

		// update option
		update_option( 'dlm_hide_notice-' . $notice, 1 );

		// send JSON
		wp_send_json( array( 'response' => 'success' ) );
	}

	/**
	 * Handle lazy select AJAX calls
	 */
	public function handle_settings_lazy_select() {

		// check nonce
		check_ajax_referer( 'dlm-settings-lazy-select-nonce', 'nonce' );

		if ( ! isset( $_POST['option'] ) ) {
			wp_send_json_error();
			exit;
		}

		// settings key
		$option_key = sanitize_text_field( wp_unslash($_POST['option']) );

		// get options
		$options = apply_filters( 'dlm_settings_lazy_select_'.$option_key, array() );

		// send options
		wp_send_json( $options );
		exit;

	}


	/**
	 * Save attachment meta dlm_download
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function save_attachment_meta() {
		// Check if there is a nonce
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'No nonce send' );
		}
		// check nonce
		check_ajax_referer( 'add-file', 'nonce' );
		$meta = json_encode(
			array(
				'download_id' => absint( $_POST['download_id'] ),
				'version_id'  => absint( $_POST['version_id'] )
			)
		);
		update_post_meta( absint( $_POST['file_id'] ), 'dlm_download', $meta );
		wp_send_json_success();
	}


	/**
	 * Log the XHR download
	 *
	 * @return void
	 */
	public function xhr_no_access_modal() {
		$settings = download_monitor()->service( 'settings' );
		if ( ! isset( $_POST['download_id'] ) || ! isset( $_POST['version_id'] ) ) {
			if ( '1' === $settings->get_option( 'xsendfile_enabled' ) ) {
				wp_send_json_error( 'Missing download_id or version_id. X-Sendfile is enabled, so this is a problem.' );
			}
			wp_send_json_error( 'Missing download_id or version_id' );
		}

		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );

		// Action to allow the adition of extra scripts and code related to the shortcode.
		do_action( 'dlm_dlm_no_access_shortcode_scripts' );

		$atts = array(
			'show_message' => 'true',
		);
		$content = '';
		$no_access_page = $settings->get_option( 'no_access_page' );
		if ( ! $no_access_page ) {
			ob_start();

			// template handler.
			$template_handler = new DLM_Template_Handler();

			if ( 'empty-download' === $_POST['download_id'] || ( isset( $_POST['modal_text'] ) && ! empty( $_POST['modal_text'] ) ) ) {
				if ( isset( $_POST['modal_text'] ) && ! empty( $_POST['modal_text'] ) ) {
					echo sanitize_text_field( wp_unslash( $_POST['modal_text'] ) );
				} else {
					echo '<p>' . __( 'You do not have permission to download this file.', 'download-monitor' ) . '</p>';
				}
			} else {

				try {
					/** @var \DLM_Download $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $_POST['download_id'] ) );
					$version  = ( 'empty-download' !== $_POST['download_id'] ) ? download_monitor()->service( 'version_repository' )->retrieve_single( absint( $_POST['version_id'] ) ) : $download->get_version();
					$download->set_version( $version );

					// load no access template.
					$template_handler->get_template_part(
						'no-access',
						'',
						'',
						array(
							'download'          => $download,
							'no_access_message' => ( ( $atts['show_message'] ) ? wp_kses_post( get_option( 'dlm_no_access_error', '' ) ) : '' )
						)
					);
				} catch ( Exception $exception ) {
					wp_send_json_error( 'No download found' );
				}
			}

			$restriction_type = isset( $_POST['restriction'] ) && 'restriction-empty' !== $_POST['restriction'] ? sanitize_text_field( wp_unslash( $_POST['restriction'] ) ) : 'no_access_page';

			$title   = apply_filters(
				'dlm_modal_title',
				array(
					'no_file_path'   => __( 'Error!', 'download-monitor' ),
					'no_file_paths'  => __( 'Error!', 'download-monitor' ),
					'access_denied'  => __( 'No access!', 'download-monitor' ),
					'file_not_found' => __( 'Error!', 'download-monitor' ),
					'not_found'      => __( 'Error!', 'download-monitor' ),
					'filetype'       => __( 'No access!', 'download-monitor' ),
					'no_access_page' => __( 'No access!', 'download-monitor' ),
				)
			);
			$content = ob_get_clean();
		} else {
			$content = do_shortcode( apply_filters( 'the_content', get_post_field( 'post_content', $no_access_page ) ) );
		}
		$modal_template = '
			<div id="dlm-no-access-modal" >
				<div class="dlm-no-access-modal-overlay">

				</div>
				<div class="dlm-no-access-modal-window">
					<div class="dlm-no-access-modal__header">
						<span class="dlm-no-access-modal__title">' . esc_html( $title[ $restriction_type ] ) . ' </span>
						<span class="dlm-no-access-modal-close" title="' . esc_attr__( 'Close Modal', 'download-monitor' ) . '"> <span class="dashicons dashicons-no"></span>
					</div>
					<div class="dlm-no-access-modal__body">						
						' . $content . '			
					</div>	
					<div class="dlm-no-access-modal__footer">
						<button class="dlm-no-access-modal-close">' . esc_html__( 'Close', 'download-monitor' ) . '</button>
					</div>
				</div>			
			</div>';
		// Content and variables escaped above.
		// $content variable escaped from extensions as it may include inputs or other HTML elements.
		echo $modal_template; //phpcs:ignore
		die();
	}

	/**
	 * Forgot license function.
	 *
	 * @return void
	 *
	 * @since 4.8.0
	 */
	public function forgot_license() {
		check_ajax_referer( 'dlm-ajax-nonce', 'nonce' );
		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Email is required', 'download-monitor' ) ) );
		}

		if ( ! is_email( sanitize_email( wp_unslash( $_POST['email'] ) ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'download-monitor' ) ) );
		}

		// Do activate request.
		$api_request = wp_remote_get(
			DLM_Product::STORE_URL . 'dlm_forgotten_license_api' . '&' . http_build_query(
				array(
					'email'    => sanitize_email( wp_unslash( $_POST['email'] ) ),
				),
				'',
				'&'
			)
		);

		// Check request.
		if ( is_wp_error( $api_request ) || wp_remote_retrieve_response_code( $api_request ) != 200 ) {
			wp_send_json_error( array( 'message' => __( 'Could not connect to the license server', 'download-monitor' ) ) );
		}

		wp_send_json( json_decode( $api_request['body'], true ) );
		wp_die();
	}

	/**
	 * Update the column download_category from table download_log from varchar to longtext
	 *
	 * @return void
	 * @since 4.8.0
	 */
	public function upgrade_download_category() {
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce is required', 'download-monitor' ) ) );
		}

		// Check ajax referrer.
		check_ajax_referer( 'dlm-ajax-nonce', 'nonce' );
		global $wpdb;

		$cat_col_type = DLM_Admin_Helper::check_column_type( $wpdb->prefix . 'download_log', 'download_category', 'longtext' );
		// If null, then the column doesn't exist. If false, then the column is not the correct type.
		if ( null !== $cat_col_type && ! $cat_col_type ) {
			$result = $wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'download_log` MODIFY COLUMN `download_category` longtext DEFAULT NULL;' );
			if ( ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Error while updating the column download_category', 'download-monitor' ) ) );
			}
			wp_send_json_success( array( 'message' => __( 'Column download_category has been updated', 'download-monitor' ) ) );
		} else {
			wp_send_json_success( array( 'message' => __( 'Column download_category is already updated', 'download-monitor' ) ) );
		}
	}
}
