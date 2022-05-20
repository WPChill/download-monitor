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
	public $dlm_logging;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';

		$this->dlm_logging = DLM_Logging::get_instance();
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
		$request_method = ( ! empty( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET' );
		if ( ! in_array( $request_method, apply_filters( 'dlm_accepted_request_methods', array( 'GET', 'POST' ) ) ) ) {
			return;
		}

		// GET to query_var
		if ( ! empty( $_GET[ $this->endpoint ] ) ) {
			$wp->query_vars[ $this->endpoint ] = sanitize_text_field( wp_unslash($_GET[ $this->endpoint ]) );
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
				$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash($_SERVER['HTTP_REFERER'])) : '';

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
					wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
				}
			}

			if ( ! $download ) {
				wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			// Handle version (if set)
			$version_id = '';

			if ( ! empty( $_GET['version'] ) ) {
				$version_id = $download->get_version_id_version_name( sanitize_text_field( wp_unslash($_GET['version']) ) );
			}

			if ( ! empty( $_GET['v'] ) ) {
				$version_id = absint( $_GET['v'] );
			}


			if ( $version_id ) {
				try {
					$version = download_monitor()->service( 'version_repository' )->retrieve_single( $version_id );
					$download->set_version( $version );
				} catch ( Exception $e ) {

				}
			}

			$def_restricted = array( 'php', 'html', 'htm', 'tmp' );
			$restricted_file_types = array_merge( $def_restricted, apply_filters( 'dlm_restricted_file_types', array( '' ), $download ) );

			// Do not allow the download of certain file types.
			if ( in_array( $download->get_version()->get_filetype(), $restricted_file_types ) ) {
				wp_die( esc_html__( 'Download is not allowed for this file type.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			// Action on found download
			if ( $download->exists() ) {
				if ( post_password_required( $download_id ) ) {
					wp_die( get_the_password_form( $download_id ) , esc_html__( 'Password Required', 'download-monitor' ) );
				}

				$this->trigger( $download );
			} elseif ( $redirect = apply_filters( 'dlm_404_redirect', false ) ) {
				wp_redirect( $redirect );
			} else {
				wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			die( '1' );
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

			header( 'Status: 404' . esc_html__('No file paths defined.', 'download-monitor') );
			wp_die( esc_html__( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}

		// Get a random file (mirror)
		$file_path = $file_paths[ array_rand( $file_paths ) ];

		// Check if we actually got a path
		if ( ! $file_path ) {

			header( 'Status: 404 NoFilePaths, No file paths defined.' );
			wp_die( esc_html__( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}

		// Parse file path
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $file_path );

		$file_path = apply_filters( 'dlm_file_path', $file_path, $remote_file, $download );
		// The return of the get_secure_path function is an array that consists of the path ( string ), remote file ( bool ) and restriction ( bool ).
		// If the path is false it means that the file is restricted, so don't download it or redirect to it.
		if ( $restriction ) {

			header( 'Status: 403 Access denied, file not in allowed paths.' );
			wp_die( esc_html__( 'Access denied to this file', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}

		// Check Access
		if ( ! apply_filters( 'dlm_can_download', true, $download, $version, $_POST ) ) {

			// Check if we need to redirect if visitor don't have access to file
			if ( $redirect = apply_filters( 'dlm_access_denied_redirect', false ) ) {
				if ( isset( $_SERVER['HTTP_DLM_REQUEST'] ) && 'dlm_XMLHttpRequest' === $_SERVER['HTTP_DLM_REQUEST'] ) {
					header("dlm-redirect:" . $redirect );
					exit;
				}
				header( "Status: 401 redirect,$redirect" );
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

						//get wordpress permalink structure so we can build the url
						$structure = get_option('permalink_structure', 0 );

						// append download id to no access URL

						if( '' == $structure || 0 == $structure ){
							$no_access_permalink = add_query_arg( 'download-id', $download->get_id(), untrailingslashit( $no_access_permalink ) );
						}else{
							$no_access_permalink = untrailingslashit( $no_access_permalink ) . '/download-id/' . $download->get_id() . '/';
						}

						if ( ! $download->get_version()->is_latest() ) {
							$no_access_permalink = add_query_arg( 'version', $download->get_version()->get_version(), $no_access_permalink );
						}

						if ( isset( $_SERVER['HTTP_DLM_REQUEST'] ) || 'dlm_XMLHttpRequest' === $_SERVER['HTTP_DLM_REQUEST']  ) {							
							header("dlm-redirect:" . $no_access_permalink );
							exit;
						}
						// redirect to no access page
						header( "Status: 401 redirect,$redirect" );
						wp_redirect( $no_access_permalink );
						exit; // out
					}

				}

				header( "Status: 403 AccessDenied, You do not have permission to download this file." );
				wp_die( wp_kses_post( get_option( 'dlm_no_access_error', '' ) ), esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 200 ) );

			}

			exit;
		}

		// check if user downloaded this version in the past minute.
		if ( false === DLM_Cookie_Manager::exists( $download ) ) {
			// Trigger Download Action.
			do_action( 'dlm_downloading', $download, $version, $file_path );
		}

		// Redirect to the file...
		if ( $download->is_redirect_only() || apply_filters( 'dlm_do_not_force', false, $download, $version ) ) {

			$this->dlm_logging->log( $download, $version, 'redirect' );
			$allowed_paths = download_monitor()->service( 'file_manager' )->get_allowed_paths();
			
			// Ensure we have a valid URL, not a file path
			$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
			// At this point the $correct_path should have a value of the file path as the verification was made prior to this check
			// we get the secure file path.
			$correct_path = download_monitor()->service( 'file_manager' )->get_correct_path( $file_path, $allowed_paths );
			$file_path    = str_replace( str_replace( DIRECTORY_SEPARATOR, '/', $correct_path ), site_url( '/', $scheme ), str_replace( DIRECTORY_SEPARATOR, '/', $file_path ) );

			header( "X-Robots-Tag: noindex, nofollow", true );
			header( 'Location: ' . $file_path );
			exit;
		}
		
		$this->download_headers( $file_path, $download, $version );

		do_action( 'dlm_start_download_process', $download, $version, $file_path, $remote_file );

		if ( '1' === get_option( 'dlm_xsendfile_enabled' ) ) {
			if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {

				$this->dlm_logging->log( $download, $version, 'completed' );

				header( "X-Sendfile: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {

				$this->dlm_logging->log( $download, $version, 'completed' );

				header( "X-LIGHTTPD-send-file: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {

				$this->dlm_logging->log( $download, $version, 'completed' );

				if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
					// phpcs:ignore
					$file_path = str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', $file_path );
				}

				header( "X-Accel-Redirect: /$file_path" );
				exit;
			}
		}

		// multipart-download and download resuming support - http://www.phpgang.com/force-to-download-a-file-in-php_112.html
		if ( isset( $_SERVER['HTTP_RANGE'] ) && $version->get_filesize() ) {
			// phpcs:ignore
			list( $a, $range ) = explode( "=", $_SERVER['HTTP_RANGE'], 2 );
			list( $range ) = explode( ",", $range, 2 );
			list( $range, $range_end ) = explode( "-", $range );
			$range = intval( $range );

			if ( ! $range_end ) {
				$range_end = $version->get_filesize();
			} else {
				$range_end = intval( $range_end );
			}

			//$new_length = $range_end - $range;
			$new_length = ( $range_end - $range ) + 1;
			header( "HTTP/1.1 206 Partial Content" );
			header( "Content-Length: $new_length" );
			header( "Content-Range: bytes {$range}-{$range_end}/{$version->get_filesize()}" );

		} else {
			$range = false;
		}

		if ( $remote_file ) {
			// Redirect - we can't track if this completes or not
			header( 'Location: ' . $file_path );
			$this->dlm_logging->log( $download, $version, 'redirect' );

		} elseif ( file_exists( $file_path ) ) {

			$this->dlm_logging->log( $download, $version, 'completed' );
			$this->readfile_chunked( $file_path, false, $range );

		} else {
			$this->dlm_logging->log( $download, $version, 'failed' );

			wp_die( esc_html__( 'File not found.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
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

		$file_manager = new DLM_File_Manager();
		$file_size    = $file_manager->get_file_size( $file_path );

		if ( $file_size ) {
			// Replace the old way ( getting the filesize from the DB ) in case the user has replaced the file directly using cPanel,
			// FTP or other File Manager, or sometimes using  an optimization service it may cause unwanted results.
			$headers['Content-Length'] = $file_size;
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
		$handle    = fopen( $file, 'rb' );

		if ( $handle === false ) {
			return false;
		}

		if ( $range ) {
			fseek( $handle, $range );
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			// phpcs:ignore
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
