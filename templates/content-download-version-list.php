<?php
/**
 * List of versions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$versions = $dlm_download->get_versions();

if ( $versions ) : ?>
	<ul class="download-versions<?php echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : ''; ?>">
		<?php
		/** @var DLM_Download_Version $version */
		foreach ( $versions as $version ) {

		    // set loop version as current version
			$dlm_download->set_version( $version );
			?>
			<li><a class="download-link"
			       title="<?php printf( esc_attr(_n( 'Downloaded 1 time', 'Downloaded %d times', $dlm_download->get_download_count(), 'download-monitor' )), esc_html( $dlm_download->get_download_count() ) ) ?>"
			       href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
					<?php echo esc_html( $version->get_filename() ); ?> <?php if ( $version->has_version_number() ) {
						echo '- ' . esc_html( $version->get_version_number() );
					} ?>
				</a></li>
		<?php
		}
		?>
	</ul>
<?php endif; ?>