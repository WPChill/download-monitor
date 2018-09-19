<?php

namespace Never5\DownloadMonitor\Ecommerce\Order;

class Order {

	/** @var string */
	private $status;

	/** @var \DateTimeImmutable */
	private $date_created;

	/** @var \DateTimeImmutable */
	private $date_modified;

	/** @var string */
	private $currency;

	/** @var OrderCustomer */
	private $customer;

	/** @var OrderCoupon[] */
	private $coupons;

	/** @var OrderItem[] */
	private $items;

	/** @var OrderTransaction[] */
	private $transactions;
}