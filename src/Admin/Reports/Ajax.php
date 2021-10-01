<?php

class DLM_Reports_Ajax {

	/**
	 * Setup AJAX report hooks
	 */
	public function setup() {
		add_action( 'wp_ajax_dlm_reports_data', array( $this, 'handle' ) );
	}

	public function handle() {

		// check nonce
		check_ajax_referer( 'dlm_reports_data', 'nonce' );

		// check permission
		if ( ! current_user_can( 'dlm_view_reports' ) ) {
			die();
		}

		// getters
		$id     = ( ! empty( $_GET['id'] ) ) ? $_GET['id'] : null;
		$from   = ( ! empty( $_GET['from'] ) ) ? $_GET['from'] : null;
		$to     = ( ! empty( $_GET['to'] ) ) ? $_GET['to'] : null;
		$period = ( ! empty( $_GET['period'] ) ) ? $_GET['period'] : 'day';

		// setup date filter query
		$filters   = array(
			array( "key" => "download_status", "value" => array( "completed", "redirected" ), "operator" => "IN" ),
		);
		$fromObj   = new DateTime( $from );
		$toObj     = new DateTime( $to );
		$filters[] = array(
			'key'      => 'download_date',
			'value'    => $fromObj->format( 'Y-m-d 00:00:00' ),
			'operator' => '>='
		);

		$filters[] = array(
			'key'      => 'download_date',
			'value'    => $toObj->format( 'Y-m-d 23:59:59' ),
			'operator' => '<='
		);

		/** @var DLM_WordPress_Log_Item_Repository $repo */
		$repo = download_monitor()->service( 'log_item_repository' );

		$response = array();
		$downloads = array();
		if ( null != $id ) {
			switch ( $id ) {
				case 'total_downloads_table':

					$total  = $repo->num_rows( $filters );
					$offset = ( ! empty( $_GET['page'] ) ) ? absint( $_GET['page'] ) : 0;
					$response['offset'] = $offset;
					$limit  = 15;

					$data = $repo->retrieve_grouped_count( $filters, $period, "download_id", $limit, $offset, "amount", "DESC" );

					if ( ! empty( $data ) ) {

						/** @var DLM_Download_Repository $download_repo */
						$download_repo = download_monitor()->service( 'download_repository' );

						$downloads[] = array( "#","ID", "Download Title", "Times Downloaded", "%" );
						foreach ( $data as $key => $row ) {

							$percentage = round( 100 * ( absint( $row->amount ) / absint( $total ) ), 2 );

							try {

								$download   = $download_repo->retrieve_single( $row->value );
								$downloads[] = array(
									absint( $key + 1 ) + absint( $offset * $limit ),
									'<a href="' . esc_url( $download->get_the_download_link() ) . '" target="_blank">' . $download->get_id() . '</a>',
									sprintf( "%s", $download->get_title() ),
									$row->amount,
									$percentage . "%"
								);

							} catch ( Exception $e ) {
								$downloads[] = array(
									sprintf( "Download no longer exists (#%d)", $row->value ),
									$row->amount,
									$percentage . "%"
								);
							}


						}
					}
					break;
			}
		}

		$response['downloads'] = $downloads;

		wp_send_json( $response );
		exit;
	}

}