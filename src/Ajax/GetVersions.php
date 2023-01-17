<?php

class DLM_Ajax_GetVersions extends DLM_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'get_versions' );
	}

	/**
	 * AJAX callback method
	 *
	 * @return void
	 */
	public function run() {
		// check nonce
		$this->check_nonce();

		if ( ! isset( $_GET['download_id'] ) ) {
			wp_send_json( array() );
			exit;
		}

		$download_id = absint( $_GET['download_id'] );

		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
		} catch ( Exception $exception ) {
			wp_send_json( array() );
			exit;
		}

		$versions = $download->get_versions();

		$versions_array = array();
		if ( ! empty( $download ) ) {
			/** @var DLM_Download_Version $version */
			foreach ( $versions as $version ) {
				if( $version->get_version() === '' ) {
					$label = 'n/a - ' . $version->get_id();
				} else {
					$label = $version->get_version();
				}
				$versions_array[] = array(
					'value' => $version->get_id(),
					'label' => $label,
				);
			}
		}

		wp_send_json( $versions_array );

		exit;
	}

}