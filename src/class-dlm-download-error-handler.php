<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Download_Error_Handler {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Holds the download handler object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public $download_handler;

	private function __construct( $data ) {
		$this->download_handler = $data;
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Download_Error_Handler object.
	 * @since 5.0.0
	 */
	public static function get_instance( $data ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Download_Error_Handler ) ) {
			self::$instance = new DLM_Download_Error_Handler( $data );
		}

		return self::$instance;
	}

	/**
	 * No file path error.
	 *
	 * @param  DLM_Download  $download
	 *
	 * @since 5.0.0
	 */
	public function no_file_path_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: no_file_path' );
			$restriction_type = 'no_file_path';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'No file path defined',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 404 );
			exit;
		}
		header( 'Status: 404 NoFilePaths, No file paths defined.' );
		wp_die( esc_html__( 'No file paths defined.',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ) );
	}

	/**
	 * No file paths error.
	 *
	 * @since 5.0.0
	 */
	public function no_file_paths_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: no_file_paths' );
			$restriction_type = 'no_file_paths';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'No file paths defined',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 404 );
			exit;
		}

		header( 'Status: 404' . esc_html__( 'No file paths defined.',
		                                    'download-monitor' ) );
		wp_die( esc_html__( 'No file paths defined.',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ) );
	}

	/**
	 * No download error.
	 *
	 * @since 5.0.0
	 */
	public function no_download_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: not_found' );
			$restriction_type = 'not_found';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'Download does not exist.',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 404 );
			exit;
		}
		wp_die( esc_html__( 'Download does not exist.',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ),
		        array( 'response' => 404 ) );
	}

	/**
	 * No secure file path error.
	 *
	 * @since 5.0.0
	 */
	public function no_secure_file_path( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: filetype' );
			$restriction_type = 'filetype';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'File has been deleted or moved.',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 404 );
			exit;
		}
		wp_die( esc_html__( 'File has been deleted or moved.',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ),
		        array( 'response' => 404 ) );
	}

	/**
	 * Restricted file type error.
	 *
	 * @since 5.0.0
	 */
	public function restricted_file_type_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: filetype' );
			$restriction_type = 'filetype';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'Download is not allowed for this file type.',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 403 );
			exit;
		}
		wp_die( esc_html__( 'Download is not allowed for this file type.',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ),
		        array( 'response' => 403 ) );
	}

	/**
	 * Restricted file error.
	 *
	 * @since 5.0.0
	 */
	public function restricted_file_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: file_access_denied' );
			$restriction_type = 'access_denied';
			// Set no access modal.
			$this->download_handler->set_no_access_modal( __( 'Access denied to this file.',
			                                                  'download-monitor' ),
			                                              $download,
			                                              $restriction_type );
			http_response_code( 403 );
			exit;
		}
		header( 'Status: 403 Access denied, file not in allowed paths.' );
		wp_die( esc_html__( 'Access denied to this file',
		                    'download-monitor' ) . ' <a href="'
		        . esc_url( home_url() ) . '">'
		        . esc_html__( 'Go to homepage &rarr;',
		                      'download-monitor' ) . '</a>',
		        esc_html__( 'Download Error', 'download-monitor' ) );
	}

	/**
	 * No file access error.
	 *
	 * @since 5.0.0
	 */
	public function no_access_error( $download ) {
		// IF XHR, send error header.
		if ( $this->download_handler->check_for_xhr() ) {
			header( 'X-DLM-Error: access_denied' );
			$restriction_type = 'access_denied';
			// Set no access modal.
			$this->download_handler->set_no_access_modal(
				__( 'Access denied. You do not have permission to download this file.',
				    'download-monitor' ),
				$download,
				$restriction_type
			);
			exit;
		}

		header( 'Status: 403 AccessDenied, You do not have permission to download this file.' );
		wp_die( wp_kses_post( get_option( 'dlm_no_access_error',
		                                  '' ) ),
		        esc_html__( 'Download Error', 'download-monitor' ),
		        array( 'response' => 200 ) );
	}
}