<?php

class DLM_WordPress_Log_Item_Repository implements DLM_Log_Item_Repository {

	/**
	 * Prep where statement for WP DB SQL queries
	 *
	 * @param $filters
	 *
	 * @return string
	 */
	// @todo razvan : Method can be deleted after discussion if no logs page
	private function prep_where_statement( $filters ) {
		global $wpdb;

		// setup where statements.
		$where = array( 'WHERE 1=1' );

		foreach ( $filters as $filter ) {
			$operator = ( ! empty( $filter['operator'] ) ) ? esc_sql( $filter['operator'] ) : '=';

			if ( 'IN' == $operator && is_array( $filter['value'] ) ) {
				array_walk( $filter['value'], 'esc_sql' );
				$value_str = implode( "','", $filter['value'] );
				$where[]   = 'AND `' . esc_sql( $filter['key'] ) . '` ' . $operator . " ('" . $value_str . "')";
			} else {
				$where[] = $wpdb->prepare( 'AND `' . esc_sql( $filter['key'] ) . '` ' . $operator . " '%s'", $filter['value'] );
			}
		}

		$where_str = '';
		if ( count( $where ) > 1 ) {
			$where_str = implode( ' ', $where );
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

		return $wpdb->get_var( "SELECT COUNT(`ID`) FROM {$wpdb->download_log} " . $this->prep_where_statement( $filters ) . ';' );
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
		$logs = $this->retrieve(
			array(
				array(
					'key'   => 'ID',
					'value' => absint( $id ),
				),
			)
		);

		if ( count( $logs ) != 1 ) {
			throw new Exception( 'Log Item not found' );
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
	// @todo razvan : This will be deleted as logs page will not be visible to users and will be merged into reports
	// Also search in Google Drive and Amazon + others that log entries into the DB
	public function retrieve( $filters = array(), $limit = 0, $offset = 0, $order_by = 'download_date', $order = 'DESC' ) {
		global $wpdb;

		$items = array();

		// prep where statement
		$where_str = $this->prep_where_statement( $filters );

		// setup limit & offset
		$limit_str = '';
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
				//$log_item->set_download_date( new DateTime( $row->download_date ) );
				$log_item->set_download_status( $row->download_status );
				$log_item->set_download_status_message( $row->download_status_message );
				$log_item->set_meta_data( json_decode( $row->meta_data ) );
				$items[] = $log_item;
			}
		}

		return $items;
	}

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
}
