<?php
/**
 * Download No Access
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 5.0.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var DLM_Download $download */

/**
 * Hook: dlm_no_access_before_message
 * Adds possibility to add content before the no access message
 *
 * @param  DLM_Download  $download  The download
 */

do_action( 'dlm_no_access_before_message', $download );

if ( ! empty( $no_access_message ) ) {
	echo wp_kses_post( '<p>' . $no_access_message . '</p>' );
}

/**
 * Hook: dlm_no_access_after_message
 * Adds possibility to add content after the no access message
 *
 * @param  DLM_Download  $download  The download
 */
do_action( 'dlm_no_access_after_message', $download );
