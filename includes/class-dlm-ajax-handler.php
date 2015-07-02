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
		add_action( 'wp_ajax_dlm_extension', array( $this, 'handle_extensions' ) );
		add_action( 'wp_ajax_dlm_dismiss_notice', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * insert_panel_upload function.
	 *
	 * @access public
	 * @return void
	 */
	public function insert_panel_upload() {

		check_ajax_referer( 'file-upload' );

		$status = wp_handle_upload( $_FILES['async-upload'], array( 'test_form' => false ) );

		echo $status['url'];

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

		$file = get_post( intval( $_POST['file_id'] ) );

		if ( $file && $file->post_type == "dlm_download_version" ) {
			delete_transient( 'dlm_file_version_ids_' . $file->post_parent );
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

		check_ajax_referer( 'add-file', 'security' );

		$post_id = intval( $_POST['post_id'] );
		$size    = intval( $_POST['size'] );

		$file = array(
			'post_title'   => 'Download #' . $post_id . ' File Version',
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post_id,
			'post_type'    => 'dlm_download_version'
		);

		$file_id             = wp_insert_post( $file );
		$i                   = $size;
		$file_version        = '';
		$file_post_date      = current_time( 'mysql' );
		$file_download_count = 0;
		$file_urls           = array();

		delete_transient( 'dlm_file_version_ids_' . $post_id );

		include( 'admin/html-downloadable-file-version.php' );

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
			return false;
		}

		$path = esc_attr( stripslashes( $_POST['path'] ) );

		if ( $path ) {

			// The File Manager
			$file_manager = new DLM_File_Manager();

			// List all files
			$files = $file_manager->list_files( $path );

			foreach ( $files as $found_file ) {

				// Multi-byte-safe pathinfo
				$file = $file_manager->mb_pathinfo( $found_file['path'] );

				if ( $found_file['type'] == 'folder' ) {

					echo '<li><a href="#" class="folder" data-path="' . trailingslashit( $file['dirname'] ) . $file['basename'] . '">' . $file['basename'] . '</a></li>';

				} else {

					$filename  = $file['basename'];
					$extension = ( empty( $file['extension'] ) ) ? '' : $file['extension'];

					if ( substr( $filename, 0, 1 ) == '.' ) {
						continue;
					} // Ignore files starting with . like htaccess
					if ( in_array( $extension, array( '', 'php', 'html', 'htm', 'tmp' ) ) ) {
						continue;
					} // Ignored file types

					echo '<li><a href="#" class="file filetype-' . sanitize_title( $extension ) . '" data-path="' . trailingslashit( $file['dirname'] ) . $file['basename'] . '">' . $file['basename'] . '</a></li>';

				}

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
		$notice = $_POST['notice'];

		// check nonce
		check_ajax_referer( 'dlm_hide_notice-' . $notice, 'nonce' );

		// update option
		update_option( 'dlm_hide_notice-' . $notice, 1 );

		// send JSON
		wp_send_json( array( 'response' => 'success' ) );
	}

	/**
	 * Handle extensions AJAX
	 */
	public function handle_extensions() {

		// Check nonce
		check_ajax_referer( 'dlm-ajax-nonce', 'nonce' );

		// Post vars
		$product_id       = sanitize_text_field( $_POST['product_id'] );
		$key              = sanitize_text_field( $_POST['key'] );
		$email            = sanitize_text_field( $_POST['email'] );
		$extension_action = $_POST['extension_action'];

		// Get products
		$products = DLM_Product_Manager::get()->get_products();

		// Check if product exists
		if ( isset( $products[ $product_id ] ) ) {

			// Get correct product
			$product = $products[ $product_id ];

			// Set new key in license object
			$product->get_license()->set_key( $key );

			// Set new email in license object
			$product->get_license()->set_email( $email );


			if ( 'activate' === $extension_action ) {
				// Try to activate the license
				$response = $product->activate();
			} else {
				// Try to deactivate the license
				$response = $product->deactivate();
			}

		}

		// Send JSON
		wp_send_json( $response );

	}
}