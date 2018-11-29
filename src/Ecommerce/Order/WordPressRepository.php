<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class WordPressRepository implements Repository {

	/**
	 * Prep where statement for WP DB SQL queries
	 *
	 * An example filter is an array like this:
	 * array(
	 *  'key'       => 'id',
	 *  'value'     => 1,
	 *  'operator'  => '='
	 * )
	 *
	 * @param $filters
	 *
	 * @return string
	 */
	private function prep_where_statement( $filters ) {
		global $wpdb;
		// setup where statements
		$where = array( "WHERE 1=1" );
		foreach ( $filters as $filter ) {
			$operator = ( ! empty( $filter['operator'] ) ) ? esc_sql( $filter['operator'] ) : "=";
			if ( 'IN' == $operator && is_array( $filter['value'] ) ) {
				array_walk( $filter['value'], 'esc_sql' );
				$value_str = implode( "','", $filter['value'] );
				$where[]   = "AND `" . esc_sql( $filter['key'] ) . "` " . $operator . " ('" . $value_str . "')";
			} else {
				$where[] = $wpdb->prepare( "AND `" . esc_sql( $filter['key'] ) . "` " . $operator . " '%s'", $filter['value'] );
			}
		}
		$where_str = "";
		if ( count( $where ) > 1 ) {
			$where_str = implode( " ", $where );
		}

		return $where_str;
	}

	/**
	 * Retrieve session
	 *
	 * @param array $filters
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string order
	 *
	 * @return Order[]
	 *
	 * @throws \Exception
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0, $order_by = 'id', $order = 'DESC' ) {
		global $wpdb;

		// prep order
		$order_by = ( empty( $order_by ) ) ? 'id' : esc_sql( $order_by );
		$order    = strtoupper( $order );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'DESC';
		}

		// prep where statement
		$where_str = $this->prep_where_statement( $filters );

		$sql = "
		SELECT O.*, C.* 
		FROM `" . $wpdb->prefix . "dlm_order` O
		INNER JOIN `" . $wpdb->prefix . "dlm_order_customer` C ON O.id=C.order_id
		" . $where_str . "
		ORDER BY O.`" . $order_by . "` " . $order;

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

			$order->set_id( $result->id );
			$order->set_status( Services::get()->service( 'order_status_factory' )->make( $result->status ) );
			$order->set_currency( $result->currency );

			if ( ! empty( $result->date_created ) ) {
				$order->set_date_created( new \DateTimeImmutable( $result->date_created ) );
			}

			if ( ! empty( $result->date_modified ) ) {
				$order->set_date_created( new \DateTimeImmutable( $result->date_modified ) );
			}

			// create and set customer
			$order->set_customer( new OrderCustomer(
				$result->first_name,
				$result->last_name,
				$result->company,
				$result->address_1,
				$result->address_2,
				$result->city,
				$result->state,
				$result->postcode,
				$result->country,
				$result->email,
				$result->phone,
				$result->ip_address
			) );

			$order_items = array();
			$db_items    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . $wpdb->prefix . "dlm_order_item` WHERE `order_id` = %d ORDER BY `id` ASC ;", $order->get_id() ) );
			if ( count( $db_items ) > 0 ) {
				foreach ( $db_items as $db_item ) {
					$order_item = new OrderItem();

					$order_item->set_id( $db_item->id );
					$order_item->set_label( $db_item->label );
					$order_item->set_qty( $db_item->qty );
					$order_item->set_download_id( $db_item->download_id );
					$order_item->set_subtotal( $db_item->subtotal );
					$order_item->set_tax_class( $db_item->tax_class );
					$order_item->set_tax_total( $db_item->tax_total );
					$order_item->set_total( $db_item->total );

					$order_items[] = $order_item;
				}
			}

			$order->set_items( $order_items );


			// add new order object to array
			$orders[] = $order;

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
		$orders = $this->retrieve( array( array( 'key' => 'id', 'value' => $id, 'operator' => '=' ) ), 1 );
		if ( 0 === count( $orders ) ) {
			throw new \Exception( 'Order not found' );
		}

		return $orders[0];
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

		$order_id = $order->get_id();

		// check if it's a new order or if we need to update an existing one
		if ( empty( $order_id ) ) {
			// new order

			// insert order
			$wpdb->insert(
				$wpdb->prefix . 'dlm_order',
				array(
					'status'        => $order->get_status()->get_key(),
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

			// insert customer record
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

		} else {

			// update an existing order
			$wpdb->update(
				$wpdb->prefix . 'dlm_order',
				array(
					'status'        => $order->get_status()->get_key(),
					'date_modified' => current_time( 'mysql', 1 ),
					'currency'      => $order->get_currency()
				),
				array( 'id' => $order_id ),
				array(
					'%s',
					'%s',
					'%s',
				),
				array( '%d' )
			);

			// update customer record
			$wpdb->update(
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
				array( 'order_id' => $order_id ),
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
				),
				array( '%d' )
			);

		}


		// handle order items
		$order_items = $order->get_items();
		if ( ! empty( $order_items ) ) {
			foreach ( $order_items as $order_item ) {

				// check if this order item exists in DB already
				$order_item_id = $order_item->get_id();
				if ( empty( $order_item_id ) ) {

					// insert new order item
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
							'total'       => $order_item->get_total()
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
				} else {

					// update existing order item record
					$wpdb->update(
						$wpdb->prefix . 'dlm_order_item',
						array(
							'order_id'    => $order->get_id(),
							'label'       => $order_item->get_label(),
							'qty'         => $order_item->get_qty(),
							'download_id' => $order_item->get_download_id(),
							'tax_class'   => $order_item->get_tax_class(),
							'tax_total'   => $order_item->get_tax_total(),
							'subtotal'    => $order_item->get_subtotal(),
							'total'       => $order_item->get_total()
						),
						array( 'id' => $order_item_id ),
						array(
							'%d',
							'%s',
							'%d',
							'%d',
							'%s',
							'%d',
							'%d',
							'%d',
						),
						array( '%d' )
					);

				}
			}
		}

	}

}