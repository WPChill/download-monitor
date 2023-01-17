<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var WPChill\DownloadMonitor\Shop\Cart\Cart $cart */
?>
<table cellspacing="0" cellpadding="0" border="0">
    <tbody>
    <tr>
        <th><?php echo esc_html__( 'Subtotal', 'download-monitor' ); ?></th>
        <td><?php echo esc_html( dlm_format_money( $cart->get_subtotal() ) ); ?></td>
    </tr>
	<?php
	/**
	 * @todo [TAX] Implement taxes
	 */
	?>
	<?php
	/**
	 * @todo [COUPONS] Implement coupons
	 */
	?>
    <tr class="dlm-totals-last-row">
        <th><?php echo esc_html__( 'Total', 'download-monitor' ); ?></th>
        <td><?php echo esc_html( dlm_format_money( $cart->get_total() ) ); ?></td>
    </tr>
    </tbody>
</table>
