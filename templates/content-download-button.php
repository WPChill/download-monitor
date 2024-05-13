<?php
/**
 * Download button
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

// Assign the template to a variable for easier access.
$template = __FILE__;

// This is a fix for the Gutenberg block to ensure the added classes are loaded.
if ( ! empty( $dlm_attributes['className'] ) ) {
	$attributes['link_attributes']['class'][] = $dlm_attributes['className'];
}

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
	printf( esc_html__( 'Download &ldquo;%s&rdquo;', 'download-monitor' ), wp_kses_post( $dlm_download->get_title() ) ); ?>
	<small><?php
		echo esc_html( $dlm_download->get_version()->get_filename() ); ?>
		&ndash; <?php
		printf( esc_html( _n( 'Downloaded 1 time', 'Downloaded %d times', $dlm_download->get_download_count(), 'download-monitor' ) ), absint( $dlm_download->get_download_count() ) ) ?>
		&ndash; <?php
		echo esc_html( $dlm_download->get_version()->get_filesize_formatted() ); ?></small>
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
