<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( count( $items ) > 0 ) : ?>
	<?php foreach ( $items as $item ) : ?>
		<?php echo $item['label']; ?> ( <?php echo $item['version']; ?> ): <?php echo $item['download_url'] . PHP_EOL; ?>
	<?php endforeach; ?>
<?php endif; ?>