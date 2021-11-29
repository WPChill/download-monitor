<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var WPChill\DownloadMonitor\Shop\Cart\Cart $cart
 * @var array $items
 * @var string $subtotal
 * @var string $total
 */
?>
<table cellpadding="0" cellspacing="0" border="0">
    <thead>
    <tr>
        <th><?php echo esc_html__( 'Product', 'download-monitor' ); ?></th>
        <th><?php echo esc_html__( 'Total', 'download-monitor' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php
	if ( ! empty( $items ) ) {
		/** @var \WPChill\DownloadMonitor\Shop\Cart\Item $item */
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
        <th><?php echo esc_html__( 'Subtotal', 'download-monitor' ); ?></th>
        <td><?php echo esc_html( $subtotal ); ?></td>
    </tr>
    <tr>
        <th><?php echo esc_html__( 'Total', 'download-monitor' ); ?></th>
        <td><?php echo esc_html( $total ); ?></td>
    </tr>
    </tfoot>
</table>