<?php

class DLM_WordPress_Log_Item_Repository implements DLM_Log_Item_Repository {

	/**
	 * Prep where statement for WP DB SQL queries
	 *
	 * @param $filters
	 *
	 * @return string
	 */
	private function prep_where_statement( $filters ) {
		global $wpdb;

		// setup where statements
		$where = array( "WHERE 1=1" );

		foreach ( $filters as $filter ) {
			$operator = ( ! empty( $filter['operator'] ) ) ? esc_sql( $filter['operator'] ) : "=";
			$where[]  = $wpdb->prepare( "AND `" . esc_sql( $filter['key'] ) . "` " . $operator . " '%s'", $filter['value'] );
		}

		$where_str = "";
		if ( count( $where ) > 1 ) {
			$where_str = implode( " ", $where );
		}

		return $where_str;
	}

	/**
	 * Returns number of rows for given filters
	 *
	 * @param array $filters
	 *
	 * @return int
	 */
	public function num_rows( $filters = array() ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT COUNT(`ID`) FROM {$wpdb->download_log} " . $this->prep_where_statement( $filters ) . ";" );
	}

	/**
	 * Retrieve single item
	 *
	 * @param int $id
	 *
	 * @return DLM_Log_Item
	 * @throws Exception
	 */
	public function retrieve_single( $id ) {
		$logs = $this->retrieve( array( array( 'key' => 'ID', 'value' => absint( $id ) ) ) );

		if ( count( $logs ) != 1 ) {
			throw new Exception( "Log Item not found" );
		}

		return array_shift( $logs );
	}

	/**
	 * @param array $filters
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0 ) {
		global $wpdb;

		$items = array();

		// prep where statement
		$where_str = $this->prep_where_statement( $filters );

		// setup limit & offset
		$limit_str = "";
		$limit     = absint( $limit );
		$offset    = absint( $offset );
		if ( $limit > 0 ) {
			$limit_str = "LIMIT {$offset},{$limit}";
		}

		// query
		$data = $wpdb->get_results(
			"SELECT * FROM {$wpdb->download_log} {$where_str} ORDER BY download_date DESC {$limit_str};"
		);

		if ( count( $data ) > 0 ) {
			foreach ( $data as $row ) {
				$log_item = new DLM_Log_Item();
				$log_item->set_id( $row->ID );
				$log_item->set_user_id( $row->user_id );
				$log_item->set_user_ip( $row->user_ip );
				$log_item->set_user_agent( $row->user_agent );
				$log_item->set_download_id( $row->download_id );
				$log_item->set_version_id( $row->version_id );
				$log_item->set_version( $row->version );
				$log_item->set_download_date( new DateTime( $row->download_date ) );
				$log_item->set_download_status( $row->download_status );
				$log_item->set_download_status_message( $row->download_status_message );
				$log_item->set_meta_data( json_decode( $row->meta_data ) );
				$items[] = $log_item;
			}
		}

		return $items;
	}

	/**
	 * @param DLM_Log_Item $log_item
	 *
	 * @throws Exception
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

		// format log item meta data
		$meta_data = null;
		$lmd       = $log_item->get_meta_data();
		if ( ! empty( $lmd ) ) {
			$meta_data = json_encode( $lmd );
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
					'meta_data'               => $meta_data
				),
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				)
			);

			if ( false === $result ) {
				throw new Exception( 'Unable to insert log item in WordPress database' );
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
					'meta_data'               => $meta_data
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
					'%s',
					'%s',
					'%s'
				),
				array( '%d' )
			);

			if ( false === $result ) {
				throw new Exception( 'Unable to insert log item in WordPress database' );
			}

		}

		return true;
	}

}