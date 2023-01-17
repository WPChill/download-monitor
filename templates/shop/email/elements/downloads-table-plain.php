<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var array $products */
?>
<?php if ( count( $products ) > 0 ) : ?>
	<?php foreach ( $products as $product ) : ?>
		-- <?php echo esc_html( $product['label'] ); ?> -- <?php echo PHP_EOL; ?>
		<?php if ( count( $product['downloads'] ) > 0 ) : ?>
			<?php foreach ( $product['downloads'] as $item ) : ?>
				<?php echo esc_html( $item['label'] ); ?> ( <?php echo esc_html( $item['version'] ); ?> ): <?php echo esc_url( $item['download_url'] ) . PHP_EOL; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
