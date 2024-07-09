<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DLM_License class
 *
 * @description This class is used to manage the license of the plugin.
 *
 * @since       5.0.0
 */
class DLM_License {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Primary class constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		// Add License tab in the Download Monitor's settings page.
		add_filter( 'dlm_settings', array( $this, 'add_license_tab' ), 90, 1 );
		// Add License tab content
		// Show the templates tab content.
		add_action( 'dlm_tab_section_content_license', array( $this, 'license_page' ) );
		add_filter( 'dlm_show_save_settings_button', array( $this, 'hide_save_button' ), 15, 3 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_License object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_License ) ) {
			self::$instance = new DLM_License();
		}

		return self::$instance;
	}

	/**
	 * Master license input.
	 *
	 * @since 5.0.0
	 */
	public function master_license() {
		$master_license        = json_decode( get_option( 'dlm_master_license', json_encode( array( 'email' => '', 'license_key' => '', 'status' => 'inactive' ) ) ), true );
		$expired_licenses      = array();
		$expired_licenses_text = '';

		if ( ! empty( $expired_licenses ) ) {
			$expired_licenses_text .= '<a href="https://www.download-monitor.com/my-account" target="_blank">' . esc_html__( 'renew your license', 'download-monitor' ) . '</a> ';
		}
		?>
		<div class='dlm-master-license'>
			<div class="dlm-master-license__wrapper">
				<div>
					<p>
						<?php
						esc_html_e( 'Master License', 'download-monitor' ); ?>
					</p>
					<p>
						<a href='#' target='_blank' id='dlm-forgot-license' data-nonce="<?php
						echo esc_attr( wp_create_nonce( 'dlm-ajax-nonce' ) ); ?>"><?php
							esc_html_e( 'Forgot your license?', 'download-monitor' ); ?></a>
					</p>
				</div>
				<div class="dlm-master-license__inputs">
					<div class="dlm-master-license-license-wrapper">
						<input type="text" id="dlm-master-license" name="dlm_master_license" value="<?php
						echo esc_attr( $master_license['license_key'] ) ?>" size="35" placeholder="<?php
						esc_attr_e( 'Main license', 'download-monitor' ); ?>">
					</div>
					<div class='dlm-master-license-email-wrapper'>
						<input type='email' id='dlm-master-license-email' name='dlm_master_license_email' value="<?php
						echo esc_attr( $master_license['email'] ) ?>" size='35' placeholder="<?php
						esc_attr_e( 'Email', 'download-monitor' ); ?>">
					</div>
					<div class='dlm-master-license__action_buttons'>
						<input type='hidden' value="<?php
						echo esc_attr( wp_create_nonce( 'dlm-ajax-nonce' ) ); ?>"/>
						<button class='button button-primary' id='dlm-master-license-btn' data-action="<?php
						echo ( 'inactive' === $master_license['status'] ) ? 'activate' : 'deactivate'; ?>"><?php
							( 'inactive' === $master_license['status'] ) ? esc_html_e( 'Activate', 'download-monitor' ) : esc_html_e( 'Deactivate', 'download-monitor' ); ?></button>
					</div>
				</div>
			</div>
			<p class='error-display'>&nbsp;</p>
			<?php

			if ( isset( $master_license['license_status'] ) && isset( $master_license['license_key'] ) && '' !== $master_license['license_key'] ) {
				if ( 'expired' === $master_license['license_status'] ) {
					// Output the expired message.
					?>
					<div class="dlm_license_error">
									<span><strong><?php
											echo sprintf( esc_html__( 'License expired, please %srenew%s.', 'download-monitor' ), '<a href="https://www.download-monitor.com/cart/?renew_license=' . esc_attr( $master_license['license_key'] ) . '&activation_email=' . esc_attr( $master_license['email'] ) . '" target="_blank">', '</a>' ); ?></strong></span><span> <?php
							esc_html_e( 'If you already renewed, please activate the license.', 'download-monitor' ) ?></span>
					</div>
					<?php
				} elseif ( 'invalid' === $master_license['license_status'] ) {
					// Output the invalid message.
					?>
					<div class='dlm_license_error'>
						&nbsp;<span class='dlm-red-text'><?php
							esc_html_e( 'Invalid license, please check your license key.', 'download-monitor' ); ?></span>
					</div>
					<?php
				}
			} elseif ( ! empty( $expired_licenses ) ) {
				?>
				<div class='dlm_license_error'>
								<span><strong><?php
										echo sprintf( esc_html__( 'You license has expired,  %s.', 'download-monitor' ), $expired_licenses_text ); ?></strong></span><span> <?php
						esc_html_e( 'If you already renewed, please activate the license.', 'download-monitor' ) ?></span>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Output installed extensions page
	 *
	 * @since 5.0.0
	 */
	public function license_page() {
		?>
		<div class="wrap dlm_extensions_wrap">
		<?php

		// Installed Extensions
		// WPChill Welcome Class.
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

		if ( ! class_exists( 'WPChill_Welcome' ) ) {
			return;
		}

		$extensions = DLM_Admin_Extensions::get_instance();
		$extensions->load_data();

		$installed_extensions = $extensions->get_available_extensions();
		$products             = $extensions->get_products();
		foreach ( $installed_extensions as $extension ) {
			$sl = get_option( $extension->product_id . '-license', false );

			if ( $sl && isset( $sl['license_status'] ) && 'expired' === $sl['license_status'] ) {
				$expired_licenses[ $sl['key'] ] = $sl['email'];
			}
		}

		echo '<div id="installed-extensions-licenses" class="settings_panel">';
		echo '<div class="dlm_extensions">';
		?>
		<div class="clear">
			<h2><?php
				esc_html_e( 'Main License', 'download-monitor' ); ?></h2>
			<?php
			$this->master_license();

			if ( ! empty( $installed_extensions ) && ! empty( $products ) ) {
			?>
			<!-- Let's display the extensions.  -->
			<h2><?php
				esc_html_e( 'Single extension licenses', 'download-monitor' ); ?></h2>
			<p class="description"><?php
				esc_html_e( 'Used if you have purchased a license for a single extension.', 'download-monitor' ); ?></p>
			<?php

			foreach ( $installed_extensions as $extension ) {
				if ( empty( $products[ $extension->product_id ] ) ) {
					continue;
				}
				// Get the product
				$license = $products[ $extension->product_id ]->get_license();
				echo '<div class="dlm_extension">';
				echo '<span class="dlm_extension__title">' . esc_html( $extension->name ) . '</span>';
				echo '<div class="extension_license">';
				echo '<input type="hidden" id="dlm-ajax-nonce" value="' . esc_attr( wp_create_nonce( 'dlm-ajax-nonce' ) ) . '" />';
				echo '<input type="hidden" id="status" value="' . esc_attr( $license->get_status() ) . '" />';
				echo '<input type="hidden" id="product_id" value="' . esc_attr( $extension->product_id ) . '" />';
				echo '<input type="text" name="key" id="key" value="' . esc_attr( $license->get_key() ) . '" placeholder="License Key"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />';
				echo '<input type="text" name="email" id="email" value="' . esc_attr( $license->get_email() ) . '" placeholder="License Email"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />';
				echo '<a href="javascript:;" class="button button-primary">' . ( ( $license->is_active() ) ? 'Deactivate' : 'Activate' ) . '</a>';
				echo '</div>';
				echo '<p class="license-status' . ( ( $license->is_active() ) ? ' active' : '' ) . '">' . esc_html( strtoupper( $license->get_status() ) ) . '</p>';
				echo '</div>';
			}
			}
			?><!-- end extensions display -->
		</div><!-- .block -->
		<?php
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Add the License tab
	 *
	 * @param  array  $settings  The settings array.
	 *
	 * @since 5.0.0
	 */
	public function add_license_tab( $settings ) {
		$settings['license'] = array(
			'title'    => __( 'License', 'download-monitor' ),
			'sections' => array(
				'license' => array(
					'title'  => __( 'License', 'download-monitor' ),
					'fields' => array(
						array(
							'name'     => '',
							'type'     => 'title',
							'title'    => __( '', 'download-monitor' ),
							'priority' => 30,
						),
					),
				),
			),
		);

		return $settings;
	}

	/**
	 * Hide the save button on the license page.
	 *
	 * @param  array  $settings  Array of settings.
	 *
	 * @return bool
	 * @since 5.0.0
	 */
	public function hide_save_button( $return, $settings, $active_section ) {
		if ( 'license' === $active_section && empty( $_GET['action'] ) ) {
			return false;
		}

		return $return;
	}
}
