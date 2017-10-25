<?php

interface DLM_Download_Repository {

	/**
	 * @param int $id
	 *
	 * @return \stdClass()
	 */
	public function retrieve( $id );

	/**
	 * @param DLM_Download $download
	 *
	 * @return bool
	 */
	public function persist( $download );
}