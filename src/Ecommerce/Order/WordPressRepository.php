<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

class WordPressRepository implements Repository {

	/**
	 * Retrieve session
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string order
	 *
	 * @return Order[]
	 *
	 * @throws \Exception
	 */
	public function retrieve( $limit = 0, $offset = 0, $order_by = 'id', $order = 'DESC' ) {
		global $wpdb;

		$order_by = ( empty( $order_by ) ) ? 'id' : $order_by;
		$order    = strtoupper( $order );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'DESC';
		}

		$sql = $wpdb->prepare( "SELECT * FROM `" . $wpdb->prefix . "dlm_order` ORDER BY %s %s", $order_by, $order );

		$limit  = absint( $limit );
		$offset = absint( $offset );

		if ( $limit > 0 ) {
			$sql .= " LIMIT " . $limit;
		}

		if ( $offset > 0 ) {
			$sql .= " OFFSET " . $offset;
		}

		$sql .= ";";


		// try to fetch session from database
		$results = $wpdb->get_results( $sql );

		// check if result if found
		if ( null == $results ) {
			throw new \Exception( 'SQL error while fetching order' );
		}

		// array that will hold all order objects
		$orders = array();

		foreach ( $results as $result ) {

			$order = new Order();

		}

		return $orders;

	}

	/**
	 * Retrieve a single order
	 *
	 * @param $id
	 *
	 * @return Order
	 *
	 * @throws \Exception
	 */
	public function retrieve_single( $id ) {

	}

	/**
	 * Persist order
	 *
	 * @param Order $order
	 *
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function persist( $order ) {
		global $wpdb;

		$date_created = '';
		if ( null !== $order->get_date_created() ) {
			$date_created = $order->get_date_created()->format( 'Y-m-d H:i:s' );
		}

		$date_modified = '';
		if ( null !== $order->get_date_modified() ) {
			$date_modified = $order->get_date_modified()->format( 'Y-m-d H:i:s' );
		}

		if ( empty( $order->get_id() ) ) {
			// new order

			// insert order
			$wpdb->insert(
				$wpdb->prefix . 'dlm_order',
				array(
					'status'        => $order->get_status(),
					'date_created'  => $date_created,
					'date_modified' => $date_modified,
					'currency'      => $order->get_currency()
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s'
				)
			);

			// set the new id as order id
			$order->set_id( $wpdb->insert_id );

			// insert customer
			$wpdb->insert(
				$wpdb->prefix . 'dlm_order_customer',
				array(
					'first_name' => $order->get_customer()->get_first_name(),
					'last_name'  => $order->get_customer()->get_last_name(),
					'company'    => $order->get_customer()->get_company(),
					'address_1'  => $order->get_customer()->get_address_1(),
					'address_2'  => $order->get_customer()->get_address_2(),
					'city'       => $order->get_customer()->get_city(),
					'state'      => $order->get_customer()->get_state(),
					'postcode'   => $order->get_customer()->get_postcode(),
					'country'    => $order->get_customer()->get_country(),
					'email'      => $order->get_customer()->get_email(),
					'phone'      => $order->get_customer()->get_phone(),
					'ip_address' => $order->get_customer()->get_ip_address(),
					'order_id'   => $order->get_id()
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d'
				)
			);

			// insert order items
			$order_items = $order->get_items();
			if ( ! empty( $order_items ) ) {
				foreach ( $order_items as $order_item ) {
					$wpdb->insert(
						$wpdb->prefix . 'dlm_order_item',
						array(
							'order_id'    => $order->get_id(),
							'label'       => $order_item->get_label(),
							'qty'         => $order_item->get_qty(),
							'download_id' => $order_item->get_download_id(),
							'tax_class'   => $order_item->get_tax_class(),
							'tax_total'   => $order_item->get_tax_total(),
							'subtotal'    => $order_item->get_subtotal(),
							'total'       => $order_item->get_total(),
						),
						array(
							'%d',
							'%s',
							'%d',
							'%d',
							'%s',
							'%d',
							'%d',
							'%d',
						)
					);
				}
			}

		} else {

			/** @todo create update order method */

		}

	}

}