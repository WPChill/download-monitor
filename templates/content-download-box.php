<?php
/**
 * Detailed download output
 *
 *  More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
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
?>
<aside
	class="download-box<?php
	echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : ''; ?>">

	<?php
	$dlm_download->the_image(); ?>

	<div
		class="download-count"><?php
		printf( esc_attr( _n( '1 download', '%d downloads', $dlm_download->get_download_count(), 'download-monitor' ) ), esc_html( $dlm_download->get_download_count() ) ) ?></div>

	<div
		class="download-box-content">

		<h1><?php
			$dlm_download->the_title(); ?></h1>

		<?php
		$dlm_download->the_excerpt(); ?>
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
			echo esc_html__( 'Download File', 'download-monitor' ); ?>
			<small><?php
				echo esc_html( $dlm_download->get_version()->get_filename() ); ?>
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
	</div>
</aside>


