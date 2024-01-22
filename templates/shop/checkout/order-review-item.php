<?php
/**
 * Order review item template
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var array $item */
?>
<tr>
	<td><?php echo esc_html( $item['label'] ); ?></td>
	<td><?php echo esc_html( $item['subtotal'] ); ?></td>
</tr>