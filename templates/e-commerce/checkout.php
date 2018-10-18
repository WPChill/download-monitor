<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var Never5\DownloadMonitor\Ecommerce\Cart\Cart $cart
 * @var string $url_cart
 * @var string $url_checkout
 */

?>
<div class="dlm-checkout">
    <div class="dlm-checkout-billing">
        <h2><?php _e( 'Billing details', 'download-monitor' ); ?></h2>
		<?php dlm_checkout_fields(); ?>
    </div>
    <div class="dlm-checkout-order-review">
        <h2><?php _e( 'Your order', 'download-monitor' ); ?></h2>
		<?php
		download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/checkout/order-review', '', '', array(
			'cart'         => $cart,
			'url_checkout' => $url_checkout
		) );
		?>

        <div class="dlm-checkout-payment">
            <?php
            download_monitor()->service( 'template_handler' )->get_template_part( 'e-commerce/checkout/payment', '', '', array(
	            'cart'         => $cart,
	            'url_checkout' => $url_checkout
            ) );
            ?>
        </div>
    </div>
</div>