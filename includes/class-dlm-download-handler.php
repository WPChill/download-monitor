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
		add_filter( 'dlm_can_download', array( $this, 'check_blacklist' ), 10, 2 );
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
	 * Check blacklist (hooked into dlm_can_download) checks if the download request comes from blacklisted IP address or user agent
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
	public function check_blacklist( $can_download, $download ) {

		// Check if IP is blacklisted
		if ( false !== $can_download ) {

			$visitor_ip = DLM_Utils::get_visitor_ip();
			$ip_type    = 0;

			if ( filter_var( $visitor_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$ip_type = 4;
			} elseif ( filter_var( $visitor_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$ip_type = 6;
			}

			$blacklisted_ips = preg_split( "/\r?\n/", trim( get_option( 'dlm_ip_blacklist', "" ) ) );

			/**
			 * Until IPs are validated at time of save, we need to ensure entries
			 * are legitimate before using them. Allow formats:
			 *   IPv4, e.g. 198.51.100.1
			 *   IPv4/CIDR netmask, e.g. 198.51.100.0/24
			 *   IPv6, e.g. 2001:db8::1
			 *   IPv6/CIDR netmask, e.g. 2001:db8::/32
			 */

			// IP/CIDR netmask regexes
			// http://blog.markhatton.co.uk/2011/03/15/regular-expressions-for-ip-addresses-cidr-ranges-and-hostnames/
			// http://stackoverflow.com/questions/53497/regular-expression-that-matches-valid-ipv6-addresses
			$ip4_with_mask_pattern = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))$/';
			$ip6_with_mask_pattern = '/^((([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))(\/[0-9][0-9]?|1([01][0-9]|2[0-8])))$/';

			if ( 4 === $ip_type ) {
				foreach ( $blacklisted_ips as $blacklisted_ip ) {

					// Detect unique IPv4 address and ranges of IPv4 addresses in IP/CIDR netmask format
					if ( filter_var( $blacklisted_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) || preg_match( $ip4_with_mask_pattern, $blacklisted_ip ) ) {
						if ( DLM_Utils::ipv4_in_range( $visitor_ip, $blacklisted_ip ) ) {
							$can_download = false;
							break;
						}
					}
				}
			} elseif ( 6 === $ip_type ) {
				foreach ( $blacklisted_ips as $blacklisted_ip ) {

					// Detect unique IPv6 address and ranges of IPv6 addresses in IP/CIDR netmask format
					if ( filter_var( $blacklisted_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) || preg_match( $ip6_with_mask_pattern, $blacklisted_ip ) ) {
						if ( DLM_Utils::ipv6_in_range( $visitor_ip, $blacklisted_ip ) ) {
							$can_download = false;
							break;
						}
					}
				}
			}

		}

		// Check if user agent is blacklisted
		if ( false !== $can_download ) {

			// get request user agent
			$visitor_ua = DLM_Utils::get_visitor_ua();

			// check if $visitor_ua isn't empty
			if ( ! empty( $visitor_ua ) ) {

				// get blacklisted user agents
				$blacklisted_uas = preg_split( "/\r?\n/", trim( get_option( 'dlm_user_agent_blacklist', "" ) ) );

				if ( ! empty( $blacklisted_uas ) ) {

					// loop through blacklisted user agents
					foreach ( $blacklisted_uas as $blacklisted_ua ) {

						if ( ! empty( $blacklisted_ua ) ) {
							// check if blacklisted user agent is found in request user agent
							if ( false !== stristr( $visitor_ua, $blacklisted_ua ) ) {
								$can_download = false;
								break;
							}
						}

					}

				}

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
	private function log( $type = '', $status = '', $message = '', $download, $version ) {

		// Logging object
		$logging = new DLM_Logging();

		// Check if logging is enabled and if unique ips is enabled
		if ( $logging->is_logging_enabled() ) {

			// set create_log to true
			$create_log = true;

			// check if requester downloaded this version before
			if ( '1' == get_option( 'dlm_count_unique_ips', '0' ) && true === $this->has_ip_downloaded_version( $version ) ) {
				$create_log = false;
			}

			// check if we need to create the log
			if ( $create_log ) {
				// Create log
				$logging->create_log( $type, $status, $message, $download, $version );
			}

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

				// get 'no access' page id
				$no_access_page_id = get_option( 'dlm_no_access_page', 0 );

				// check if a no access page is set
				if ( $no_access_page_id > 0 ) {

					// get permalink of no access page
					$no_access_permalink = get_permalink( $no_access_page_id );

					// check if we can find a permalink
					if ( false !== $no_access_permalink ) {

						// append download id to no access URL
						$no_access_permalink = untrailingslashit( $no_access_permalink ) . '/download-id/' . $download->id . '/';

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

		// check if user downloaded this version in the past minute
		if ( empty( $_COOKIE['wp_dlm_downloading'] ) || $download->get_the_version_number() != $_COOKIE['wp_dlm_downloading'] ) {


			// bool if we need to increment download count
			$increment_download_count = true;

			// check if unique ips option is enabled and if so, if visitor already downloaded this file version
			if ( '1' == get_option( 'dlm_enable_logging' ) && '1' == get_option( 'dlm_count_unique_ips' ) && true === $this->has_ip_downloaded_version( $version ) ) {
				$increment_download_count = false;
			}

			// check if we need to increment the download count
			if ( true === $increment_download_count ) {
				// Increase download count
				$version->increase_download_count();
			}

			// Trigger Download Action
			do_action( 'dlm_downloading', $download, $version, $file_path );

			// Set cookie to prevent double logging
			setcookie( 'wp_dlm_downloading', $download->get_the_version_number(), time() + 60, COOKIEPATH, COOKIE_DOMAIN, false, true );
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

		// Check file exists
		if (empty($file_path)) {
			$this->log( 'download', 'failed', __( 'File not found.', 'download-monitor' ), $download, $version );
			wp_die( __( 'File not found.', 'download-monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', __( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
		}

		$this->download_headers( $file_path, $download, $version, $remote_file );

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
	private function download_headers( $file_path, $download, $version, $remote_file = true ) {
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
		$file_name = urldecode( DLM_Utils::basename( $file_path ) );

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

		// Zip corruption fix
		while ( ob_get_level() > 0 ) {
			@ob_end_clean();
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

		if ( $version->filesize >= 0 ) {
			$headers['Content-Length'] = $version->filesize;
			$headers['Accept-Ranges']  = 'bytes';
		} elseif (!$remote_file) {
			$headers['Content-Length'] = filesize($file_path);
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

	/**
	 * Check if visitor has downloaded version in the past 24 hours
	 *
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	private function has_ip_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE type = 'download' AND `version_id` = %d AND `user_ip` = %s", $version->id, DLM_Utils::get_visitor_ip() ) ) ) > 0 );
	}
}
