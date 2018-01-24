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

		$du                    = new DLM_LU_Download_Upgrader();
		$legacy_tables         = $du->get_legacy_tables();
		$sql                   = "SELECT 1 FROM `" . $legacy_tables['files'] . "` LIMIT 1;";
		$o_suppress_errors     = $wpdb->suppress_errors;
		$wpdb->suppress_errors = true;
		$r                     = $wpdb->query( $sql );
		$wpdb->suppress_errors = $o_suppress_errors;

		return ( $r !== false );
	}

	/**
	 * Returns true if there is at least one 'new' downloads.
	 * A new download is a custom post type with type 'dlm_download'
	 * @return bool
	 */
	private function has_modern_downloads() {
		$repo   = new DLM_WordPress_Download_Repository();
		$amount = $repo->num_rows();

		return ( $amount > 0 );
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

		// check if we already upgraded
		if ( ! $this->has_been_upgraded() ) {

			// check if we have legacy tables
			if ( $this->has_legacy_tables() ) {

				/**
				 * Check if there are already 'new' download
				 * We're doing this because there are users that manually upgraded in the past
				 * So they will have the legacy tables but don't need converting
				 */
				if ( ! $this->has_modern_downloads() ) {
					return true;
				}

			}
		}

		return false;
	}

}