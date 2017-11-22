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
		$filters = array(
			array( "key" => "download_status", "value" => array( "completed", "redirected" ), "operator" => "IN" ),
		);
		$fromObj   = new DateTime( $from );
		$toObj     = new DateTime( $to );
		$filters[] = array(
			'key'      => 'download_date',
			'value'    => $fromObj->format( 'Y-m-d' ),
			'operator' => '>='
		);

		$filters[] = array(
			'key'      => 'download_date',
			'value'    => $toObj->format( 'Y-m-d' ),
			'operator' => '<='
		);

		/** @var DLM_WordPress_Log_Item_Repository $repo */
		$repo = download_monitor()->service( 'log_item_repository' );

		$response = array();
		if ( null != $id ) {
			switch ( $id ) {
				case 'total_downloads_chart':

					$data = $repo->retrieve_grouped_count( $filters, $period );

					$chart                = new DLM_Reports_Chart( $data, array(
						'from' => $from,
						'to'   => $to
					), $period );
					$response['labels']   = $chart->generate_labels();
					$response['datasets'] = array( $chart->generate_chart_data() );

					break;
				case 'total_downloads_grouped':
					$total = $repo->num_rows( $filters );
					$response['total']   = $total;
					$response['average'] = "8";
					$response['popular'] = "Great PDF";
					break;
			}
		}

		wp_send_json( $response );
		exit;
	}

}