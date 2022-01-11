<?php
/**
 * Download button
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>

<p>
	<a class="aligncenter download-button" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
		<?php printf( esc_html__( 'Download &ldquo;%s&rdquo;', 'download-monitor' ), wp_kses_post( $dlm_download->get_title() ) ); ?>
		<small><?php echo esc_html( $dlm_download->get_version()->get_filename() ); ?> &ndash; <?php printf( esc_html(_n( 'Downloaded 1 time', 'Downloaded %d times', $dlm_download->get_download_count(), 'download-monitor' )), absint( $dlm_download->get_download_count() ) ) ?> &ndash; <?php echo esc_html( $dlm_download->get_version()->get_filesize_formatted() ); ?></small>
	</a>
</p>