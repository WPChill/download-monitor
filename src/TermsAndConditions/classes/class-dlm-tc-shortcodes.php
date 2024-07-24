<?php

class DLM_TC_Shortcodes {

	/**
	 * Register shortcode(s)
	 */
	public function register() {
		add_shortcode( 'dlm_tc_form', array( $this, 'term_and_conditions_form' ) );
	}

	/**
	 * Tweet button shortcode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function term_and_conditions_form( $atts ) {

		// check if download id is set
		if ( ! isset( $atts['id'] ) ) {
			return 'No <code>id</code> set';
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
		if ( apply_filters( 'dlm_skip_extension_' . DLM_TC_Constants::SLUG, false, $atts['id'] ) ) {
			return do_shortcode( '[download id="' . $atts['id'] . '"]' );
		}

		wp_enqueue_style( 'dlm-frontend' );

		// get download
		try {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $atts['id'] ) );

			// template handler
			$template_handler = new DLM_Template_Handler();

			// enqueue shrortcode JS
			DLM_TC_Assets::enqueue_shortcode_js();

			// unlock text
			$terms_page_id = get_option( 'dlm_tc_content_page', false );
			$unlock_text = apply_filters( 'dlm_tc_unlock_text', get_option( 'dlm_tc_text', __( 'I accept the terms & conditions', 'download-monitor' ) ), $download );

			if( $terms_page_id && 0 != $terms_page_id ){
				$terms_page  = '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" target="_blank">' . wp_kses_post( get_the_title( $terms_page_id ) ) . '</a>';
				$unlock_text = str_replace ( '%%terms_conditions%%', $terms_page, $unlock_text );
			}else{
				$unlock_text = str_replace ( '%%terms_conditions%%', '', $unlock_text );
			}

			// load template
			ob_start();
			$template_handler->get_template_part( 'tc-form', '', plugin_dir_path( DLM_Integrated_Terms_And_Conditions::get_plugin_file() ) . 'templates/', array(
				'download'    => $download,
				'unlock_text' => $unlock_text,
				'tmpl'        => $template_handler
			) );

			return ob_get_clean();

		} catch ( Exception $exception ) {
			// no download with given ID found
		}

		return '';

	}

}