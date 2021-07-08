<?php
/**
 * Download No Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


do_action( 'dlm_no_access_before_message', $download );
if ( ! empty( $no_access_message ) ) : ?>
	<p><?php echo $no_access_message; ?></p>
<?php endif; ?>

<?php do_action( 'dlm_no_access_after_message', $download ); ?>
