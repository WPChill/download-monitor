<?php

/**
 * Class DLM_Download_Duplicator_AAM
 *
 * Download Monitor - Download Duplicater > Advanced Access Manager
 */
class DLM_Download_Duplicator_AAM {

	/**
	 * Setup class
	 */
	public function setup() {
		add_action( 'dlm_download_duplicator_download_duplicated', array( $this, 'duplicate_rules' ), 10, 2 );
	}

	/**
	 * Duplicate rules
	 *
	 * @param int $new_id
	 * @param int $old_id
	 */
	public function duplicate_rules( $new_id, $old_id ) {

		// rules manager
		$rules_manager = new Dlm_Aam_Rule_Manager();

		// get rules for download
		$rules = $rules_manager->get_rules( $old_id );

		// check if we have rules
		if ( ! empty( $rules ) ) {

			// loop through rules
			foreach ( $rules as $rule ) {

				// add rule
				$rules_manager->add_rule( $new_id, ( ( $rule->is_can_download() ) ? 1 : 0 ), $rule->get_group(), $rule->get_group_value(), $rule->get_restriction(), $rule->get_restriction_value() );
			}
		}

	}

}