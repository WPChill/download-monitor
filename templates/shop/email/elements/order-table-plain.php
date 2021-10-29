<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( count( $items ) > 0 ) : ?>
	<?php foreach ( $items as $item ) : ?>
		<?php echo esc_html( $item['key'] ); ?>: <?php echo esc_html( $item['value'] ) . PHP_EOL; ?>
	<?php endforeach; ?>
<?php endif; ?>