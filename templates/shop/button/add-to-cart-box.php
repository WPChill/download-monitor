<?php
/**
 * Add to cart button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \Never5\DownloadMonitor\Shop\DownloadProduct\DownloadProduct $download */
/** @var string $atc_url */
?>
<aside class="download-box">

	<?php $download->the_image(); ?>

    <div class="download-count"><?php echo dlm_format_money( $download->get_price() ); ?></div>

    <div class="download-box-content">

        <h1><?php $download->the_title(); ?></h1>

		<?php $download->the_excerpt(); ?>

        <a class="download-button" title="<?php _e( 'Purchase Now', 'download-monitor' ); ?>" href="<?php echo $atc_url; ?>"
           rel="nofollow">
			<?php _e( 'Purchase Now', 'download-monitor' ); ?>
        </a>

    </div>
</aside>