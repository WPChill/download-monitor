<?php

interface DLM_Log_Item_Repository {

	/**
	 * @param int $id
	 *
	 * @return \stdClass()
	 */
	public function retrieve( $id );

	/**
	 * @param DLM_Log_Item $log_item
	 *
	 * @return bool
	 */
	public function persist( $log_item );
}