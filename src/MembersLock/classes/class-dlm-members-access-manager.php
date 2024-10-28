<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_Members_Access_Manager
 *
 * Checks if the download is locked and if the user is logged in
 *
 * @since 5.0.13
 */
class DLM_Members_Access_Manager {

	/**
	 * Setup class
	 */
	public function setup() {
		add_filter( 'dlm_can_download', array( $this, 'check_access' ), 30, 2 );
	}

	/**
	 * Check if download is tc locked
	 *
	 * @param $download_id
	 *
	 * @return bool
	 *
	 * @since 5.0.13
	 */
	public static function is_members_locked( $download ) {
		return $download->is_members_only();
	}

	/**
	 * Check members only (hooked into dlm_can_download) checks if the download is members only and enfoces log in.
	 *
	 * Other plugins can use the 'dlm_can_download' filter directly to change access rights.
	 *
	 * @access public
	 *
	 * @param  boolean  $can_download
	 * @param  mixed    $download
	 *
	 * @return boolean
	 *
	 * @since 5.0.13
	 */
	public function check_access( $can_download, $download ) {
		if ( false === $can_download ) {
			if ( ! empty( $_SESSION['dlm_error_texts']['members_only'] ) ) {
				unset( $_SESSION['dlm_error_texts']['members_only'] );
			}
			return $can_download;
		}
		// Check if download is a 'members only' download
		if ( false !== $can_download && $download->is_members_only() ) {
			// Check if user is logged in
			if ( ! is_user_logged_in() ) {
				$_SESSION['dlm_error_texts'] = array(
					'members_only' => apply_filters( 'dlm_members_only_access_text', __( 'You\'ll need to log in to download the file.', 'download-monitor' ) ),
				);
				if ( get_option( 'dlm_no_access_modal', false ) && apply_filters( 'do_dlm_xhr_access_modal', true, $download ) && defined( 'DLM_DOING_XHR' ) && DLM_DOING_XHR ) {
					header_remove( 'X-dlm-no-waypoints' );

					$restriction_type = 'no_access_page';

					header( 'X-DLM-Members: true' );
					header( 'X-DLM-No-Access: true' );
					header( 'X-DLM-No-Access-Modal: true' );
					header( 'X-DLM-No-Access-Restriction: ' . $restriction_type );
					header( 'X-DLM-Nonce: ' . wp_create_nonce( 'dlm_ajax_nonce' ) );
					header( 'X-DLM-Members-Locked: true' );
					header( 'X-DLM-Download-ID: ' . absint( $download->get_id() ) );
					header( 'X-DLM-No-Access-Modal-Text: ' . __( 'Only members can download', 'download-monitor' ) );
					exit;
				}

				$can_download = false;
			} elseif ( is_multisite()
					&& ! is_user_member_of_blog(
						get_current_user_id(),
						get_current_blog_id()
					)
			) { // Check if it's a multisite and if user is member of blog
				$can_download = false;
			}
		}

		if ( $can_download && ! empty( $_SESSION['dlm_error_texts']['members_only'] ) ) {
			unset( $_SESSION['dlm_error_texts']['members_only'] );
		}

		return $can_download;
	}
}
