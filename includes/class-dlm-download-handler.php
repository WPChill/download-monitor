<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * DLM_Download_Handler class.
 */
class DLM_Download_Handler {

	private $endpoint;
	private $ep_value;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';

		add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );
		add_action( 'init', array( $this, 'add_endpoint'), 0 );
		add_action( 'parse_request', array( $this, 'handler'), 0 );
		add_action( 'dlm_can_download', array( $this, 'check_access' ), 10, 2 );
	}

	/**
	 * Check access (hooked into dlm_can_download) checks if the download is members only and enfoces log in.
	 *
	 * Other plugins can use the 'dlm_can_download' filter directly to change access rights.
	 *
	 * @access public
	 * @param mixed $download
	 * @return void
	 */
	public function check_access( $can_download, $download ) {
		if ( $download->is_members_only() && ! is_user_logged_in() )
			$can_download = false;

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
	 * Listen for download requets and trigger downloading.
	 *
	 * @access public
	 * @return void
	 */
	public function handler() {
		global $wp, $wpdb;

		if ( ! empty( $_GET[ $this->endpoint ] ) )
			$wp->query_vars[ $this->endpoint ] = $_GET[ $this->endpoint ];

		if ( ! empty( $wp->query_vars[ $this->endpoint ] ) ) {

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

			if ( $download_id > 0 )
				$download = new DLM_Download( $download_id );
			else
				$download = null;

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
			if ( ! is_null( $download ) && $download->exists() )
				$this->trigger( $download, $version_id );

			elseif ( $redirect = apply_filters( 'dlm_404_redirect', false ) )
				wp_redirect( $redirect );

			else
				wp_die( __( 'Download does not exist.', 'download_monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download_monitor' ) . '</a>', __( 'Download Error', 'download_monitor' ) );

			die('1');
		}
	}

	/**
	 * trigger function.
	 *
	 * @access private
	 * @param mixed $download
	 * @return void
	 */
	private function trigger( $download ) {

		$version    = $download->get_file_version();
		$file_paths = $version->mirrors;

		if ( empty( $file_paths ) )
			wp_die( __( 'No file paths defined.', 'download_monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download_monitor' ) . '</a>', __( 'Download Error', 'download_monitor' ) );

		$file_path  = $file_paths[ array_rand( $file_paths ) ];

		if ( ! $file_path )
			wp_die( __( 'No file paths defined.', 'download_monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download_monitor' ) . '</a>', __( 'Download Error', 'download_monitor' ) );

		// Check Access
		if ( ! apply_filters( 'dlm_can_download', true, $download, $version ) ) {

			if ( $redirect = apply_filters( 'dlm_access_denied_redirect', false ) )
				wp_redirect( $redirect );

			else
				wp_die( __( 'You do not have permission to access this download.', 'download_monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download_monitor' ) . '</a>', __( 'Download Error', 'download_monitor' ) );

			exit;
		}

		// Log Hit
		$version->increase_download_count();

		// Trigger Download Action
		do_action( 'dlm_downloading', $download, $version );

		// Redirect to the file...
		if ( apply_filters( 'dlm_do_not_force', false, $download, $version ) ) {
			if ( function_exists( 'dlm_create_log' ) )
				dlm_create_log( 'download', 'redirected', __( 'Redirected to file', 'download_monitor' ), $download, $version );

			$file_path = str_replace( ABSPATH, site_url( '/', 'http' ), $file_path );

			header( 'Location: ' . $file_path );
			exit;
		}

		// ...or serve it
		if ( ! is_multisite() ) {

			$file_path   = str_replace( site_url( '/', 'https' ), ABSPATH, $file_path );
			$file_path   = str_replace( site_url( '/', 'http' ), ABSPATH, $file_path );

		} else {

			// Try to replace network url
			$file_path   = str_replace( network_admin_url( '/', 'https' ), ABSPATH, $file_path );
			$file_path   = str_replace( network_admin_url( '/', 'http' ), ABSPATH, $file_path );

			// Try to replace upload URL
			$upload_dir  = wp_upload_dir();
			$file_path   = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );
		}

		// See if its local or remote
		if ( strstr( $file_path, 'http:' ) || strstr( $file_path, 'https:' ) || strstr( $file_path, 'ftp:' ) ) {
			$remote_file = true;
		} else {
			$remote_file    = false;
			$real_file_path = realpath( current( explode( '?', $file_path ) ) );

			if ( ! empty( $real_file_path ) )
				$file_path = $real_file_path;

			// See if we need to add abspath if this is a relative URL
			if ( ! file_exists( $file_path ) && file_exists( ABSPATH . $file_path ) )
				$file_path = ABSPATH . $file_path;
		}

		// Get Mime Type
		$mime_type       = "application/force-download";

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $version->filetype, $mimes ) ) {
				$mime_type = $type;
				break;
			}
		}

		// HEADERS
		if ( ! ini_get('safe_mode') )
			@set_time_limit(0);

		if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() )
			@set_magic_quotes_runtime(0);

		if( function_exists( 'apache_setenv' ) )
			@apache_setenv( 'no-gzip', 1 );

		@session_write_close();
		@ini_set( 'zlib.output_compression', 'Off' );
		@ob_end_clean();

		if ( ob_get_level() )
			@ob_end_clean(); // Zip corruption fix

		nocache_headers();

		$file_name = basename( $file_path );

		if ( strstr( $file_name, '?' ) )
			$file_name = current( explode( '?', $file_name ) );

		header( "Robots: none" );
		header( "Content-Type: " . $mime_type );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $file_name . "\";" );
		header( "Content-Transfer-Encoding: binary" );

        if ( $version->filesize )
        	header( "Content-Length: " . $version->filesize );

		if ( get_option( 'dlm_xsendfile_enabled' ) ) {

         	if ( getcwd() )
         		$file_path = trim( preg_replace( '`^' . getcwd() . '`' , '', $file_path ), '/' );

            header( "Content-Disposition: attachment; filename=\"" . $file_name . "\";" );

            if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {
            	if ( function_exists( 'dlm_create_log' ) )
            		dlm_create_log( 'download', 'redirected', __( 'Redirected to file', 'download_monitor' ), $download, $version );

            	header("X-Sendfile: $file_path");
            	exit;
            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {
            	if ( function_exists( 'dlm_create_log' ) )
            		dlm_create_log( 'download', 'redirected', __( 'Redirected to file', 'download_monitor' ), $download, $version );

            	header( "X-Lighttpd-Sendfile: $file_path" );
            	exit;
            } elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
            	if ( function_exists( 'dlm_create_log' ) )
            		dlm_create_log( 'download', 'redirected', __( 'Redirected to file', 'download_monitor' ), $download, $version );

            	header( "X-Accel-Redirect: /$file_path" );
            	exit;
            }
        }

        if ( $this->readfile_chunked( $file_path ) ) {
	        // Complete!
	        if ( function_exists( 'dlm_create_log' ) )
	        	dlm_create_log( 'download', 'completed', '', $download, $version );

        } elseif ( $remote_file ) {
	        // Redirect - we can't track if this completes or not
	       if ( function_exists( 'dlm_create_log' ) )
	        	dlm_create_log( 'download', 'redirected', __( 'Redirected to remote file.', 'download_monitor' ), $download, $version );

	        header( 'Location: ' . $file_path );
        } else {
        	if ( function_exists( 'dlm_create_log' ) )
        		dlm_create_log( 'download', 'failed', __( 'File not found', 'download_monitor' ), $download, $version );

	        wp_die( __( 'File not found.', 'download_monitor' ) . ' <a href="' . home_url() . '">' . __( 'Go to homepage &rarr;', 'download_monitor' ) . '</a>', __( 'Download Error', 'download_monitor' ) );
        }

        exit;
	}

	/**
	 * readfile_chunked
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @access   public
	 * @param    string    file
	 * @param    boolean    return bytes of file
	 * @return   void
	 */
	public function readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$buffer = '';
		$cnt = 0;

		$handle = @fopen( $file, 'r' );
		if ( $handle === false )
			return false;

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			ob_flush();
			flush();

			if ( $retbytes )
				$cnt += strlen( $buffer );
		}

		$status = fclose( $handle );

		if ( $retbytes && $status )
			return $cnt;

		return $status;
	}
}

$GLOBALS['DLM_Download_Handler'] = new DLM_Download_Handler();