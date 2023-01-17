<?php
/**
 * Default output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<a class="download-link<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?> filetype-icon <?php echo 'filetype-' . esc_html( $dlm_download->get_version()->get_filetype() ); ?>"
   title="<?php if ( $dlm_download->get_version()->has_version_number() ) {
	   printf( esc_html__( 'Version %s', 'download-monitor' ), esc_html( $dlm_download->get_version()->get_version_number() ) );
   } ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<?php echo esc_html( $dlm_download->get_version()->get_filename() ); ?>
	(<?php printf( esc_html( _n( '1 download', '%d downloads', $dlm_download->get_download_count(), 'download-monitor' ) ), esc_html( $dlm_download->get_download_count() ) ) ?>)
</a>