<?php
/**
 * Default output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<a class="download-link<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?>" title="<?php echo esc_attr__( 'Please set a version in your WordPress admin', 'download-monitor' ); ?>" href="#" rel="nofollow">
	"<?php $dlm_download->the_title(); ?>" <strong><?php echo esc_html__( 'has no version set!', 'download-monitor' ); ?></strong>
</a>