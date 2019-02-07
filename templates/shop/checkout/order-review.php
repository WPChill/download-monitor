<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var Never5\DownloadMonitor\Shop\Cart\Cart $cart
 */
?>
<table cellpadding="0" cellspacing="0" border="0">
    <thead>
    <tr>
        <th><?php _e( 'Product', 'download-monitor' ); ?></th>
        <th><?php _e( 'Total', 'download-monitor' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php
	$items = $cart->get_items();
	if ( ! empty( $items ) ) {
		/** @var \Never5\DownloadMonitor\Shop\Cart\Item $item */
		foreach ( $items as $item ) {
			download_monitor()->service( 'template_handler' )->get_template_part( 'shop/checkout/order-review-item', '', '', array(
				'item' => $item
			) );
		}
	}
	?>
    </tbody>
    <tfoot>
    <tr>
        <th><?php _e( 'Subtotal', 'download-monitor' ); ?></th>
        <td><?php echo dlm_format_money( $cart->get_subtotal() ); ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Total', 'download-monitor' ); ?></th>
        <td><?php echo dlm_format_money( $cart->get_total() ); ?></td>
    </tr>
    </tfoot>
</table>