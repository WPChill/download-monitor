<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin class.
 */
class DLM_Admin_OptionsUpsells {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		/**
		 * @hook dlm_remove_upsells
		 *
		 * Remove upsells hook
		 * @since 4.9.4
		 *
		 * @hooked DLM_Upsells check_license_validity - 10
		 */
		if ( apply_filters( 'dlm_remove_upsells', false ) ) {
			return;
		}

		add_action( 'dlm_options_end', array( $this, 'add_upsells_products' ), 99 );
	}

    public function get_active_addons(){

        return DLM_Product_Manager::get()->get_products();
    }

    public function add_upsells_products(){

       $active_addons = $this->get_active_addons();

        if( !array_key_exists( 'dlm-email-lock', $active_addons ) ){
            $this->render_email_lock_upsell();
        }

        if( !array_key_exists( 'dlm-captcha', $active_addons ) ){
            $this->render_captcha_upsell();
        }
    }

    public function render_email_lock_upsell(){
        ?>
        <a href="<?php echo esc_url( 'https://www.download-monitor.com/pricing/?utm_source=plugin&utm_medium=extension-block&utm_campaign=Email%20Lock' ); ?>" class="options_upsell_link" target="_blank">
            <p class="form-field form-field-checkbox not-active">
                <span class="dashicons dashicons-lock"></span>
                <span><?php esc_html_e( 'Email Lock', 'download-monitor' ); ?><span class="dlm-upsell-badge">PAID</span></span>
                <span class="dlm-description"> <?php esc_html_e( 'Email locked downloads will only be available after user entered their email address.', 'download-monitor' ); ?></span>
            </p>
        </a>
        <?php
    }

    public function render_captcha_upsell(){
        ?>
        <a href="<?php echo esc_url( 'https://www.download-monitor.com/pricing/?utm_source=plugin&utm_medium=extension-block&utm_campaign=Captcha' ); ?>" class="options_upsell_link" target="_blank">
            <p class="form-field form-field-checkbox not-active">
                <span class="dashicons dashicons-lock"></span>
                <span><?php esc_html_e( 'Require Captcha', 'download-monitor' ); ?><span class="dlm-upsell-badge">PAID</span></span>
                <span class="dlm-description"> <?php esc_html_e( 'User is required to complete a reCAPTCHA before access is granted to the dowload.', 'download-monitor' ); ?></span>
            </p>
        </a>
        <?php
    }
}