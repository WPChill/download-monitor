<?php

/**
 * Submit button template
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version     4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<input type="submit" name="dlm_checkout_submit" id="dlm_checkout_submit" value="<?php
echo esc_html__( 'Complete order', 'download-monitor' ); ?>"/>