<?php


class DLM_LU_Checker {

	/**
	 * Check if DLM has already been upgraded
	 *
	 * @return bool
	 */
	private function has_been_upgraded() {
		return ( 1 === absint( get_option( DLM_Constants::LU_OPTION_UPGRADED, 0 ) ) );
	}

	/**
	 * Check if legacy table exists
	 * @return bool
	 */
	private function has_legacy_tables() {
		global $wpdb;

		$du            = new DLM_LU_Download_Upgrader();
		$legacy_tables = $du->get_legacy_tables();
		$sql           = "SELECT 1 FROM `" . $legacy_tables['files'] . "` LIMIT 1;";
		$o_suppress_errors = $wpdb->suppress_errors;
		$wpdb->suppress_errors = true;
		$r             = $wpdb->query( $sql );
		$wpdb->suppress_errors = $o_suppress_errors;

		return ( $r !== false );
	}

	/**
	 * Mark website as upgraded
	 *
	 * @return void
	 */
	public function mark_upgraded() {
		update_option( DLM_Constants::LU_OPTION_UPGRADED, 1 );
	}

	/**
	 * Check if DLM needs upgrading
	 *
	 * @return bool
	 */
	public function needs_upgrading() {

		// no upgrade requests in AJAX
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		if ( ! $this->has_been_upgraded() ) {
			if ( $this->has_legacy_tables() ) {
				return true;
			}
		}

		return false;
	}

}