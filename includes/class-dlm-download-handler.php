<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Download_Handler class.
 */
class DLM_Download_Handler {

	private $endpoint;
	private $ep_value;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';
	}

	/**
	 * Setup Download Handler class
	 */
	public function setup() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		add_action( 'parse_request', array( $this, 'handler' ), 0 );
		add_filter( 'dlm_can_download', array( $this, 'check_access' ), 10, 2 );
	}


	/**
	 * Check access (hooked into dlm_can_download) checks if the download is members only and enfoces log in.
	 *
	 * Other plugins can use the 'dlm_can_download' filter directly to change access rights.
	 *
	 * @access public
	 *
	 * @param boolean $can_download
	 * @param mixed $download
	 *
	 * @return boolean
	 */
	public function check_access( $can_download, $download ) {

		// Check if download is a 'members only' download
		if ( $download->is_members_only() ) {

			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				$can_download = false;
			} // Check if it's a multisite and if user is member of blog
			else if ( is_multisite() && ! is_user_member_of_blog( get_current_user_id(), get_current_blog_id() ) ) {
				$can_download = false;
			}

		}

		return $can_download;
	}

	/**
	 * add_query_vars function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_query_vars( $vars ) {
		$vars[] = $this->endpoint;

		return $vars;
	}

	/**
	 * add_endpoint function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( $this->endpoint, EP_ALL );
	}

	/**
	 * Listen for download requests and trigger downloading.
	 *
	 * @access public
	 * @return void
	 */
	public function handler() {
		global $wp, $wpdb;

		if ( ! empty( $_GET[ $this->endpoint ] ) ) {
			$wp->query_vars[ $this->endpoint ] = $_GET[ $this->endpoint ];
		}

		if ( ! empty( $wp->query_vars[ $this->endpoint ] ) && ( ( null === $wp->request ) || ( null !== $wp->request && strstr( $wp->request, $this->endpoint . '/' ) ) ) ) {

			// Prevent caching when endpoint is set
			define( 'DONOTCACHEPAGE', true );

			// Get ID of download
			$raw_id = sanitize_title( stripslashes( $wp->query_vars[ $this->endpoint ] ) );

			// Find real ID
			switch ( $this->ep_value ) {
				case 'slug' :
					$download_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download';", $raw_id ) ) );
					break;
				default :
					$download_id = absint( $raw_id );
					break;
			}

			// Prevent hotlinking
			if ( '1' == get_option( 'dlm_hotlink_protection_enabled' ) ) {

				// Get referer
				$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

				// Check if referer isn't empty or if referer is empty but empty referer isn't allowed
				if ( ! empty( $referer ) || ( empty( $referer ) && apply_filters( 'dlm_hotlink_block_empty_referer', false ) ) ) {

					$allowed_referers = apply_filters( 'dlm_hotlink_allowed_referers', array( home_url() ) );
					$allowed          = false;

					// Loop allowed referers
					foreach ( $allowed_referers as $allowed_referer ) {
						if ( strstr( $referer, $allowed_referer ) ) {
							$allowed = true;
							break;
						}
					}

					// Check if allowed
					if ( false == $allowed ) {
						wp_redirect( apply_filters( 'dlm_hotlink_redirect', home_url(), $download_id ) );
						exit;
					}

				}

			}

			if ( $download_id > 0 ) {
				$download = new DLM_Download( $download_id );
			} else {
				$download = null;
			}

			// Handle version (if set)
			$version_id = '';

			if ( ! empty( $_GET['version'] ) ) {
				$version_id = $download->get_version_id( $_GET['version'] );
			}

			if ( ! empty( $_GET['v'] ) ) {
				$version_id = absint( $_GET['v'] );
			}

			if ( $version_id ) {
				$download->set_version( $version_id );
			}

			// Action on found download
			if ( ! is_null( $download ) && $download->exists() ) {
				if ( post_password_required( $download_id ) ) {
					wp_die( get_the_password_form( $download_id ), __( 'Password Required', 'download-monitor' ) );
				}
				$this->trigger( $download, $version_id );
			} elseif ( $redirect = apply_filters( 'dlm_404_redirect', false ) ) {
				wp_redirect( $redirect );
			} else {
				wp_die( __( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			die( '1' );
		}
	}

	/**
	 * Create a log if logging is enabled
	 */
	private function log( $type = '', $status = '', $message = '', $download, $version ) {

		// Logging object
		$logging = new DLM_Logging();

		// Check if logging is enabled
		if ( $logging->is_logging_enabled() ) {

			// Create log
			$logging->create_log( $type, $status, $message, $download, $version );

		}

	}

	/**
	 * trigger function.
	 *
	 * @access private
	 *
	 * @param mixed $download
	 *
	 * @return void
	 */
	private function trigger( $download ) {

		$version    = $download->get_file_version();
		$file_paths = $version->mirrors;

		// Check if we got files in this version
		if ( empty( $file_paths ) ) {
			wp_die( __( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ) );
		}

		// Get a random file (mirror)
		$file_path = $file_paths[ array_rand( $file_paths ) ];

		// Check if we actually got a path
		if ( ! $file_path ) {
			wp_die( __( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ) );
		}

		// Check Access
		if ( ! apply_filters( 'dlm_can_download', true, $download, $version ) ) {

			// Check if we need to redirect if visitor don't have access to file
			if ( $redirect = apply_filters( 'dlm_access_denied_redirect', false ) ) {
				wp_redirect( $redirect );
				exit;
			} else {
				// Visitor don't have access to file and there's no redirect so display 'no access' message and die
				wp_die( wp_kses_post( get_option( 'dlm_no_access_error', '' ) ), __( 'Download Error', 'download-monitor' ), array( 'response' => 200 ) );
			}

			exit;
		}

		if ( empty( $_COOKIE['wp_dlm_downloading'] ) || $download->id != $_COOKIE['wp_dlm_downloading'] ) {
			// Increase download count
			$version->increase_download_count();

			// Trigger Download Action
			do_action( 'dlm_downloading', $download, $version, $file_path );

			// Set cookie to prevent double logging
			setcookie( 'wp_dlm_downloading', $download->id, time() + 60, COOKIEPATH, COOKIE_DOMAIN, false, true );
		}

		// Redirect to the file...
		if ( $download->redirect_only() || apply_filters( 'dlm_do_not_force', false, $download, $version ) ) {
			$this->log( 'download', 'redirected', __( 'Redirected to file', 'download-monitor' ), $download, $version );

			// Ensure we have a valid URL, not a file path
			$file_path = str_replace( ABSPATH, site_url( '/', 'http' ), $file_path );

			header( 'Location: ' . $file_path );
			exit;
		}

		// File Manager
		$file_manager = new DLM_File_Manager();

		// Parse file path
		list( $file_path, $remote_file ) = $file_manager->parse_file_path( $file_path );

		$this->download_headers( $file_path, $download, $version );

		if ( get_option( 'dlm_xsendfile_enabled' ) ) {
			if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {

				$this->log( 'download', 'redirected', __( 'Redirected to file', 'download-monitor' ), $download, $version );

				header( "X-Sendfile: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {

				$this->log( 'download', 'redirected', __( 'Redirected to file', 'download-monitor' ), $download, $version );

				header( "X-LIGHTTPD-send-file: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {

				$this->log( 'download', 'redirected', __( 'Redirected to file', 'download-monitor' ), $download, $version );

				$file_path = str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', $file_path );
				header( "X-Accel-Redirect: /$file_path" );
				exit;
			}
		}

		// multipart-download and download resuming support - http://www.phpgang.com/force-to-download-a-file-in-php_112.html
		if ( isset( $_SERVER['HTTP_RANGE'] ) && $version->filesize ) {
			list( $a, $range ) = explode( "=", $_SERVER['HTTP_RANGE'], 2 );
			list( $range ) = explode( ",", $range, 2 );
			list( $range, $range_end ) = explode( "-", $range );
			$range = intval( $range );

			if ( ! $range_end ) {
				$range_end = $version->filesize - 1;
			} else {
				$range_end = intval( $range_end );
			}

			$new_length = $range_end - $range;

			header( "HTTP/1.1 206 Partial Content" );
			header( "Content-Length: $new_length" );
			header( "Content-Range: bytes {$range}-{$range_end}/{$version->filesize}" );

		} else {
			$range = false;
		}

		if ( $this->readfile_chunked( $file_path, $range ) ) {

			// Complete!
			$this->log( 'download', 'completed', '', $download, $version );

		} elseif ( $remote_file ) {

			// Redirect - we can't track if this completes or not
			$this->log( 'download', 'redirected', __( 'Redirected to remote file.', 'download-monitor' ), $download, $version );

			header( 'Location: ' . $file_path );

		} else {
			$this->log( 'download', 'failed', __( 'File not found.', 'download-monitor' ), $download, $version );

			wp_die( __( 'File not found.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
		}

		exit;
	}

	/**
	 * Output download headers
	 */
	private function download_headers( $file_path, $download, $version ) {
		global $is_IE;

		// Get Mime Type
		$mime_type = "application/octet-stream";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $version->filetype, $mimes ) ) {
				$mime_type = $type;
				break;
			}
		}

		// Get file name
		$file_name = urldecode( basename( $file_path ) );

		if ( strstr( $file_name, '?' ) ) {
			$file_name = current( explode( '?', $file_name ) );
		}

		// Environment + headers
		if ( ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}

		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
			@set_magic_quotes_runtime( 0 );
		}

		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}

		@session_write_close();
		@ini_set( 'zlib.output_compression', 'Off' );
		@error_reporting( 0 );

		/**
		 * Prevents errors, for example: transfer closed with 3 bytes remaining to read
		 */
		@ob_end_clean(); // Clear the output buffer

		if ( ob_get_level() ) {
			@ob_end_clean(); // Zip corruption fix
		}

		$headers = array();

		if ( $is_IE && is_ssl() ) {
			// IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
			$headers['Expires']       = 'Wed, 11 Jan 1984 05:00:00 GMT';
			$headers['Cache-Control'] = 'private';
		} else {
			nocache_headers();
		}

		$headers['X-Robots-Tag']              = 'noindex, nofollow';
		$headers['Content-Type']              = $mime_type;
		$headers['Content-Description']       = 'File Transfer';
		$headers['Content-Disposition']       = "attachment; filename=\"{$file_name}\";";
		$headers['Content-Transfer-Encoding'] = 'binary';

		if ( $version->filesize ) {
			$headers['Content-Length'] = $version->filesize;
			$headers['Accept-Ranges']  = 'bytes';
		}

		$headers = apply_filters( 'dlm_download_headers', $headers, $file_path, $download, $version );

		foreach ( $headers as $key => $value ) {
			header( $key . ': ' . $value );
		}
	}

	/**
	 * readfile_chunked
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @access   public
	 *
	 * @param    string    file
	 * @param    boolean   return bytes of file
	 * @param    range if  HTTP RANGE to seek
	 *
	 * @return   void
	 */
	public function readfile_chunked( $file, $retbytes = true, $range = false ) {
		$chunksize = 1 * ( 1024 * 1024 );
		$buffer    = '';
		$cnt       = 0;
		$handle    = fopen( $file, 'r' );

		if ( $handle === false ) {
			return false;
		}

		if ( $range ) {
			fseek( $handle, $range );
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;

			if ( $retbytes ) {
				$cnt += strlen( $buffer );
			}
		}

		$status = fclose( $handle );

		if ( $retbytes && $status ) {
			return $cnt;
		}

		return $status;
	}
}
