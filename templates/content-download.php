<?php
/**
 * Default output for a download via the [download] shortcode
 */

global $dlm_download;
?>
<a class="download-link" title="<?php if ( $dlm_download->has_version_number() ) printf( __( 'Version %s', 'download-monitor' ), $dlm_download->get_the_version_number() ); ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
	<?php $dlm_download->the_title(); ?> (<?php printf( _n( '1 download', '%d downloads', $dlm_download->get_the_download_count(), 'download-monitor' ), $dlm_download->get_the_download_count() ) ?>)
</a>