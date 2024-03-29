<?php
/**
 * Empty cart page
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version     4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<table cellpadding="0" cellspacing="0" border="0" class="dlm-order-table">
    <tbody>
	<?php if ( count( $items ) > 0 ) : ?>
		<?php foreach ( $items as $item ) : ?>
            <tr>
                <th><?php echo esc_html( $item['key'] ); ?></th>
                <td><?php echo esc_html( $item['value'] ); ?></td>
            </tr>
		<?php endforeach; ?>
	<?php endif; ?>
    </tbody>
</table>