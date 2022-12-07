<?php
/**
 * Default output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! isset( $dlm_download ) || ! $dlm_download ) {
	return esc_html__( 'No download found', 'download-monitor' );
}

/** @var DLM_Download $dlm_download */
/** @var Attributes $dlm_attributes */
?>
<a class="download-link<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?>" title="
<?php
if ( $dlm_download->get_version()->has_version_number() ) {
		printf( esc_html__( 'Version %s', 'download-monitor' ), esc_html( $dlm_download->get_version()->get_version_number() ) );
}
?>
	" href="<?php esc_url( $dlm_download->the_download_link() ); ?>" rel="nofollow">
	<?php $dlm_download->the_title(); ?>
	(<?php printf( esc_html( _n( '1 download', '%d downloads', $dlm_download->get_download_count(), 'download-monitor' ) ), esc_html( $dlm_download->get_download_count() ) ); ?>)
</a>
