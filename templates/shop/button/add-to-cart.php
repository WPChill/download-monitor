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
<p><a class="aligncenter download-button" href="<?php echo $atc_url; ?>" rel="nofollow">
		<?php printf( __( 'Purchase &ldquo;%s&rdquo;', 'download-monitor' ), $download->get_title() ); ?>
        <small><?php echo dlm_format_money( $download->get_price() ); ?>
            - <?php _e( 'Instant Access!', 'download-monitor' ); ?></small>
    </a></p>