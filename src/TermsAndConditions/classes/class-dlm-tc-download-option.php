<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_TC_Download_Option {

	/**
	 * Setup the class
	 */
	public function setup() {

		// Add email locked download option
		add_action( 'dlm_options_end', array( $this, 'add_download_option' ), 10, 1 );

		// Save download options
		add_action( 'dlm_save_metabox', array( $this, 'save_download_option' ), 10, 1 );
	}

	/**
	 * Add twitter lock to download options
	 *
	 * @param int $post_id
	 */
	public function add_download_option( $post_id ) {

		// get if terms & conditions locked
		$locked = get_post_meta( $post_id, DLM_TC_Constants::META_LOCKED_KEY, true );
		?>
		<p class="form-field form-field-checkbox">
			<?php wp_nonce_field( __FILE__, '_dlm_tc_nonce' ); ?>
			<input type="checkbox" name="<?php echo DLM_TC_Constants::META_LOCKED_KEY; ?>" id="<?php echo DLM_TC_Constants::META_LOCKED_KEY; ?>" <?php checked( $locked, 'yes' ); ?> />
			<label for="<?php echo DLM_TC_Constants::META_LOCKED_KEY; ?>"><?php _e( 'Terms & Conditions Required', 'download-monitor' ); ?></label>
			<span class="dlm-description"><?php _e( 'This download will only be downloadable after accepting the terms and conditions.', 'download-monitor' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Save download option
	 *
	 * @param $post_id
	 */
	public function save_download_option( $post_id ) {
		$locked = ( isset( $_POST[ DLM_TC_Constants::META_LOCKED_KEY ] ) ) ? 'yes' : 'no';
		if ( 'yes' === $locked ) {

			// check nonce if download is marked as twitter locked
			if ( ! isset( $_POST['_dlm_tc_nonce'] ) || ! wp_verify_nonce( $_POST['_dlm_tc_nonce'], __FILE__ ) ) {
				return;
			}

			// mark download as Twitter locked
			update_post_meta( $post_id, DLM_TC_Constants::META_LOCKED_KEY, $locked );
		} else {
			delete_post_meta( $post_id, DLM_TC_Constants::META_LOCKED_KEY );
		}
	}

}