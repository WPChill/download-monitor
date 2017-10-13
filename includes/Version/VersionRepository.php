<?php

interface DLM_Version_Repository {

	/**
	 * @param int $id
	 *
	 * @return \stdClass()
	 */
	public function retrieve( $id );

	/**
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	public function persist( $version );
}