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

			if ( 'IN' == $operator && is_array( $filter['value'] ) ) {
				array_walk( $filter['value'], 'esc_sql' );
				$value_str = implode( "','", $filter['value'] );
				$where[]   = "AND `" . esc_sql( $filter['key'] ) . "` " . $operator . " ('" . $value_str . "')";
			} else {
				$where[] = $wpdb->prepare( "AND `" . esc_sql( $filter['key'] ) . "` " . $operator . " '%s'", $filter['value'] );
			}


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
	 * Retrieve grouped counts. Useful for statistics
	 *
	 * @param array $filters
	 * @param string $grouped_by
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array
	 */
	public function retrieve_grouped_count( $filters = array(), $grouped_by = "date", $limit = 0, $offset = 0, $order_by = 'value', $order = 'DESC' ) {
		global $wpdb;

		// escape grouped_by
		$grouped_by = esc_sql( $grouped_by );

		if ( "date" == $grouped_by ) {
			$format = "%Y-%m-%d";
			$grouped_by = "DATE_FORMAT(`download_date`, '" . $format . "')";
		}

		// escape order_by
		$order_by = esc_sql( $order_by );

		// order can only be ASC or DESC
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		// setup limit & offset
		$limit_str = "";
		$limit     = absint( $limit );
		$offset    = absint( $offset );
		if ( $limit > 0 ) {
			$limit_str = "LIMIT {$offset},{$limit}";
		}

		return $wpdb->get_results( "SELECT COUNT(`ID`) AS `amount`, " . $grouped_by . " AS `value` FROM {$wpdb->download_log} " . $this->prep_where_statement( $filters ) . " GROUP BY " . $grouped_by . " ORDER BY `" . $order_by . "` " . $order . " " . $limit_str . ";" );
	}

	/**
	 * @param array $filters
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array
	 */
	public function retrieve_downloads_info_per_day( ) {

		global $wpdb;

		return $wpdb->get_results( "SELECT  * FROM {$wpdb->prefix}dlm_reports_log;"
			, ARRAY_A );

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
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0, $order_by = 'download_date', $order = 'DESC' ) {
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

		// escape order_by
		$order_by = esc_sql( $order_by );

		// order can only be ASC or DESC
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		// query
		$data = $wpdb->get_results(
			"SELECT * FROM {$wpdb->download_log} {$where_str} ORDER BY `{$order_by}` {$order} {$limit_str};"
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

		// allow filtering of log item.
		$log_item = apply_filters( 'dlm_log_item', $log_item, $log_item->get_download_id(), $log_item->get_version_id() );

		// hide wpdb errors to prevent errors in download request.
		 $wpdb->hide_errors();

		 // Set the log date. Should be current date, as logs will be separated by dates.
		$log_date   = date( 'Y-m-d' ) . ' 00:00:00';
		// Our new table
		$sql_update = "UPDATE $wpdb->dlm_reports dlm SET dlm.download_ids = %s WHERE dlm.date = %s";

		$today =  $wpdb->get_results( $wpdb->prepare( "SELECT  * FROM {$new_table} WHERE date = %s;", $log_date ), ARRAY_A );

		// check if entry exists.
		if ( null !== $today && ! empty( $today ) ) {

			$downloads = json_decode( $today[0]['download_ids'], ARRAY_A );

			if ( isset( $downloads[ $log_item->get_download_id() ] ) ) {
				$downloads[ $log_item->get_download_id() ]['downloads'] = $downloads[ $log_item->get_download_id() ]['downloads'] + 1;
			} else {
				$downloads[ $log_item->get_download_id() ] = array(
					'id'        => $log_item->get_download_id(),
					'downloads' => 1,
					'title'     => get_the_title( $log_item->get_download_id() ),
				);
			}

			$result = $wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $log_date ) );

			if ( false === $result ) {
				throw new Exception( 'Unable to insert log item in WordPress database' );
			}
		} else {

			// insert row.
			$result = $wpdb->insert(
				$$wpdb->dlm_reports,
				array(
					'date'         => $log_date,
					'download_ids' => wp_json_encode(
						array(
							$log_item->get_download_id() => array(
								'id'        => $log_item->get_download_id(),
								'downloads' => 1,
								'title'     => get_the_title( $log_item->get_download_id() ),
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
		do_action( 'dlm_downloading_log_item_added', $log_item, $log_item->get_download_id(), $log_item->get_version_id() );

		return true;
	}

	/**
	 * Delete log item
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );

		return ( false !== $wpdb->delete( $wpdb->download_log, array( 'ID' => $id ), array( '%d' ) ) );
	}
}