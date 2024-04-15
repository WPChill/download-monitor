<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_TC_Access_Manager {

	/**
	 * Setup class
	 */
	public function setup() {
		add_filter( 'dlm_can_download', array( $this, 'check_access' ), 30, 4 );
		add_filter( 'dlm_download_is_locked', array( $this, 'admin_list_table_locked_download' ), 10, 2 );
		add_filter( 'dlm_admin_sort_columns', array( $this, 'admin_list_table_sort_locked_download' ) );
	}

	/**
	 * Check if download is tc locked
	 *
	 * @param $download_id
	 *
	 * @return bool
	 */
	public static function is_tc_locked( $download_id ) {

		// get global lock option
		$global = apply_filters( 'dlm_tc_all_downloads_locked', get_option( 'dlm_tc_global', '0' ) );

		// get post meta lock
		$tc_locked = get_post_meta( $download_id, DLM_TC_Constants::META_LOCKED_KEY, true );

		return ( 'yes' == $tc_locked || '1' == $global );
	}

	/**
	 * Check if requester has access to download
	 *
	 * @param bool $has_access
	 * @param DLM_Download $download
	 * @param string $version
	 *
	 * @return bool
	 */
	public function check_access( $has_access, $download, $version, $post_data = null ) {

		/**
		 * Filter to skip the check access of terms and conditions.
		 * We pass the extension slug because this is a general filter, and we want to make sure we only skip the check for certain extensions.
		 *
		 * @hook  dlm_skip_extension
		 *
		 * @param  int     $download_id  The download ID.
		 * @param  string  $plugin_slug  The plugin slug.
		 *
		 * @since 4.1.3
		 *
		 * @hook  dlm_skip_access_check
		 */
		if ( apply_filters( 'dlm_skip_extension', false, $download->get_id(), DLM_TC_Constants::SLUG ) ) {
			return $has_access;
		}

		if ( ! self::is_tc_locked( $download->get_id() ) ) {
			return $has_access;
		}

		if ( ! isset( $post_data ) ) {
			$post_data = $_POST;
		}

		if ( ! isset( $post_data['tc_accepted'] ) || '1' !== $post_data['tc_accepted'] ) {
			if ( ! isset( $_COOKIE[ 'dlm_tc_access_' . $download->get_id() ] ) ) {
				$has_access = false;
			} else {
				$cookie_data = json_decode( base64_decode( $_COOKIE[ 'dlm_tc_access_' . $download->get_id() ] ), true );
				if ( empty( $cookie_data['hash'] ) || md5( $download->get_id() . DLM_Utils::get_visitor_ip() ) !== $cookie_data['hash'] ) {
					$has_access = false;
				}
			}
		} else {
			setcookie(
				'dlm_tc_access_' . $download->get_id(),
				base64_encode(
					json_encode(
						array(
							'hash' => md5( $download->get_id() . DLM_Utils::get_visitor_ip() )
						)
					)
				),
				time() + 300, COOKIEPATH, COOKIE_DOMAIN, false, true );
		}

		if ( ! $has_access && get_option( 'dlm_no_access_modal', false ) && apply_filters( 'do_dlm_xhr_access_modal', true, $download ) && defined( 'DLM_DOING_XHR' ) && DLM_DOING_XHR ) {

			header_remove( 'X-dlm-no-waypoints' );

			$restriction_type = 'dlm-terms-conditions-modal';

			header( 'X-DLM-TC-redirect: true' );
			header( 'X-DLM-No-Access: true' );
			header( 'X-DLM-No-Access-Modal: true' );
			header( 'X-DLM-No-Access-Restriction: ' . $restriction_type );
			header( 'X-DLM-Nonce: ' . wp_create_nonce( 'dlm_ajax_nonce' ) );
			header( 'X-DLM-TC-Required: true' );
			header( 'X-DLM-Download-ID: ' . absint( $download->get_id() ) );
			exit;
		}

		return $has_access;
	}

     /**
	 * Check if the download post is locked for the admin downloads list table
	 *
	 * @param bool $is_locked
	 * 
	 * @param DLM_Download $download
	 * 
	 * @return bool
	 */
	public function admin_list_table_locked_download( $is_locked, $download ){

		if ( 'yes' == get_post_meta( $download->get_id(), DLM_TC_Constants::META_LOCKED_KEY, true ) ) {
			return true;
		}

		return $is_locked;
	}

	/**
	 * Add meta query key to sort by locked downloads in admin list table
	 *
	 * @param array $vars
	 * 
	 * @return array
	 * @since
	 */
	public function admin_list_table_sort_locked_download( $vars ) {
		if ( isset( $_GET['post_type'] ) && isset( $_GET['orderby'] ) && 'locked_download' === $_GET['orderby'] && 'dlm_download' === $_GET['post_type'] ) {
			$vars['meta_query'][] = array( 'key' => DLM_TC_Constants::META_LOCKED_KEY );
		}

		return $vars;
	}
}