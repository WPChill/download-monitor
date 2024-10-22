<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_TC_Access_Manager {

	/**
	 * Setup class
	 */
	public function setup() {
		add_filter( 'dlm_can_download', array( $this, 'check_access' ), 140, 4 );
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
		// If the access is already denied, we don't need to check anything else, most probably
		// it is a different access restriction that is denying access.
		if ( false === $has_access ) {
			if ( ! empty( $_SESSION['dlm_error_texts']['terms_and_conditions'] ) ) {
				unset( $_SESSION['dlm_error_texts']['terms_and_conditions'] );
			}
			return $has_access;
		}

		/**
		 * Filter to skip the check access of terms and conditions.
		 * We pass the extension slug because this is a general filter, and we want to make sure we only skip the check for certain extensions.
		 *
		 * @hook  dlm_skip_extension
		 *
		 * @param  int     $download_id  The download ID.
		 * @param  string  $plugin_slug  The plugin slug.
		 *
		 * @since 5.0.0
		 *
		 * @hook  dlm_skip_access_check
		 */
		if ( apply_filters( 'dlm_skip_extension_' . DLM_TC_Constants::SLUG, false, $download->get_id() ) ) {
			return $has_access;
		}

		if ( ! self::is_tc_locked( $download->get_id() ) ) {
			if ( ! empty( $_SESSION['dlm_error_texts']['terms_and_conditions'] ) ) {
				unset( $_SESSION['dlm_error_texts']['terms_and_conditions'] );
			}
			return $has_access;
		}

		if ( ! isset( $post_data ) ) {
			$post_data = $_POST;
		}
		// New cookie management system, starting from DLM 4.9.6
		$cookie_manager = DLM_Cookie_Manager::get_instance();
		if ( ! isset( $post_data['tc_accepted'] ) || '1' !== $post_data['tc_accepted'] ) {
			$has_access = false;
			// Check for pre-DLM 4.9.5 set cookies
			$has_access = apply_filters( 'dlm_tc_cookie_access', $has_access, $download );
			$_SESSION['dlm_error_texts']['terms_and_conditions'] = apply_filters( 'dlm_terms_and_conditions_access_text', __( 'You must accept the terms and conditions to download this file.', 'download-monitor' ) );
			// New cookie management system check
			if ( $cookie_manager->check_cookie_meta( DLM_TC_Constants::COOKIE_META, $download->get_id() ) ) {
				if ( ! empty( $_SESSION['dlm_error_texts']['members_only'] ) ) {
					unset( $_SESSION['dlm_error_texts']['members_only'] );
				}
				$has_access = true;
			}
		} else {
			// User accepted terms and conditions, grant access
			$has_access = true;
			// Create cookie data
			$cookie_data = array(
				'expires' => time() + 300,
				'meta'    => array(
					array(
						DLM_TC_Constants::COOKIE_META => $download->get_id(),
					),
				),
			);
			// Set cookie
			$cookie_manager->set_cookie( $download, $cookie_data );
			if ( ! empty( $_SESSION['dlm_error_texts']['terms_and_conditions'] ) ) {
				unset( $_SESSION['dlm_error_texts']['terms_and_conditions'] );
			}
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
	public function admin_list_table_locked_download( $is_locked, $download ) {

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
	 * @since 5.0.0
	 */
	public function admin_list_table_sort_locked_download( $vars ) {
		if ( isset( $_GET['post_type'] ) && isset( $_GET['orderby'] ) && 'locked_download' === $_GET['orderby'] && 'dlm_download' === $_GET['post_type'] ) {
			$vars['meta_query'][] = array( 'key' => DLM_TC_Constants::META_LOCKED_KEY );
		}

		return $vars;
	}
}
