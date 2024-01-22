<?php

/**
 * Payment gateway template
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version     4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway $gateway */
?>
<li>
    <label for="dlm_gateway_<?php echo esc_attr( $gateway->get_id() ); ?>">
        <input type="radio" name="dlm_gateway" id="dlm_gateway_<?php echo esc_attr( $gateway->get_id() ); ?>"
               value="<?php echo esc_attr( $gateway->get_id() ); ?>" <?php checked( $default_gateway, $gateway->get_id() ); ?>/>
		<?php echo esc_html( $gateway->get_title() ); ?>
    </label>
    <div class="dlm_gateway_details">
		<?php
		$description = $gateway->get_description();
		if ( ! empty( $description ) ) {
			printf( "<p>%s</p>", esc_html( $description ) );
		}
		?>
    </div>
</li>