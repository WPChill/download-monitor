<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var array $item */
?>
<tr>
	<td><?php echo esc_html( $item['label'] ); ?></td>
	<td><?php echo esc_html( $item['subtotal'] ); ?></td>
</tr>