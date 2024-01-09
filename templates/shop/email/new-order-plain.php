<?php
/**
 * Empty cart page
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version     4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

Hey %FIRST_NAME%,

Thank you for your purchase, this email confirms your order.

Here's an overview of your files:

%DOWNLOADS_TABLE_PLAIN%

Many thanks,

Team %WEBSITE_NAME%
