<?php

class DLM_Download_Manager {

	/**
	 * Clear download transient
	 *
	 * @param int $download_id
	 *
	 * @return bool
	 */
	public function clear_transient( $download_id ) {

		delete_transient( 'dlm_file_version_ids_' . $download_id );

		return true;
	}

}