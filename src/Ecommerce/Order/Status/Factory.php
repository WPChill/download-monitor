<?php

namespace Never5\DownloadMonitor\Ecommerce\Order\Status;

class Factory {

	/**
	 * Make new order status object for given key
	 *
	 * @param string $key
	 *
	 * @return OrderStatus
	 */
	public function make( $key ) {
		$status = new OrderStatus( '', '' );

		$status->set_key( $key );

		switch ( $key ) {
			case 'pending-payment':
				$status->set_label( __( 'Pending Payement', 'download-monitor' ) );
				break;
			case 'completed':
				$status->set_label( __( 'Completed', 'download-monitor' ) );
				break;
			case 'failed':
				$status->set_label( __( 'Failed', 'download-monitor' ) );
				break;
			case 'refunded':
				$status->set_label( __( 'Refunded', 'download-monitor' ) );
				break;
			default:
				$status->set_label( apply_filters( 'dlm_ecommerce_order_status_label', $key ) );
				break;
		}


		return $status;
	}

}