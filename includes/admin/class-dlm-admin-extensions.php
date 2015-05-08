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

		// Allow user to reload extensions
		if ( isset( $_GET['dlm-force-recheck'] ) ) {
			delete_transient( 'dlm_extension_json' );
		}

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
		<div class="wrap dlm_extensions_wrap">
			<div class="icon32 icon32-posts-dlm_download" id="icon-edit"><br/></div>
			<h2><?php _e( 'Download Monitor Extensions', 'download-monitor' ); ?> <a href="<?php echo add_query_arg( 'dlm-force-recheck', '1', admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ) ); ?>" class="button dlm-reload-button">Reload Extensions</a></h2>
			<?php

			if ( false !== $extension_json ) {

				// Get all extensions
				$response = json_decode( $extension_json );

				// Display message if it's there
				if ( isset( $response->message ) && '' !== $response->message ) {
					echo '<div id="message" class="updated">' . $response->message . '</div>' . PHP_EOL;
				}

				if ( count( $response ) > 0 && isset( $response->extensions ) && count( $response->extensions ) > 0 ) {

					// Extensions
					$extensions = $response->extensions;

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

					echo '<p>' . sprintf( __( 'Extend Download Monitor with its powerful free and paid extensions. %sClick here to browse all extensions%s', 'download-monitor' ), '<a href="https://www.download-monitor.com/extensions/?utm_source=plugin&utm_medium=link&utm_campaign=extensions-top" target="_blank">', '</a>' ) . '</p>' . PHP_EOL;
					?>
					<h2 class="nav-tab-wrapper">
						<a href="#available-extensions" class="nav-tab nav-tab-active">Available Extensions</a>
						<?php if ( count( $installed_extensions ) > 0 ) { ?><a href="#installed-extensions" class="nav-tab">Installed Extensions</a><?php } ?>
					</h2>
					<?php


					// Available Extensions
					if ( count( $extensions ) > 0 ) {

						echo '<div id="available-extensions" class="settings_panel">' . PHP_EOL;
						echo '<div class="theme-browser dlm_extensions">';

						foreach ( $extensions as $extension ) {

							$sale = false;
							if ( $extension->price > 0 ) {
								$price_display = '$' . $extension->price;
								if ( '' != $extension->sale_price && $extension->sale_price > 0 ) {
									$price_display = '<strike>$' . $extension->price . '</strike> $' . $extension->sale_price;
									$sale = true;
								}
							} else {
								$price_display = 'FREE';
							}

							//$price_display = ( ( $extension->price > 0 ) ? '$' . $extension->price : 'FREE' );

							echo '<div class="theme dlm_extension">';
								echo '<a href="' . $extension->url . '?utm_source=plugin&utm_medium=extension-block&utm_campaign=' . $extension->name . '" target="_blank">';
									echo '<div class="dlm_extension_img_wrapper"><img src="' . $extension->image . '" alt="' . $extension->name . '" /></div>' . PHP_EOL;
										echo '<h3>' . $extension->name . '</h3>' . PHP_EOL;
										echo '<p class="extension-desc">' . $extension->desc . '</p>';
										echo '<div class="product_footer">';
											echo '<span class="loop_price' . ( ( $sale ) ? ' sale' : '' ) . '">' . $price_display . '</span>';
											echo '<span class="loop_more">Get This Extension</span>';
										echo '</div>';
								echo '</a>';
							echo '</div>';
						}

						echo '</div>';
						echo '</div>';


					}else if ( count( $installed_extensions ) > 0 ) {
						echo '<p>Wow, looks like you installed all our extensions. Thanks, you rock!</p>';
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
										echo '<a href="javascript:;" class="button button-primary">' . ( ( $license->is_active() ) ? 'Deactivate' : 'Activate' ) . '</a>';
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