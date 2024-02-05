<?php
/**
 * List of versions
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @version 4.9.6
 *
 * @var DLM_Download       $dlm_download   The download object.
 * @var Attributes         $dlm_attributes The shortcode attributes.
 * @var TemplateAttributes $attributes     The template attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! isset( $dlm_download ) || ! $dlm_download ) {
	return esc_html__( 'No download found', 'download-monitor' );
}

// Assign the template file to a variable for easier access.
$template = __FILE__;

$versions = $dlm_download->get_versions();

// This is a fix for the Gutenberg block to ensure the added classes are loaded.
if ( ! empty( $dlm_attributes['className'] ) ) {
	$attributes['link_attributes']['class'][] = $dlm_attributes['className'];
}

if ( $versions ) : ?>
	<ul class="download-versions<?php
	echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : ''; ?>">
		<?php
		/** @var DLM_Download_Version $version */
		foreach ( $versions as $version ) {
			// set loop version as current version
			$dlm_download->set_version( $version );
			$attributes['link_attributes']['href'] = $dlm_download->get_the_download_link();
			?>
			<li>
				<?php

				/**
				 * Hook: dlm_template_content_before_link.
				 * Add possibility to add content before the link.
				 *
				 * @param  DLM_Download  $dlm_download  The download object.
				 * @param  array         $attributes    The template attributes.
				 * @param  string        $template      The template file.
				 *
				 * @since 4.9.6
				 *
				 */
				do_action( 'dlm_template_content_before_link', $dlm_download, $attributes, $template );
				?>
				<a <?php
				echo
				DLM_Utils::generate_attributes( $attributes['link_attributes'] ) // phpcs:ignore WordPress.Security.EscapeOutput ?> >
					<?php
					echo esc_html( $version->get_filename() ); ?><?php
					if ( $version->has_version_number() ) {
						echo '- ' . esc_html( $version->get_version_number() );
					} ?>
				</a>
				<?php

				/**
				 * Hook: dlm_template_content_after_link.
				 * Add possibility to add content after the link.
				 *
				 * @param  DLM_Download  $dlm_download  The download object.
				 * @param  array         $attributes    The template attributes.
				 * @param  string        $template      The template file.
				 *
				 * @since 4.9.6
				 *
				 */
				do_action( 'dlm_template_content_after_link', $dlm_download, $attributes, $template );
				?>
			</li>
			<?php
		}
		?>
	</ul>
<?php
endif; ?>