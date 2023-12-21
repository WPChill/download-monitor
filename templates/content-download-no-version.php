<?php
/**
 * Default output for a download via the [download] shortcode
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<a data-e-Disable-Page-Transition="true" class="download-link<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?>" title="<?php echo esc_attr__( 'Please set a version in your WordPress admin', 'download-monitor' ); ?>" href="#" rel="nofollow">
	"<?php $dlm_download->the_title(); ?>" <strong><?php echo esc_html__( 'has no version set!', 'download-monitor' ); ?></strong>
</a>