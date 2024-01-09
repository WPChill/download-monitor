<?php
/**
 * The template for displaying single products
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version     4.9.6
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \WPChill\DownloadMonitor\Shop\Product\Product $product */

/**
 * dlm_before_single_product hook
 */
do_action( 'dlm_before_single_product', $product );
?>
	<div class="dlm-product">
		<p><?php echo do_shortcode( $product->get_content() ); ?></p>
		<?php echo do_shortcode( sprintf( '[dlm_buy id="%s"]', intval( $product->get_id() ) ) ); ?>
	</div>
<?php do_action( 'dlm_after_single_product', $product ); ?>