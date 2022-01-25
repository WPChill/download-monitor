<?php
/**
 * Add to cart button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \WPChill\DownloadMonitor\Shop\Product\Product $product */
/** @var string $atc_url */
?>
<aside class="download-box">

	<?php echo wp_kses_post( $product->get_image() ); ?>

    <div class="download-count"><?php echo esc_html( dlm_format_money( $product->get_price() ) ); ?></div>

    <div class="download-box-content">

        <h1><?php echo esc_html( $product->get_title() ); ?></h1>

		<p><?php $product->the_excerpt(); ?></p>

        <a class="download-button" title="<?php echo esc_html__( 'Purchase Now', 'download-monitor' ); ?>" href="<?php echo esc_url( $atc_url ); ?>"
           rel="nofollow">
			<?php echo esc_html__( 'Purchase Now', 'download-monitor' ); ?>
        </a>

    </div>
</aside>