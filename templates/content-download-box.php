<?php
/**
 * Detailed download output
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>

<aside class="download-box<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : '' ; ?>">

	<?php $dlm_download->the_image(); ?>

	<div
		class="download-count"><?php printf( esc_attr(_n( '1 download', '%d downloads', $dlm_download->get_download_count(), 'download-monitor' )), esc_html( $dlm_download->get_download_count() ) ) ?></div>

	<div class="download-box-content">

		<h1><?php $dlm_download->the_title(); ?></h1>

		<?php $dlm_download->the_excerpt(); ?>

		<a class="download-button" title="<?php if ( $dlm_download->get_version()->has_version_number() ) {
			printf( esc_html__( 'Version %s', 'download-monitor' ), esc_html( $dlm_download->get_version()->get_version_number() ) );
		} ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
			<?php echo esc_html__( 'Download File', 'download-monitor' ); ?>
			<small><?php echo esc_html( $dlm_download->get_version()->get_filename() ); ?> &ndash; <?php echo esc_html( $dlm_download->get_version()->get_filesize_formatted() ); ?></small>
		</a>

	</div>
</aside>


