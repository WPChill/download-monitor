<?php
/**
 * Addons Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * DLM_Admin_Extensions Class
 */
class DLM_Admin_Extensions {

	/**
	 * Handles output of the reports page in admin.
	 */
	public function output() {

		delete_transient( 'dlm_extension_json' );

		// Load extension json
		if ( false === ( $extension_json = get_transient( 'dlm_extension_json' ) ) ) {

			// Extension request
			$extension_request = wp_remote_get( 'https://download-monitor.com/?dlm-extensions=true', array( 'sslverify' => false ) );

			if ( ! is_wp_error( $extension_request ) ) {

				// The extension json from server
				$extension_json = wp_remote_retrieve_body( $extension_request );

				// Set Transient
				set_transient( 'dlm_extension_json', $extension_json, DAY_IN_SECONDS );
			}
		}

		?>
		<div class="wrap dlm_addons_wrap">
			<div class="icon32 icon32-posts-dlm_download" id="icon-edit"><br/></div>
			<h2><?php _e( 'Download Monitor Extensions', 'download-monitor' ); ?></h2>
			<?php

			if ( false !== $extension_json ) {
				$extensions = json_decode( $extension_json );
				
				if ( count( $extensions ) > 0 ) {
					echo "<p>Extend Download Monitor with it's powerful free and paid extensions.</p>" . PHP_EOL;
					echo '<div class="theme-browser dlm_extensions">';
					foreach ( $extensions as $extension ) {
						echo '<div class="theme dlm_extension">';
							echo '<a href="' . $extension->url . '" target="_blank">';
								echo '<div class="dlm_extension_img_wrapper"><img src="' . $extension->image . '" alt="' . $extension->name . '" /></div>' . PHP_EOL;
								echo '<h3>' . $extension->name . '</h3>' . PHP_EOL;
								echo '<p>' . $extension->desc . '</p>';
								echo '<div class="product_footer">';
									echo '<span class="loop_price">' . ( ( $extension->price > 0 ) ? '$' . $extension->price : 'FREE' ) . '</span>';
									echo '<span class="loop_more">Get This Extension</span>';
								echo '</div>';
							echo '</a>';
						echo '</div>';
					}
					echo '</div>';
				}

			} else {
				echo "<p>Couldn't load extensions, please try again later.</p>" . PHP_EOL;
			}
			?>
		</div>
	<?php
	}
}