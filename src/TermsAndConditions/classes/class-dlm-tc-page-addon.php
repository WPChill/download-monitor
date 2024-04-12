<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_TC_Page_Addon {

	private static $js_printed = false;

	public function setup() {
		// Hijack the page addon download button
		add_filter( 'dlm_page_addon_download_button', array( $this, 'page_addon_download_button' ), 10, 2 );
	}

	/**
	 * Hijack the [download] shortcode
	 *
	 * @param $content
	 * @param $download_id
	 *
	 * @return string
	 */
	public function page_addon_download_button( $content, $download_id ) {

		// access manager
		$access_manager = new DLM_TC_Access_Manager();

		try {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

			// only replace button with form if user has no access to download
			if ( false === $access_manager->check_access( true, $download, null ) ) {

				// Check if modal is enabled, and we are doing an XHR request, otherwise use shortcode as it will output Tailwind CSS template.
				if ( get_option( 'dlm_no_access_modal', false ) && apply_filters( 'do_dlm_xhr_access_modal', true, $download ) ) {
					$modal            = new DLM_TC_Modal();
					$hijacked_content = $modal->modal_content( $download_id );
				} else {
					$shortcode        = new DLM_TC_Shortcodes();
					$hijacked_content = $shortcode->term_and_conditions_form( array( 'id' => $download_id ) );
				}

				// Replace content if we've got content
				if ( '' !== $hijacked_content ) {
					$content = $hijacked_content;
				}
			}
		} catch ( Exception $exception ) {
			// no download found
		}

		// Return content
		return $content;
	}
}
