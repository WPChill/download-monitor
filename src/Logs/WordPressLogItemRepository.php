<?php

class DLM_WordPress_Log_Item_Repository implements DLM_Log_Item_Repository {

	/**
	 * @param int $id
	 *
	 * @throws \Exception
	 *
	 * @return \stdClass()
	 */
	public function retrieve( $id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->download_log} WHERE `ID` = %d", $id ) );

		if ( null === $row ) {
			throw new Exception( 'Log Item not found' );
		}

		$data = new stdClass();

		$data->id                      = $row->ID;
		$data->user_id                 = $row->user_id;
		$data->user_ip                 = $row->user_ip;
		$data->user_agent              = $row->user_agent;
		$data->download_id             = $row->download_id;
		$data->version_id              = $row->version_id;
		$data->version                 = $row->version;
		$data->download_date           = new DateTime( $row->download_date );
		$data->download_status         = $row->download_status;
		$data->download_status_message = $row->download_status_message;
		$data->meta_data               = json_decode( $row->meta_data );

		return $data;
	}

	/**
	 * @param DLM_Log_Item $log_item
	 *
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function persist( $log_item ) {
		global $wpdb;

		// hide wpdb errors to prevent errors in download request
		$wpdb->hide_errors();

		// format log item date string
		$log_item_date_string = "";
		if ( null != $log_item->get_download_date() ) {
			$log_item_date_string = $log_item->get_download_date()->format( 'Y-m-d H:i:s' );
		} else {
			$log_item_date_string = current_time( 'mysql' );
		}

		// check if new download or existing
		if ( 0 == $log_item->get_id() ) {

			// insert row
			$result = $wpdb->insert(
				$wpdb->download_log,
				array(
					'user_id'                 => absint( $log_item->get_user_id() ),
					'user_ip'                 => $log_item->get_user_ip(),
					'user_agent'              => $log_item->get_user_agent(),
					'download_id'             => absint( $log_item->get_download_id() ),
					'version_id'              => absint( $log_item->get_version_id() ),
					'version'                 => $log_item->get_version(),
					'download_date'           => $log_item_date_string,
					'download_status'         => $log_item->get_download_status(),
					'download_status_message' => $log_item->get_download_status_message(),
					'meta_data'               => json_encode( $log_item->get_meta_data() )
				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s'
				)
			);

			if ( false === $result ) {
				throw new \Exception( 'Unable to insert log item in WordPress database' );
			}

			// set new log id
			$log_item->set_id( $wpdb->insert_id );

		} else {

			// insert row
			$result = $wpdb->update(
				$wpdb->download_log,
				array(
					'user_id'                 => absint( $log_item->get_user_id() ),
					'user_ip'                 => $log_item->get_user_ip(),
					'user_agent'              => $log_item->get_user_agent(),
					'download_id'             => absint( $log_item->get_download_id() ),
					'version_id'              => absint( $log_item->get_version_id() ),
					'version'                 => $log_item->get_version(),
					'download_date'           => $log_item_date_string,
					'download_status'         => $log_item->get_download_status(),
					'download_status_message' => $log_item->get_download_status_message(),
					'meta_data'               => json_encode( $log_item->get_meta_data() )
				),
				array( 'ID' => $log_item->get_id() ),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s'
				),
				array( '%d' )
			);

			if ( false === $result ) {
				throw new \Exception( 'Unable to insert log item in WordPress database' );
			}

		}

		return true;
	}

}