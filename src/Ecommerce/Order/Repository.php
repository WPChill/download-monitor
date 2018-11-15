<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

interface Repository {

	/**
	 * Retrieve session
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return Order
	 *
	 * @throws \Exception
	 */
	public function retrieve( $limit, $offset, $order_by, $order );

	/**
	 * Retrieve a single order
	 *
	 * @param $id
	 *
	 * @return Order
	 *
	 * @throws \Exception
	 */
	public function retrieve_single( $id );

	/**
	 * Persist order
	 *
	 * @param Order $order
	 *
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function persist( $order );

}