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

<?php if ( count( $items ) > 0 ) : ?>
	<?php foreach ( $items as $item ) : ?>
		<?php echo esc_html( $item['key'] ); ?>: <?php echo esc_html( $item['value'] ) . PHP_EOL; ?>
	<?php endforeach; ?>
<?php endif; ?>