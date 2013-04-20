<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_DLM_Ajax_Handler class.
 */
class WP_DLM_Ajax_Handler {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_download_monitor_remove_file', array( $this, 'remove_file' ) );
		add_action( 'wp_ajax_download_monitor_add_file', array( $this, 'add_file' ) );
		add_action( 'wp_ajax_download_monitor_list_files', array( $this, 'list_files' ) );
		add_action( 'wp_ajax_download_monitor_insert_panel_upload', array( $this, 'insert_panel_upload' ) );
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

		if ( $file && $file->post_type == "dlm_download_version" )
			wp_delete_post( $file->ID );

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

		$file_id        = wp_insert_post( $file );
		$i              = $size;
		$file_version 	= '';
		$file_post_date = current_time( 'mysql' );
		$file_download_count 		= 0;
		$file_urls      = array();

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
		global $download_monitor;

		check_ajax_referer( 'list-files', 'security' );

		if ( ! current_user_can('manage_downloads') ) return false;

		$path = esc_attr( stripslashes( $_POST['path'] ) );

		if ( $path ) {
			$files = $download_monitor->list_files( $path );

			foreach( $files as $found_file ) {

				$file = pathinfo( $found_file['path'] );

				if ( $found_file['type'] == 'folder' ) {

					echo '<li><a href="#" class="folder" data-path="' . trailingslashit( $file['dirname'] ) . $file['basename']  . '">' . $file['basename'] . '</a></li>';

				} else {

					$filename = $file['basename'];
					$extension = ( empty( $file['extension'] ) ) ? '' : $file['extension'];

					if ( substr( $filename, 0, 1 ) == '.' ) continue; // Ignore files starting with . like htaccess
					if ( in_array( $extension, array( '', 'php', 'html', 'htm', 'tmp' ) )  ) continue; // Ignored file types

					echo '<li><a href="#" class="file filetype-' . sanitize_title( $extension ) . '" data-path="' . trailingslashit( $file['dirname'] ) . $file['basename']  . '">' . $file['basename'] . '</a></li>';

				}

			}
		}

		die();
	}
}

new WP_DLM_Ajax_Handler();