<?php

interface DLM_Log_Item_Repository {

	/**
	 * Retrieve items
	 *
	 * @param array $filters
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array<DLM_Log_Item>
	 */
	public function retrieve( $filters=array(), $limit=0, $offset=0 );

	/**
	 * Retrieve single item
	 *
	 * @param int $id
	 *
	 * @return DLM_Log_Item
	 */
	public function retrieve_single( $id );

	/**
	 * Returns number of rows for given filters
	 *
	 * @param array $filters
	 *
	 * @return int
	 */
	public function num_rows( $filters=array() );

	/**
	 * Persist item
	 *
	 * @param DLM_Log_Item $log_item
	 *
	 * @return bool
	 */
	public function persist( $log_item );
}