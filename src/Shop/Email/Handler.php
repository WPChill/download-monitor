<?php

namespace WPChill\DownloadMonitor\Shop\Email;

class Handler {

	/**
	 * Send new order email to customer.
	 *
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 */
	public function send_new_order( $order ) {

		$subject = sprintf( __( 'Your %s order', 'download-monitor' ), get_bloginfo( 'name' ) );

		$this->send_email( $order->get_customer()->get_email(), $subject, "new-order", $order );
	}

	/**
	 * Send new order notification email to shop owner
	 *
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 */
	public function send_new_order_admin( $order ) {
		$subject = sprintf( __( '%s: New order', 'download-monitor' ), get_bloginfo( 'name' ) );

		$this->send_email( get_bloginfo( 'admin_email' ), $subject, "new-order-admin", $order );
	}

	/**
	 * Send shop email
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $template
	 * @param \WPChill\DownloadMonitor\Shop\Order\Order $order
	 *
	 */
	public function send_email( $to, $subject, $template, $order ) {
		$message = new Message( $to, $subject, $template, $order );
		$message->send();
	}


}