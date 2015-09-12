<?php
/**
 * Download No Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php do_action( 'dlm_no_access_before_message', $download ); ?>

<?php if ( ! empty( $no_access_message ) ) : ?>
	<p><?php echo $no_access_message; ?></p>
<?php endif; ?>

<?php do_action( 'dlm_no_access_after_message', $download ); ?>
