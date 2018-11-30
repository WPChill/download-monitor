<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \Never5\DownloadMonitor\Ecommerce\Order\Order $order */
?>
<div class="wrap dlm-order-details">

	<?php
	$table = new \Never5\DownloadMonitor\Ecommerce\Admin\OrderTable();
	$table->prepare_items();
	?>

    <h1><?php printf( __( 'Order Details #%s', 'download-monitor' ), $order->get_id() ); ?></h1>

    <div class="dlm-order-details-main">
        <div class="dlm-order-details-block dlm-order-details-order-items">
            <h2 class="dlm-order-details-block-title"><span><?php _e( 'Order Items', 'download-monitor'); ?></span></h2>
            <div class="dlm-order-details-block-inside">
                asdasd
            </div>
        </div>
    </div>

    <div class="dlm-order-details-side">
        <div class="dlm-order-details-block dlm-order-details-customer">
            <h2 class="dlm-order-details-block-title"><span><?php _e( 'Customer', 'download-monitor'); ?></span></h2>
            <div class="dlm-order-details-block-inside">
                asdasd
            </div>
        </div>
    </div>

</div>