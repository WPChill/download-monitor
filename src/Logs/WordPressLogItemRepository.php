<?php

class DLM_WordPress_Log_Item_Repository implements DLM_Log_Item_Repository {


	/**
	 * Download logging
	 *
	 * @param DLM_Log_Item $log_item
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function persist( $log_item ) {
		global $wpdb;
		$download_id = $log_item->get_download_id();
		$version_id  = $log_item->get_version_id();
		// allow filtering of log item.
		$log_item = apply_filters( 'dlm_log_item', $log_item, $download_id, $version_id );

		// hide wpdb errors to prevent errors in download request.
		$wpdb->hide_errors();

		// Set the log date. Should be current date, as logs will be separated by dates.
		$log_date = date( 'Y-m-d' ) . ' 00:00:00';
		// Our new table.
		$sql_update = "UPDATE {$wpdb->dlm_reports} dlm SET dlm.download_ids = %s WHERE dlm.date = %s";

		$today = $wpdb->get_results( $wpdb->prepare( "SELECT  * FROM {$wpdb->dlm_reports} WHERE date = %s;", $log_date ), ARRAY_A );

		// check if entry exists.
		if ( null !== $today && ! empty( $today ) ) {

			$downloads = json_decode( $today[0]['download_ids'], ARRAY_A );

			if ( isset( $downloads[ $download_id ] ) ) {
				$downloads[ $download_id ]['downloads'] = $downloads[ $download_id ]['downloads'] + 1;
			} else {
				$downloads[ $download_id ] = array(
					'id'        => $download_id,
					'downloads' => 1,
					'title'     => get_the_title( $download_id ),
				);
			}

			$result = $wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $log_date ) );

			if ( false === $result ) {
				throw new Exception( 'Unable to insert log item in WordPress database' );
			}
		} else {

			// insert row.
			$result = $wpdb->insert(
				$wpdb->dlm_reports,
				array(
					'date'         => $log_date,
					'download_ids' => wp_json_encode(
						array(
							$download_id => array(
								'id'        => $download_id,
								'downloads' => 1,
								'title'     => get_the_title( $download_id ),
							),
						)
					),
				)
			);

			if ( false === $result ) {
				throw new Exception( 'Unable to insert log item in WordPress database' );
			}
		}

		// trigger action when new log item was added for a download request.
		do_action( 'dlm_downloading_log_item_added', $log_item, $download_id, $version_id );

		return true;
	}

	/**
	 * Update Download and Version download counts
	 *
	 * @param [int] $download_id The ID of the Download.
	 * @param [int] $version_id The ID of the Version corresponding to the Download.
	 * @return void
	 * @since 4.5.0
	 */
	public function update_detailed_download_count( $download_id, $version_id ) {

		$download_info = $this->retrieve_single_download( $download_id );

		if ( false === $download_info ) {
			return;
		}

	}

	/**
	 * Retrieve download info from custom table
	 *
	 * @param  mixed $download_id The ID of the Download.
	 * @return void
	 * @since 4.5.0
	 */
	public function retrieve_single_download( $download_id ) {

		global $wpdb;

		// Let's get the results that contain all the info we need from both tables.
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wbdb->dlm_single_download} dlm_download INNER JOIN {$wpdb->posts} dlm_post ON dlm_download.download_id = dlm_post.ID WHERE dlm_download.download_id = %s", $download_id ), ARRAY_A );

		if ( null === $result || empty( $result ) ) {
			return;
		}

		return $result;

	}

	/**
	 * Retrieve download info from custom table
	 *
	 * @param  mixed $date The date for the desired report.
	 * @return void
	 * @since 4.5.0
	 */
	public function retrieve_single_day( $date ) {

		global $wpdb;

		// Let's get the results that contain all the info we need from both tables.
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wbdb->dlm_reports} dlm_download WHERE dlm_download.date = %s", $date ), ARRAY_A );

		if ( null === $result || empty( $result ) ) {
			return;
		}

		return $result;

	}
}
