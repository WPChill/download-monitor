<?php
/**
 * Checkout no access template
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<p><?php echo esc_html__( 'You have no access to this order.', 'download-monitor' ); ?></p>
