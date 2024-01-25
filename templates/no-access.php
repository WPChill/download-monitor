<?php
/**
 * Download No Access
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var DLM_Download $download */

?>

<?php
/**
 * Hook: dlm_no_access_before_message
 * Adds possibility to add content before the no access message
 *
 * @param  DLM_Download  $download  The download
 */
do_action( 'dlm_no_access_before_message', $download ); ?>
<?php
if ( ! empty( $no_access_message ) ) : ?>

	<p><?php
		echo wp_kses_post( $no_access_message ); ?></p>
<?php
endif; ?>

<?php
/**
 * Hook: dlm_no_access_after_message
 * Adds possibility to add content after the no access message
 *
 * @param  DLM_Download  $download  The download
 */
do_action( 'dlm_no_access_after_message', $download ); ?>
