<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var WPChill\DownloadMonitor\Shop\Cart\Item\Item $item */
?>
<tr>
	<td><a href="<?php echo esc_url( add_query_arg( array( 'dlm-remove-from-cart' => $item->get_product_id() ), $url_cart ) ); ?>"
		   class="dlm-cart-remove-item"
		   aria-label="<?php echo esc_attr__( 'Remove this item from your cart', 'download-monitor' ); ?>">x</a></td>
	<td><?php echo esc_html( $item->get_label() ); ?></td>
	<td><?php echo esc_html( dlm_format_money( $item->get_subtotal() ) ); ?></td>
	<td><?php echo esc_html( $item->get_qty() ); ?></td>
	<td><?php echo esc_html( dlm_format_money( $item->get_total() ) ); ?></td>
</tr>
