<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var Never5\DownloadMonitor\Ecommerce\Cart\Item $item */
?>
<tr>
    <td><?php echo $item->get_label(); ?></td>
    <td><?php echo dlm_format_money( $item->get_subtotal() ); ?></td>
    <td><?php echo $item->get_qty(); ?></td>
    <td><?php echo dlm_format_money( $item->get_total() ); ?></td>
</tr>