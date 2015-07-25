<?php
/**
 * Shows title only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<a class="download-link" title="<?php if ( $dlm_download->has_version_number() ) {
	printf( __( 'Version %s', 'download-monitor' ), $dlm_download->get_the_version_number() );
} ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<?php $dlm_download->the_title(); ?>
</a>