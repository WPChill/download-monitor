<?php

/**
 * Checkout error template
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var string $error */
?>

<div class="dlm-checkout-error">
	<img src="<?php echo esc_url( download_monitor()->get_plugin_url() ); ?>/assets/images/shop/icon-error.svg"
		 alt="<?php echo esc_html__( 'Checkout error', 'download-monitor' ); ?>" class="dlm-checkout-error-icon">
	<p><?php echo esc_html( $error ); ?></p>
</div>
