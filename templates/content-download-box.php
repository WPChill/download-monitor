<?php
/**
 * Detailed download output
 */

global $dlm_download;
?>
<aside class="download-box">

	<?php $dlm_download->the_image(); ?>

	<div class="download-count"><?php printf( _n( '1 download', '%d downloads', $dlm_download->get_the_download_count(), 'download-monitor' ), $dlm_download->get_the_download_count() ) ?></div>

	<div class="download-box-content">

		<h1><?php $dlm_download->the_title(); ?></h1>

		<?php $dlm_download->the_short_description(); ?>

		<a class="download-button" title="<?php if ( $dlm_download->has_version_number() ) printf( __( 'Version %s', 'download-monitor' ), $dlm_download->get_the_version_number() ); ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
			<?php _e( 'Download File', 'download-monitor' ); ?>
			<small><?php $dlm_download->the_filename(); ?> &ndash; <?php $dlm_download->the_filesize(); ?></small>
		</a>

	</div>
</aside>


