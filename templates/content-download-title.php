<?php
/**
 * Shows title only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<a class="download-link<?php echo ( !empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?>" title="<?php if ( $dlm_download->get_version()->has_version_number() ) {
	printf( esc_html__( 'Version %s', 'download-monitor' ), esc_html( $dlm_download->get_version()->get_version_number() ) );
} ?>" href="<?php esc_url( $dlm_download->the_download_link() ); ?>" rel="nofollow">
	<?php $dlm_download->the_title(); ?>
</a>