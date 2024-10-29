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
		// Update the download_column from table download_log from varchar to longtext.
		add_action( 'wp_ajax_dlm_update_download_category', array( $this, 'upgrade_download_category' ), 15 );
		// Action to save the Enable Shop setting.
		add_action( 'wp_ajax_dlm_enable_shop', array( $this, 'enable_shop' ) );
		// AJAX action to retrieve the AAM upsell modal
		add_action( 'wp_ajax_dlm_upsell_modal', array( $this, 'upsell_modal_ajax' ) );
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

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'async-upload', 0 );

		if ( ! is_wp_error( $attachment_id ) ) {
			$attachment_url = wp_get_attachment_url( $attachment_id );

			if ( false !== $attachment_url ) {
				echo esc_url( $attachment_url );
			}
		} else {
			$data = array(
				'error' => $attachment_id->get_error_message(),
			);
			wp_send_json_error( $data );
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

		if ( $file && $file->post_type == 'dlm_download_version' ) {
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
		download_monitor()->service( 'view_manager' )->display(
			'meta-box/version',
			array(
				'version_increment'   => $size,
				'file_id'             => $new_version->get_id(),
				'file_version'        => $new_version->get_version(),
				'file_post_date'      => $new_version->get_date(),
				'file_download_count' => $new_version->get_download_count(),
				'file_urls'           => $new_version->get_mirrors(),
				'version'             => $new_version,
				'date_format'         => get_option( 'date_format' ),
				'file_browser'        => defined( 'DLM_FILE_BROWSER' ) ? ! (bool) DLM_FILE_BROWSER : get_option( 'dlm_turn_off_file_browser', true ),
			)
		);

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

		$path         = sanitize_text_field( wp_unslash( $_POST['path'] ) );
		$file_manager = download_monitor()->service( 'file_manager' );
		// Parse file path.
		list( $file_path, $remote_file, $restriction ) = $file_manager->get_secure_path( $path );
		// Check if we have a restriction.
		if ( $restriction ) {
			echo __( 'You are not allowed in this directory', 'download-monitor' );
			wp_die();
		}

		// List all files
		$files           = $file_manager->list_files( $path );
		$disallowed_dirs = $file_manager->disallowed_wp_directories();
		foreach ( $files as $found_file ) {
			$allow = true;
			// Multi-byte-safe pathinfo
			$file = $file_manager->mb_pathinfo( $found_file['path'] );
			foreach ( $disallowed_dirs as $disallowed_dir ) {
				if ( strpos( trailingslashit( $file['dirname'] . DIRECTORY_SEPARATOR . $file['basename'] ), $disallowed_dir ) ) {
					$allow = false;
					break;
				}
			}
			if ( ! $allow ) {
				continue;
			}
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
		$notice = sanitize_text_field( wp_unslash( $_POST['notice'] ) );

		// check nonce
		check_ajax_referer( 'dlm_hide_notice-' . $notice, 'nonce' );

		// Check if the user has rights.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this', 'download-monitor' ) );
		}

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
		// Check if the user has rights.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this', 'download-monitor' ) );
			exit;
		}

		if ( ! isset( $_POST['option'] ) ) {
			wp_send_json_error();
			exit;
		}

		// settings key
		$option_key = sanitize_text_field( wp_unslash( $_POST['option'] ) );

		// get options
		$options = apply_filters( 'dlm_settings_lazy_select_' . $option_key, array() );

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
				'version_id'  => absint( $_POST['version_id'] ),
			)
		);
		update_post_meta( absint( $_POST['file_id'] ), 'dlm_download', $meta );
		wp_send_json_success();
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
		// Check if the user has rights.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'download-monitor' ) ) );
		}
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

	/**
	 * Enable Shop function.
	 *
	 * @return void
	 *
	 * @since 5.0.0
	 */
	public function enable_shop() {
		check_ajax_referer( 'dlm_ajax_nonce', 'nonce' );
		if ( empty( $_POST['value'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No data submitted', 'download-monitor' ) ) );
		}
		// Check if the user has rights.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'download-monitor' ) ) );
		}

		$enable_shop = 'true' === sanitize_text_field( wp_unslash( $_POST['value'] ) ) ? '1' : '0';

		update_option( 'dlm_shop_enabled', $enable_shop );
		wp_send_json_success();
	}

	/**
	 * Ajax handler for the DLM Upsell modals
	 *
	 * @since 5.0.13
	 */
	public function upsell_modal_ajax() {
		// Check security nonce
		check_ajax_referer( 'dlm_modal_upsell', 'security' );
		// Check if user has capability to manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'download-monitor' ) ) );
		}
		if ( empty( $_POST['upsell'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No upsell provided.', 'download-monitor' ) ) );
		}
		$upsell  = sanitize_text_field( wp_unslash( $_POST['upsell'] ) );
		$upsells = DLM_Upsells::get_modal_upsells();
		if ( ! isset( $upsells[ $upsell ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Upsell not found.', 'download-monitor' ) ) );
		}
		ob_start();
		// Get the modal content
		include dirname( DLM_PLUGIN_FILE ) . '/src/Admin/UpsellsTemplates/' . $upsell . '-modal-upsell.php';
		$content = ob_get_clean();
		// Send the modal content
		wp_send_json_success( array( 'content' => $content ) );
	}
}
