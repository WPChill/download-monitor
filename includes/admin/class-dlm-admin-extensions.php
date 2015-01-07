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

		// Load extension json
		if ( false === ( $extension_json = get_transient( 'dlm_extension_json' ) ) ) {

			// Extension request
			$extension_request = wp_remote_get( 'https://download-monitor.com/?dlm-extensions=true' );

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

				// Get all extensions
				$extensions = json_decode( $extension_json );

				if ( count( $extensions ) > 0 ) {

					// Get products
					$products = DLM_Product_Manager::get()->get_products();

					// Loop through extensions
					$installed_extensions = array();

					foreach ( $extensions as $extension_key => $extension ) {
						if ( isset( $products[ $extension->product_id ] ) ) {
							$installed_extensions[] = $extension;
							unset( $extensions[ $extension_key ] );
						}
					}


					echo "<p>Extend Download Monitor with it's powerful free and paid extensions.</p>" . PHP_EOL;
					?>
					<h2 class="nav-tab-wrapper">
						<a href="#available-extensions" class="nav-tab nav-tab-active">Available Extensions</a>
						<a href="#installed-extensions" class="nav-tab">Installed Extensions</a>
					</h2>
					<?php


					// Available Extensions
					if ( count( $extensions ) > 0 ) {

						echo '<div id="available-extensions" class="settings_panel">' . PHP_EOL;
						echo '<div class="theme-browser dlm_extensions">';
						foreach ( $extensions as $extension ) {
							echo '<div class="theme dlm_extension">';
								echo '<a href="' . $extension->url . '" target="_blank">';
									echo '<div class="dlm_extension_img_wrapper"><img src="' . $extension->image . '" alt="' . $extension->name . '" /></div>' . PHP_EOL;
										echo '<h3>' . $extension->name . '</h3>' . PHP_EOL;
										echo '<p class="extension-desc">' . $extension->desc . '</p>';
										echo '<div class="product_footer">';
											echo '<span class="loop_price">' . ( ( $extension->price > 0 ) ? '$' . $extension->price : 'FREE' ) . '</span>';
											echo '<span class="loop_more">Get This Extension</span>';
										echo '</div>';
								echo '</a>';
							echo '</div>';
						}
						echo '</div>';
						echo '</div>';


					}

					// Installed Extensions
					if ( count( $installed_extensions ) > 0 ) {

						echo '<div id="installed-extensions" class="settings_panel">' . PHP_EOL;

							echo '<div class="theme-browser dlm_extensions">';
							foreach ( $installed_extensions as $extension ) {

								// Get the product
								$license = $products[ $extension->product_id ]->get_license();

								echo '<div class="theme dlm_extension">';

								echo '<div class="dlm_extension_img_wrapper"><img src="' . $extension->image . '" alt="' . $extension->name . '" /></div>' . PHP_EOL;
									echo '<h3>' . $extension->name . '</h3>' . PHP_EOL;

									echo '<div class="extension_license">' . PHP_EOL;
										echo '<p class="license-status' . ( ( $license->is_active() ) ? ' active' : '' ) . '">' . strtoupper( $license->get_status() ) . '</p>' . PHP_EOL;
										echo '<input type="hidden" id="dlm-ajax-nonce" value="' . wp_create_nonce( 'dlm-ajax-nonce' ) . '" />' . PHP_EOL;
										echo '<input type="hidden" id="status" value="' . $license->get_status() . '" />' . PHP_EOL;
										echo '<input type="hidden" id="product_id" value="' . $extension->product_id . '" />' . PHP_EOL;
										echo '<input type="text" name="key" id="key" value="' . $license->get_key() . '" placeholder="License Key"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />' . PHP_EOL;
										echo '<input type="text" name="email" id="email" value="' . $license->get_email() . '" placeholder="License Email"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />' . PHP_EOL;
										echo '<a href="javscript:;" class="button button-primary">' . ( ( $license->is_active() ) ? 'Deactivate' : 'Activate' ) . '</a>';
									echo '</div>' . PHP_EOL;

								echo '</div>';
							}
							echo '</div>';
						echo '</div>'.PHP_EOL;

					}

				}

			} else {
				echo "<p>Couldn't load extensions, please try again later.</p>" . PHP_EOL;
			}
			?>
		</div>
	<?php
	}
}