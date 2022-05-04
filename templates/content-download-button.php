<?php
/**
 * Download button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
$download_id = $dlm_download->get_id();
?>

<a class="aligncenter download-button download-button-<?php echo absint($download_id) ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<p>
		<?php printf( esc_html__( 'Download &ldquo;%s&rdquo;', 'download-monitor' ), wp_kses_post( $dlm_download->get_title() ) ); ?>
	</p>
	<small><?php echo esc_html( $dlm_download->get_version()->get_filename() ); ?> &ndash; <?php printf( esc_html(_n( 'Downloaded 1 time', 'Downloaded %d times', $dlm_download->get_download_count(), 'download-monitor' )), absint( $dlm_download->get_download_count() ) ) ?> &ndash; <?php echo esc_html( $dlm_download->get_version()->get_filesize_formatted() ); ?></small>
	<span class="progress">
		<span class="progress-inner"></span>
	</span>
</a>
