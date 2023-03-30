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
		$this->endpoint    = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$this->ep_value    = ( $ep_value = get_option( 'dlm_download_endpoint_value' ) ) ? $ep_value : 'ID';
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
	 * @param mixed   $download
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
			elseif ( is_multisite() && ! is_user_member_of_blog( get_current_user_id(), get_current_blog_id() ) ) {
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
	 * @param DLM_Download $download
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
							if ( '/' == $blacklisted_ua[0] && '/' == substr( $blacklisted_ua, - 1 ) ) { // /regex/ pattern
								if ( preg_match( $blacklisted_ua, $visitor_ua ) ) {
									$can_download = false;
									break;
								}
							} else { // string matching
								if ( false !== stristr( $visitor_ua, $blacklisted_ua ) ) {
									$can_download = false;
									break;
								}
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
		// check HTTP method.
		$request_method = ( ! empty( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET' );
		if ( ! in_array( $request_method, apply_filters( 'dlm_accepted_request_methods', array( 'GET', 'POST' ) ) ) ) {
			return;
		}

		// GET to query_var.
		if ( ! empty( $_GET[ $this->endpoint ] ) ) {
			$wp->query_vars[ $this->endpoint ] = sanitize_text_field( wp_unslash( $_GET[ $this->endpoint ] ) );
		}

		// Check and see if this is an XHR request or a classic request.
		if ( isset( $_SERVER['HTTP_DLM_XHR_REQUEST'] ) && 'dlm_XMLHttpRequest' === $_SERVER['HTTP_DLM_XHR_REQUEST'] ) {
			define( 'DLM_DOING_XHR', true );
		}

		// check if endpoint is set but is empty.
		if ( apply_filters( 'dlm_empty_download_redirect_enabled', true ) && isset( $wp->query_vars[ $this->endpoint ] ) && empty( $wp->query_vars[ $this->endpoint ] ) ) {
			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Redirect: ' . apply_filters( 'dlm_empty_download_redirect_url', home_url() ) );
				exit;
			}
			wp_redirect( apply_filters( 'dlm_empty_download_redirect_url', home_url() ) );
			exit;
		}

		// check if need to handle an actual download.
		if ( ! empty( $wp->query_vars[ $this->endpoint ] ) && ( ( null === $wp->request ) || ( '' === $wp->request ) || ( strstr( $wp->request, $this->endpoint . '/' ) ) ) ) {

			// Prevent caching when endpoint is set
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}

			// Get ID of download
			$raw_id = sanitize_title( stripslashes( $wp->query_vars[ $this->endpoint ] ) );

			// Find real ID
			switch ( $this->ep_value ) {
				case 'slug':
					$download_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '%s' AND post_type = 'dlm_download';", $raw_id ) ) );
					break;
				default:
					$download_id = absint( $raw_id );
					break;
			}

			// Prevent hotlinking
			if ( '1' == get_option( 'dlm_hotlink_protection_enabled' ) ) {

				// Get referer
				$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

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
						if ( $this->check_for_xhr() ) {
							header( 'X-DLM-Redirect: ' . apply_filters( 'dlm_hotlink_redirect', home_url(), $download_id ) );
							exit;
						}
						wp_redirect( apply_filters( 'dlm_hotlink_redirect', home_url(), $download_id ) );
						exit;
					}
				}
			}

			/** @var DLM_Download $download */
			$download = null;
			$version  = null;
			if ( $download_id > 0 ) {
				try {
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
				} catch ( Exception $e ) {
					if ( $this->check_for_xhr() ) {
						header( 'X-DLM-Error: ' . esc_html__( 'Download does not exist.', 'download-monitor' ) );
						$restriction_type = 'not_found';
						$this->set_no_access_modal( __( 'Download does not exist.', 'download-monitor' ), $download, $restriction_type );
						http_response_code( 404 );
						exit;
					}
					wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
				}
			}

			if ( ! $download ) {
				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Error: ' . esc_html__( 'Download does not exist.', 'download-monitor' ) );
					$restriction_type = 'not_found';
					$this->set_no_access_modal( __( 'Download does not exist.', 'download-monitor' ), $download, $restriction_type );
					http_response_code( 404 );
					exit;
				}
				wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			// Handle version (if set)
			$version_id = '';

			if ( ! empty( $_GET['version'] ) ) {
				$version_id = $download->get_version_id_version_name( sanitize_text_field( wp_unslash( $_GET['version'] ) ) );
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

			// Action on found download
			if ( $download->exists() ) {

				if ( post_password_required( $download_id ) ) {
					if ( $this->check_for_xhr() ) {
						header( 'X-DLM-Redirect: ' . $download->get_the_download_link() );
						exit;
					}
					wp_die( get_the_password_form( $download_id ), esc_html__( 'Password Required', 'download-monitor' ) );
				}

				$this->trigger( $download );
			} elseif ( $redirect = apply_filters( 'dlm_404_redirect', false ) ) {
				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Redirect: ' . $redirect );
					exit;
				}
				wp_redirect( $redirect );
			} else {
				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Error: ' . esc_html__( 'Download does not exist.', 'download-monitor' ) );
					$restriction_type = 'not_found';
					$this->set_no_access_modal( __( 'Download does not exist.', 'download-monitor' ), $download, $restriction_type );
					http_response_code( 404 );
					exit;
				}
				wp_die( esc_html__( 'Download does not exist.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}

			die( '1' );
		} else {
			// Set the no-waypoints in case link/button has triggering class and we don't want to do the download action. Ex.: Page Addon extension.
			header( 'X-dlm-no-waypoints: true' );
		}
	}

	/**
	 * Trigger function.
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

		if ( $this->check_for_xhr() ) {
			// Set required headers used by XHR download
			$this->set_required_xhr_headers( $download, $version );
		}

		// Check if we got files in this version.
		if ( empty( $file_paths ) ) {
			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Error: ' . esc_html__( 'No file paths defined.', 'download-monitor' ) );
				$restriction_type = 'no_file_paths';
				$this->set_no_access_modal( __( 'No file paths defined', 'download-monitor' ), $download, $restriction_type );
				http_response_code( 404 );
				exit;
			}

			header( 'Status: 404' . esc_html__( 'No file paths defined.', 'download-monitor' ) );
			wp_die( esc_html__( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}

		// Get a random file (mirror).
		$file_path = $file_paths[ array_rand( $file_paths ) ];

		// Check if we actually got a path.
		if ( ! $file_path ) {
			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Error: ' . esc_html__( 'No file path defined.', 'download-monitor' ) );
				$restriction_type = 'no_file_path';
				$this->set_no_access_modal( __( 'No file path defined', 'download-monitor' ), $download, $restriction_type );
				http_response_code( 404 );
				exit;
			}
			header( 'Status: 404 NoFilePaths, No file paths defined.' );
			wp_die( esc_html__( 'No file paths defined.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}

		// Parse file path.
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $file_path );
		$is_redirect = $download->is_redirect_only() || apply_filters( 'dlm_do_not_force', false, $download, $version );

		$file_path = apply_filters( 'dlm_file_path', $file_path, $remote_file, $download );
		// Check for file extension.
		if ( ! $is_redirect ) {
			$def_restricted        = array( 'php', 'html', 'htm', 'tmp' );
			$restricted_file_types = array_merge( $def_restricted, apply_filters( 'dlm_restricted_file_types', array(), $download ) );

			// Do not allow the download of certain file types.
			// If user wants, file type checks can be disabled for remote files.
			$check_file_extension = ( $remote_file && apply_filters( 'dlm_check_remote_extension', true ) ) || ! $remote_file;
			if ( $check_file_extension && in_array( $download->get_version()->get_filetype(), $restricted_file_types ) ) {
				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Error: ' . esc_html__( 'Download is not allowed for this file type.', 'download-monitor' ) );
					$restriction_type = 'filetype';
					$this->set_no_access_modal( __( 'Download is not allowed for this file type.', 'download-monitor' ), $download, $restriction_type );
					http_response_code( 403 );
					exit;
				}
				wp_die( esc_html__( 'Download is not allowed for this file type.', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 404 ) );
			}
		}

		// The return of the get_secure_path function is an array that consists of the path ( string ), remote file ( bool ) and restriction ( bool ).
		// If the path is false it means that the file is restricted, so don't download it or redirect to it.
		if ( $restriction ) {
			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Error: ' . esc_html__( 'Access denied to this file.', 'download-monitor' ) );
				$restriction_type = 'access_denied';
				$this->set_no_access_modal( __( 'Access denied to this file.', 'download-monitor' ), $download, $restriction_type );
				http_response_code( 403 );
				exit;
			}
			header( 'Status: 403 Access denied, file not in allowed paths.' );
			wp_die( esc_html__( 'Access denied to this file', 'download-monitor' ) . ' <a href="' . esc_url( home_url() ) . '">' . esc_html__( 'Go to homepage &rarr;', 'download-monitor' ) . '</a>', esc_html__( 'Download Error', 'download-monitor' ) );
		}
		if ( $this->check_for_xhr() ) {
			// Set extra headers for XHR download.
			$this->set_extra_xhr_headers( $file_path, $download, $version );
		}

		// Check Access.
		if ( ! apply_filters( 'dlm_can_download', true, $download, $version, $_REQUEST, $this->check_for_xhr() ) ) {

			// Check if we need to redirect if visitor don't have access to file.
			if ( $redirect = apply_filters( 'dlm_access_denied_redirect', false ) ) {
				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Redirect: ' . $redirect );
					header( 'X-DLM-No-Access: true' );
					exit;
				}
				header( "Status: 301 redirect,$redirect" );
				wp_redirect( $redirect );
				exit;
			} else {

				// get 'no access' page id.
				$no_access_page_id = get_option( 'dlm_no_access_page', 0 );

				// check if a no access page is set.
				if ( $no_access_page_id > 0 ) {

					// get permalink of no access page.
					$no_access_permalink = get_permalink( $no_access_page_id );

					// check if we can find a permalink.
					if ( false !== $no_access_permalink ) {

						// get WordPress permalink structure so we can build the url.
						$structure = get_option( 'permalink_structure', 0 );

						// append download id to no access URL.

						if ( '' == $structure || 0 == $structure ) {
							$no_access_permalink = add_query_arg( 'download-id', $download->get_id(), untrailingslashit( $no_access_permalink ) );
						} else {
							$no_access_permalink = untrailingslashit( $no_access_permalink ) . '/download-id/' . $download->get_id() . '/';
						}

						if ( ! $download->get_version()->is_latest() ) {
							$no_access_permalink = add_query_arg( 'version', $download->get_version()->get_version(), $no_access_permalink );
						}

						if ( $this->check_for_xhr() ) {
							header( 'X-DLM-Redirect: ' . $no_access_permalink );
							$this->set_no_access_modal( false, $download, 'no_access_page' );
							exit;
						}
						// redirect to no access page.
						header( "Status: 301 redirect,$no_access_permalink" );
						wp_redirect( $no_access_permalink );
						exit; // out.
					}
				}

				if ( $this->check_for_xhr() ) {
					header( 'X-DLM-Error: ' . esc_html__( 'Access denied. You do not have permission to download this file.', 'download-monitor' ) );
					$restriction_type = 'access_denied';
					$this->set_no_access_modal( __( 'Access denied. You do not have permission to download this file.', 'download-monitor' ), $download, $restriction_type );
					exit;
				}

				header( 'Status: 403 AccessDenied, You do not have permission to download this file.' );
				wp_die( wp_kses_post( get_option( 'dlm_no_access_error', '' ) ), esc_html__( 'Download Error', 'download-monitor' ), array( 'response' => 200 ) );

			}

			exit;
		}

		// check if user downloaded this version in the past minute.
		if ( false === DLM_Cookie_Manager::exists( $download ) ) {
			// Trigger Download Action.
			do_action( 'dlm_downloading', $download, $version, $file_path );
			// Set the cookie to prevent multiple download logs in download window of 60 seconds.
			// Do this only for non-XHR downloads as XHR downloads are logged through AJAX request
			if ( '1' === get_option( 'dlm_enable_window_logging', '0' ) && ! $this->check_for_xhr() ) {
				// Set cookie here to prevent "Cannot modify header information - headers already sent" error
				// in non-XHR downloads.
				DLM_Cookie_Manager::set_cookie( $download );
			}
		}

		$referrer = ( isset( $_SERVER['HTTP_REFERER'] ) ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '';

		$allowed_paths = download_monitor()->service( 'file_manager' )->get_allowed_paths();
		// we get the secure file path.
		$correct_path = download_monitor()->service( 'file_manager' )->get_correct_path( $file_path, $allowed_paths );

		// Redirect to the file...
		if ( $is_redirect ) {
			if ( ! $this->check_for_xhr() ) {
				$this->dlm_logging->log( $download, $version, 'redirected', false, $referrer );
			}

			// If it's not a remote file we need to create the correct URL.
			if ( ! $remote_file ) {

				// Let's check if the file is in the uploads' folder.
				$uploads_dir = wp_upload_dir();
				$file_path   = str_replace( DIRECTORY_SEPARATOR, '/', $file_path );
				$basedir     = str_replace( DIRECTORY_SEPARATOR, '/', $uploads_dir['basedir'] );
				$sympath     = ( is_link( $basedir ) ) ? str_replace( DIRECTORY_SEPARATOR, '/', readlink( $basedir ) ) : false;

				if ( false !== strpos( $file_path, $basedir ) ) { // File is in the uploads' folder, so we need to create the correct URL.
					// Set the URL for the uploads' folder.
					$file_path = str_replace( str_replace( DIRECTORY_SEPARATOR, '/', trailingslashit( $basedir ) ), str_replace( DIRECTORY_SEPARATOR, '/', trailingslashit( $uploads_dir['baseurl'] ) ), $file_path );
				} elseif ( $sympath && false !== strpos( $file_path, $sympath ) ) { // File is in the uploads' folder but in symlinked directory, so we need to create the correct URL.
					// Set the URL for the uploads' folder.
					$file_path = str_replace( str_replace( DIRECTORY_SEPARATOR, '/', trailingslashit( $sympath ) ), str_replace( DIRECTORY_SEPARATOR, '/', trailingslashit( $uploads_dir['baseurl'] ) ), $file_path );
				} else { // This is the case if the file is not located in the uploads' folder.
					// Ensure we have a valid URL, not a file path.
					$scheme = wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
					// At this point the $correct_path should have a value of the file path as the verification was made prior to this check
					// If there are symbolik links the return of the function will be an URL, so the last replace will not be taken into consideration.
					$file_path = download_monitor()->service( 'file_manager' )->check_symbolic_links( $file_path, true );
					$file_path = str_replace( trailingslashit( $correct_path ), site_url( '/', $scheme ), $file_path );
				}

				// We need to rawurlencode in case there are unicode characters in the file name
				// and to prevent white space from being converted to + sign. Only do this for non-remote files
				// as remote files already have encoded url.
				// Get file name.
				$file_name = DLM_Utils::basename( $file_path );

				if ( strstr( $file_name, '?' ) ) {
					$file_name = current( explode( '?', $file_name ) );
				}

				$file_path = str_replace( $file_name, rawurlencode( $file_name ), $file_path );
			}

			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Redirect: ' . $file_path );
				exit;
			}

			header( 'X-Robots-Tag: noindex, nofollow', true );
			header( 'Location: ' . $file_path );
			exit;
		}

		$this->download_headers( $file_path, $download, $version, $remote_file );

		do_action( 'dlm_start_download_process', $download, $version, $file_path, $remote_file );

		if ( '1' === get_option( 'dlm_xsendfile_enabled' ) ) {
			if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules() ) ) {
				if ( ! $this->check_for_xhr() ) {
					$this->dlm_logging->log( $download, $version, 'completed', false, $referrer );
				}

				header( "X-Sendfile: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {

				if ( ! $this->check_for_xhr() ) {
					$this->dlm_logging->log( $download, $version, 'completed', false, $referrer );
				}

				header( "X-LIGHTTPD-send-file: $file_path" );
				exit;

			} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
				// Log this way as the js doesn't know who the download_id and version_id is
				$this->dlm_logging->log( $download, $version, 'completed', false, $referrer );

				// At this point the $correct_path should have a value of the file path as the verification was made prior to this check
				// If there are symbolik links the return of the function will be an URL, so the last replace will not be taken into consideration.
				$file_path = download_monitor()->service( 'file_manager' )->check_symbolic_links( $file_path, true );
				$file_path = str_replace( trailingslashit( $correct_path ), '', $file_path );

				header( "X-Accel-Redirect: /$file_path" );
				exit;
			}
		}

		// multipart-download and download resuming support - http://www.phpgang.com/force-to-download-a-file-in-php_112.html.
		if ( isset( $_SERVER['HTTP_RANGE'] ) && $version->get_filesize() ) {
			// phpcs:ignore
			list( $a, $range ) = explode( "=", $_SERVER['HTTP_RANGE'], 2 );

			list( $range ) = explode( ",", $range, 2 );
			list( $range, $range_end ) = explode( "-", $range );
			$range              = intval( $range );
			$range_end_modified = false;

			if ( ! $range_end || $range_end > $version->get_filesize() ) {
				$range_end          = $version->get_filesize() - 1;
				$range_end_modified = true;
			} else {
				$range_end = intval( $range_end );
			}

			if ( $range_end_modified ) {
				$new_length = ( $range_end - $range ) + 1;
			} else {
				$new_length = $range_end - $range;
			}

			header( $_SERVER['SERVER_PROTOCOL'] . ' 206 Partial Content' );
			header( "Content-Length: $new_length" );
			header( "Content-Range: bytes {$range}-{$range_end}/{$version->get_filesize()}" );

		} else {
			$range = false;
		}

		// Adding contents to an object will trigger error on big files.
		if ( $this->readfile_chunked( $file_path, false, $range ) ) {
			if ( ! $this->check_for_xhr() ) {
				$this->dlm_logging->log( $download, $version, 'completed', false, $referrer );
			}
		} elseif ( $remote_file ) {
			// Redirect - we can't track if this completes or not.
			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Redirect: ' . $file_path );
				exit;
			}

			header( 'Location: ' . $file_path );
			$this->dlm_logging->log( $download, $version, 'redirected', false, $referrer );

		} else {

			if ( $this->check_for_xhr() ) {
				header( 'X-DLM-Error: ' . esc_html__( 'File not found.', 'download-monitor' ) );
				$restriction_type = 'file_not_found';
				$this->set_no_access_modal( __( 'File not found.', 'download-monitor' ), $download, $restriction_type );
				exit;
			}

			$this->dlm_logging->log( $download, $version, 'failed', false, $referrer );
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
	 * @param string               $file_path
	 * @param DLM_Download         $download
	 * @param DLM_Download_Version $version
	 */
	private function download_headers( $file_path, $download, $version, $remote_file ) {

		// Get Mime Type
		$mime_type = 'application/octet-stream';

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
		// We use this method to encode the filename so that file names with characters like
		// chinese or persian can be named correctly after the download in Safari.
		$file_name = rawurlencode( sanitize_file_name( $file_name ) );
		if ( $this->check_for_xhr() ) {
			$headers['Content-Disposition'] = "attachment; filename=\"{$file_name}\";";
			$headers['X-DLM-File-Name']       = "{$file_name}";
		} else {
			$headers['Content-Disposition'] = "attachment; filename*=UTF-8''{$file_name};";
		}

		$headers['X-Robots-Tag']              = 'noindex, nofollow';
		$headers['Content-Type']              = $mime_type;
		$headers['Content-Description']       = 'File Transfer';
		$headers['Content-Transfer-Encoding'] = 'binary';

		if ( $remote_file ) {
			$file = wp_remote_head( $file_path );
			if ( ! is_wp_error( $file ) && ! empty( $file['headers']['content-length'] ) ) {
				$file_size = $file['headers']['content-length'];
			}
		} else {
			$file_size = filesize( $file_path );
		}

		if ( isset( $file_size ) && $file_size ) {
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
	 * Set required XHR download headers
	 *
	 * @param DLM_Download         $download DLM Download object.
	 * @param DLM_Download_Version $version DLN Version object.
	 */
	private function set_required_xhr_headers( $download, $version ) {

		$headers = array();

		$headers['X-DLM-Download-ID'] = $download->get_id();
		$headers['X-DLM-Version-ID']  = $version->get_id();
		$headers['X-DLM-Nonce']       = wp_create_nonce( 'dlm_ajax_nonce' );

		foreach ( $headers as $key => $value ) {
			header( $key . ': ' . $value );
		}
	}

	/**
	 * Set extra XHR download headers
	 *
	 * @param DLM_Download $download DLM Download object.
	 * @param DLM_Download_Version $version DLN Version object.
	 * @param string $file_path The file path.
	 */
	private function set_extra_xhr_headers( $file_path, $download, $version ) {

		$headers = apply_filters( 'dlm_xhr_download_headers', array(), $file_path, $download, $version, $_REQUEST );

		if ( ! empty( $headers ) ) {
			foreach ( $headers as $key => $value ) {
				header( $key . ': ' . $value );
			}
		}
	}

	/**
	 * readfile_chunked
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @access   public
	 *
	 * @param    string  $file
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

	/**
	 * Check if this is an XHR request or not
	 *
	 * @return bool
	 */
	private function check_for_xhr() {
		return defined( 'DLM_DOING_XHR' ) && DLM_DOING_XHR;
	}

	/**
	 * Set headers for Modal opening
	 *
	 * @param string $text The text to be displayed.
	 * @param object $download The download object.
	 * @param string $restriction_type The restriction type.
	 *
	 * @return void
	 * @since 4.7.4
	 */
	public function set_no_access_modal( $text, $download, $restriction_type ) {
		$access_modal = absint( get_option( 'dlm_no_access_modal', 0 ) );

		header( 'X-DLM-No-Access: true' );
		header( 'X-DLM-No-Access-Modal: ' . apply_filters( 'do_dlm_xhr_access_modal', $access_modal, $download ) );
		header( 'X-DLM-No-Access-Restriction: ' . $restriction_type );
		if ( ! empty( $text ) ) {
			header( 'X-DLM-No-Access-Modal-Text: ' . apply_filters( 'do_dlm_xhr_access_modal_text', $text, $download, $restriction_type ) );
		}
	}
}
