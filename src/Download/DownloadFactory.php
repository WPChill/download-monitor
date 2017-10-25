<?php

class DLM_Download_Factory {

	private $repository;

	/**
	 * DLM_Download_Factory constructor.
	 *
	 * @param DLM_Download_Repository $repository
	 */
	public function __construct( DLM_Download_Repository $repository ) {
		$this->repository = $repository;
	}


	/**
	 * Create a Download (DLM_Download) object
	 *
	 * @param int $id
	 *
	 * @return DLM_Download
	 */
	public function make( $id = 0 ) {

		$id = absint( $id );

		$download = new DLM_Download();

		if ( $id > 0 ) {

			try {
				// retrieve data
				$data = $this->repository->retrieve( $id );

				// set all returned data on object
				foreach ( $data as $dkey => $dval ) {
					$method = 'set_' . $dkey;
					if ( method_exists( $download, $method ) ) {
						$download->$method( $dval );
					}
				}

				// set id
				$download->set_id( $data->id );


			} catch ( Exception $e ) {

			}

		}

		return $download;
	}

}