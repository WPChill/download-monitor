<?php

class DLM_Version_Factory {

	private $repository;

	/**
	 * DLM_Version_Factory constructor.
	 *
	 * @param DLM_Version_Repository $repository
	 */
	public function __construct( DLM_Version_Repository $repository ) {
		$this->repository = $repository;
	}


	/**
	 * Create a Version (DLM_Download_Version) object
	 *
	 * @param int $id
	 *
	 * @return DLM_Download_Version
	 */
	public function make( $id = 0 ) {

		$id = absint( $id );

		$version = new DLM_Download_Version();

		if ( $id > 0 ) {

			try {
				// retrieve data
				$data = $this->repository->retrieve( $id );

				// set all returned data on object
				foreach ( $data as $dkey => $dval ) {
					$method = 'set_' . $dkey;
					if ( method_exists( $version, $method ) ) {
						$version->$method( $dval );
					}
				}

				// set id
				$version->set_id( $data->id );


			} catch ( Exception $e ) {

			}

		}

		return $version;
	}

}