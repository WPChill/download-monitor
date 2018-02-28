<?php
/**
 * List of versions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var DLM_Download $dlm_download */

$versions = $dlm_download->get_versions();

if ( $versions ) : ?>
	<ul class="download-versions">
		<?php
		/** @var DLM_Download_Version $version */
		foreach ( $versions as $version ) {
			?>
			<li><a class="download-link"
			       title="<?php printf( _n( 'Downloaded 1 time', 'Downloaded %d times', $dlm_download->get_download_count(), 'download-monitor' ), $dlm_download->get_download_count() ) ?>"
			       href="<?php echo add_query_arg( 'v', $version->get_id(), $dlm_download->get_the_download_link() ); ?>" rel="nofollow">
					<?php echo $version->get_filename(); ?> <?php if ( $version->has_version_number() ) {
						echo '- ' . $version->get_version_number();
					} ?>
				</a></li>
		<?php
		}
		?>
	</ul>
<?php endif; ?>