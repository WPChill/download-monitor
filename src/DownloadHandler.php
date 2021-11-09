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
		add_filter( 'dlm_can_download', array( $this, 'check_members_only' ), 10, 2 );
	}

	/**
	 * Check members only (hooked into dlm_can_download) checks if the download is members only and enfoces log in.
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
	public function check_members_only( $can_download, $download ) {

		// Check if download is a 'members only' download
		if ( false !== $can_download && $download->is_members_only() ) {

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
	 * @return array
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
		
		// check HTTP method
		$request_method = ( ! empty( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' );
		if ( ! in_array( $request_method, apply_filters( 'dlm_accepted_request_methods', array( 'GET', 'POST' ) ) ) ) {
			return;
		}

		// GET to query_var
		if ( ! empty( $_GET[ $this->endpoint ] ) ) {
			$wp->query_vars[ $this->endpoint ] = $_GET[ $this->endpoint ];
		}

		// check if endpoint is set but is empty
		if ( apply_filters( 'dlm_empty_download_redirect_enabled', true ) && isset ( $wp->query_vars[ $this->endpoint ] ) && empty ( $wp->query_vars[ $this->endpoint ] ) ) {
			wp_redirect( apply_filters( 'dlm_empty_download_redirect_url', home_url() ) );
			exit;
		}

		// check if need to handle an actual download
		if ( ! empty( $wp->query_vars[ $this->endpoint ] ) && ( ( null === $wp->request ) || ( null !== $wp->request && strstr( $wp->request, $this->endpoint . '/' ) ) ) ) {

			// Prevent caching when endpoint is set
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}


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

			/** @var DLM_Download $download */
			$download = null;
			if ( $download_id > 0 ) {
				try {
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
				} catch ( Exception $e ) {
					// download not found
				}

			}

			// Handle version (if set)
			$version_id = '';

			if (  ! is_null($download) && ! empty( $_GET['version'] ) ) {
				$version_id = $download->get_version_id_version_name( $_GET['version'] );
			}

			if ( ! empty( $_GET['v'] ) ) {
				$version_id = absint( $_GET['v'] );
			}


			if ( ! is_null($download) && $version_id ) {
				try {
					$version = download_monitor()->service( 'version_repository' )->retrieve_single( $version_id );
					$download->set_version( $version );
				} catch ( Exception $e ) {

				}
			}

			// Action on found download
			if ( ! is_null( $download ) && $download->exists() ) {
				if ( post_password_required( $download_id ) ) {
					wp_die( get_the_password_form( $download_id ), __( 'Password Required', 'download-monitor' ) );
				}

				$this->trigger( $download );
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
	 *
	 * @param string $type
	 * @param string $status
	 * @param string $message
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 */
	private function log( $download, $version ) {

		// Check if logging is enabled.
		if ( DLM_Logging::is_logging_enabled() && DLM_Logging::is_download_window_enabled( $download ) ) {

			// setup new log item object
			$log_item = new DLM_Log_Item();
			$log_item->set_user_id( absint( get_current_user_id() ) );
			$log_item->set_download_id( absint( $download->get_id() ) );
			$log_item->set_version_id( absint( $version->get_id() ) );
			$log_item->set_version( $version->get_version() );

			// persist log item.
			download_monitor()->service( 'log_item_repository' )->persist( $log_item );
		}

	}

	/**
	 * trigger function.
	 *
	 * @access private
	 *
	 * @param DLM_Download $download
	 *
	 * @return void
	 */
	private function trigger( $download ) {

		// Download is triggered. First thing we do, send no cache headers.
		$this->cache_headers();

		/** @var DLM_Download_Version $version */
		$version = $download->get_version();

		/** @var array $file_paths */
		$file_paths = $version->get_mirrors();

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

				// get 'no access' page id
				$no_access_page_id = get_option( 'dlm_no_access_page', 0 );

				// check if a no access page is set
				if ( $no_access_page_id > 0 ) {

					// get permalink of no access page
					$no_access_permalink = get_permalink( $no_access_page_id );

					// check if we can find a permalink
					if ( false !== $no_access_permalink ) {

						// append download id to no access URL
						$no_access_permalink = untrailingslashit( $no_access_permalink ) . '/download-id/' . $download->get_id() . '/';

						if ( ! $download->get_version()->is_latest() ) {
							$no_access_permalink = add_query_arg( 'version', $download->get_version()->get_version(), $no_access_permalink );
						}

						// redirect to no access page
						wp_redirect( $no_access_permalink );

						exit; // out
					}

				}

				// if we get to this point, we have no proper 'no access' page. Fallback to default wp_die
				wp_die( wp_kses_post( get_option( 'dlm_no_access_error', '' ) ), __( 'Download Error', 'download-monitor' ), array( 'response' => 200 ) );

			}

			exit;
		}

		// check if user downloaded this version in the past minute.
		if ( DLM_Logging::is_download_window_enabled( $download ) ) {

			$version->increase_download_count();

			// Trigger Download Action
			do_action( 'dlm_downloading', $download, $version, $file_path );

			// Set cookie to prevent double logging
			DLM_Cookie_Manager::set_cookie( $download );
		}

		// Redirect to the file...
		if ( $download->is_redirect_only() || apply_filters( 'dlm_do_not_force', false, $download, $version ) ) {
			$this->log( $download, $version );

			// Ensure we have a valid URL, not a file path
			$file_path = str_replace( ABSPATH, site_url( '/', 'http' ), $file_path );

			header( 'Location: ' . $file_path );
			exit;
		}

		// Parse file path
		list( $file_path, $remote_file ) = download_monitor()->service( 'file_manager' )->parse_file_path( $file_path );
		$file_path = apply_filters( 'dlm_file_path', $file_path, $remote_file, $download );

		$this->download_headers( $file_path, $download, $version );

		if ( get_option( 'dlm_xsendfile_enabled' ) ) {
			if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {
				$this->log( $download, $version );

				header( "X-Sendfile: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {
				$this->log( $download, $version );

				header( "X-LIGHTTPD-send-file: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
				$this->log( $download, $version );

				$file_path = str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', $file_path );
				header( "X-Accel-Redirect: /$file_path" );
				exit;
			}
		}

		// multipart-download and download resuming support - http://www.phpgang.com/force-to-download-a-file-in-php_112.html
		if ( isset( $_SERVER['HTTP_RANGE'] ) && $version->get_filesize() ) {
			list( $a, $range ) = explode( "=", $_SERVER['HTTP_RANGE'], 2 );
			list( $range ) = explode( ",", $range, 2 );
			list( $range, $range_end ) = explode( "-", $range );
			$range = intval( $range );

			if ( ! $range_end ) {
				$range_end = $version->get_filesize() - 1;
			} else {
				$range_end = intval( $range_end );
			}

			$new_length = $range_end - $range;

			header( "HTTP/1.1 206 Partial Content" );
			header( "Content-Length: $new_length" );
			header( "Content-Range: bytes {$range}-{$range_end}/{$version->get_filesize()}" );

		} else {
			$range = false;
		}

		if ( $this->readfile_chunked( $file_path, $range ) ) {

			// Complete!
			$this->log( $download, $version );

		} elseif ( $remote_file ) {

			// Redirect - we can't track if this completes or not.
			$this->log( $download, $version );

			header( 'Location: ' . $file_path );

		} else {
			wp_die( __( 'File not found.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
		}

		exit;
	}

	/**
	 * Send cache headers to browser. No cache pelase.
	 */
	private function cache_headers() {
		global $is_IE;

		if ( $is_IE && is_ssl() ) {
			// IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header( 'Cache-Control: private' );
		} else {
			nocache_headers();
		}
	}

	/**
	 * Output download headers
	 *
	 * @param string $file_path
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 */
	private function download_headers( $file_path, $download, $version ) {

		// Get Mime Type
		$mime_type = "application/octet-stream";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $version->get_filetype(), $mimes ) ) {
				$mime_type = $type;
				break;
			}
		}

		// Get file name
		$file_name = urldecode( DLM_Utils::basename( $file_path ) );

		if ( strstr( $file_name, '?' ) ) {
			$file_name = current( explode( '?', $file_name ) );
		}

		// Environment + headers
		if ( ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}

		if ( version_compare( PHP_VERSION, '7.4.0', '<' ) && function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() ) {
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

		// Zip corruption fix
		while ( ob_get_level() > 0 ) {
			@ob_end_clean();
		}

		$headers = array();

		$headers['X-Robots-Tag']              = 'noindex, nofollow';
		$headers['Content-Type']              = $mime_type;
		$headers['Content-Description']       = 'File Transfer';
		$headers['Content-Disposition']       = "attachment; filename=\"{$file_name}\";";
		$headers['Content-Transfer-Encoding'] = 'binary';

		if ( $version->get_filesize() ) {
			$headers['Content-Length'] = $version->get_filesize();
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
	 * @param    string $file
	 * @param    boolean $retbytes return bytes of file
	 * @param    boolean $range if  HTTP RANGE to seek
	 *
	 * @return   mixed
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
