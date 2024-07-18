<?php

class DLM_TC_Assets {

	private static $shortcode_assets_enqueued = false;

	/**
	 * Frontend shortcode JS
	 */
	public static function enqueue_shortcode_js() {
		// only include once per page
		if ( true === self::$shortcode_assets_enqueued ) {
			return;
		}
		self::$shortcode_assets_enqueued = true;
		$dependencies                    = array();
		// Let's check if DLM grater or equal to 4.6.0, so we know if we need to add depency.
		if ( class_exists( 'DLM_Constants' ) ) {
			if ( method_exists( 'WP_DLM', 'do_xhr' ) && WP_DLM::do_xhr() ) {
				$dependencies = array( 'jquery', 'dlm-xhr' );
			} else {
				$dependencies = array( 'jquery' );
			}

			// enqueue listings script.
			wp_enqueue_script(
				'dlm_tl_js',
				plugins_url( '/assets/js/dlm-terms-and-conditions' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', DLM_Integrated_Terms_And_Conditions::get_plugin_file() ),
				$dependencies,
				DLM_VERSION,
				true
			);
		}
	}
}
