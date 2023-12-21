<?php
/**
 * Download No Access
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var DLM_Download $download */

?>

<?php do_action( 'dlm_no_access_before_message', $download ); ?>
<?php if ( ! empty( $no_access_message ) ) : ?>

	<p><?php echo wp_kses_post( $no_access_message ); ?></p>
<?php endif; ?>

<?php do_action( 'dlm_no_access_after_message', $download ); ?>
