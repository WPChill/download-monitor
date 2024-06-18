<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_Frontend_Templates
 * Class used to handle frontend templates.
 *
 * @since 4.9.6
 */
class DLM_Frontend_Templates {

	/**
	 * Holds the class object.
	 *
	 * @since 4.9.6
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Class constructor
	 *
	 * @return void
	 * @since 4.9.6
	 */
	private function __construct() {
		// Set required hooks
		$this->set_hooks();
	}

	/**
	 * Sets the hooks
	 *
	 * @return void
	 * @since 4.9.6
	 */
	private function set_hooks() {
		add_filter( 'dlm_template_attributes', array( $this, 'add_template_attributes' ), 15, 3 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Frontend_Templates object.
	 * @since 4.9.6
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Frontend_Templates ) ) {
			self::$instance = new DLM_Frontend_Templates();
		}

		return self::$instance;
	}

	/**
	 * Adds the template attributes
	 *
	 * @param  array   $attributes  The attributes.
	 * @param  object  $download    The download object.
	 * @param  string  $template    The template to be used.
	 *
	 * @return array
	 * @since 4.9.6
	 */
	public function add_template_attributes( $attributes, $download, $template ) {
		switch ( $template ) {
			case 'box':
				$attributes['link_attributes']['class'][] = 'download-button';
				break;
			case 'button':
				$attributes['link_attributes']['class'][] = 'download-button';
				$attributes['link_attributes']['class'][] = 'aligncenter';
				break;
			case 'filename':
				$attributes['link_attributes']['class'][] = 'filetype-icon filetype-' . esc_attr( $download->get_version()->get_filetype() );
				break;
			case 'no-version':
				$attributes['link_attributes']['title']  = esc_html__( 'Please set a version in your WordPress admin', 'download-monitor' );
				$attributes['link_attributes']['href'] = '#';
				break;
			case 'title':
				break;
			case 'version-list':
				$attributes['link_attributes']['title'] = sprintf( esc_attr( _n( 'Downloaded 1 time', 'Downloaded %d times', $download->get_download_count(), 'download-monitor' ) ), esc_html( $download->get_download_count() ) );
				break;
		}
		$attributes['link_attributes']['data-redirect'] = 'false';
		// Check if the download is redirect only and if we need to open the download in a new tab
		if ( $download->is_redirect_only() && $download->is_new_tab() ) {
			// Add the data-redirect attribute to the link, so we can check it with JavaScript if we need to redirect the user
			$attributes['link_attributes']['data-redirect'] = 'true';
			if ( $download->is_new_tab() ) {
				// Add the target attribute to the link
				$attributes['link_attributes']['target'] = '_blank';
			}
		}

		return $attributes;
	}
}
