<?php

interface DLM_Download_Repository {

	/**
	 * Retrieve items
	 *
	 * @param array $filters
	 * @param int   $limit
	 * @param int   $offset
	 *
	 * @return array<DLM_Download>
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0 );

	/**
	 * Retrieve single item
	 *
	 * @param int $id
	 *
	 * @return DLM_Download
	 */
	public function retrieve_single( $id );

	/**
	 * Returns number of rows for given filters
	 *
	 * @param array $filters
	 *
	 * @return int
	 */
	public function num_rows( $filters = array() );

	/**
	 * @param DLM_Download $download
	 *
	 * @return bool
	 */
	public function persist( $download );

	/**
	 * Get ordered by download count Downloads
	 *
	 * @param  mixed $order The order of the downloads, can take values DESC or ASC.
	 * @param  mixed $limit How many rows should we get.
	 * @param  mixed $offset From what entry should the retriever begin.
	 * @return mixed
	 */
	public function get_orderly_downloads( $order = 'DESC', $limit = 15, $offset = 0 );


	/**
	 * Create an array of Downloads objects from an array containing DB info about Downloads CPT
	 *
	 * @param  mixed $downloads Array, usually the result of WP_Query or get_posts.
	 * @return array
	 */
	public function create_downloads_from_array( $downloads );
}
