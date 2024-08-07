<?php
/**
 * Add to cart button
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \WPChill\DownloadMonitor\Shop\Product\Product $product */
/** @var string $atc_url */
?>
<a class="aligncenter download-button" href="<?php
echo esc_url( $atc_url ); ?>" rel="nofollow">
	<?php
	printf( esc_html__( 'Purchase &ldquo;%s&rdquo;', 'download-monitor' ), esc_html( $product->get_title() ) ); ?>
	<small><?php
		echo esc_html( dlm_format_money( $product->get_price() ) ); ?>
		<?php
		$excerpt = $product->get_excerpt();
		if ( ! empty( $excerpt ) ) {
		?>
		- <?php
		$product->the_excerpt(); ?></small>
<?php
}
?>
</a>
