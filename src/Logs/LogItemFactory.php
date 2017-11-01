<?php

class DLM_Log_Item_Factory {

	private $repository;

	/**
	 * DLM_Log_Item_Factory constructor.
	 *
	 * @param DLM_Log_Item_Repository $repository
	 */
	public function __construct( DLM_Log_Item_Repository $repository ) {
		$this->repository = $repository;
	}


	/**
	 * Create a Log Item (DLM_Log_Item) object
	 *
	 * @param int $id
	 *
	 * @return DLM_Log_Item
	 */
	public function make( $id = 0 ) {

		$id = absint( $id );

		$log_item = new DLM_Log_Item();

		if ( $id > 0 ) {

			try {
				// retrieve data
				$data = $this->repository->retrieve( $id );

				// set all returned data on object
				foreach ( $data as $dkey => $dval ) {
					$method = 'set_' . $dkey;
					if ( method_exists( $log_item, $method ) ) {
						$log_item->$method( $dval );
					}
				}

				// set id
				$log_item->set_id( $data->id );


			} catch ( Exception $e ) {

			}

		}

		return $log_item;
	}

}