<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/** @var \Never5\DownloadMonitor\Ecommerce\Checkout\PaymentGateway\Manager $pgm Payment Gateway Manager */
$pgm              = \Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service( 'payment_gateway' );
$payment_gateways = $pgm->get_enabled_gateways();

if ( ! empty( $payment_gateways ) ) {
	?>
    <ul>
		<?php
		foreach ( $payment_gateways as $gateway ) {
			download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/checkout/payment-gateway', '', '', array(
				'cart'    => $cart,
				'gateway' => $gateway
			) );
		}
		?>
    </ul>
	<?php
}