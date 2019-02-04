<?php

namespace Never5\DownloadMonitor\Ecommerce\DownloadProduct;

class DownloadProduct extends \DLM_Download {

	/**
	 * @var int Price of DownloadProduct in cents
	 */
	private $price;

	/** @var bool */
	private $taxable;

	/** @var string */
	private $tax_class;

	/**
	 * @return int
	 */
	public function get_price() {
		return $this->price;
	}

	/**
	 * @param int $price
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * Set the price from user input.
	 * Format the input so the decimal separator will become a dot (.)
	 * Multiply input by 100 because we store prices in cents
	 *
	 * @param string $user_input
	 */
	public function set_price_from_user_input( $user_input ) {

		$price = $user_input;

		// if the thousand sep is not a dot, it's a comma. In this case remove all dots, then replace
		if ( '.' !== download_monitor()->service( 'settings' )->get_option( 'decimal_separator' ) ) {
			$price = str_replace( ".", "", $price );
			$price = str_replace( ",", ".", $price );
		} else {
			// thousand sep is dot. Leave the dot, remove the commas
			$price = str_replace( ",", "", $price );
		}

		// convert to cents
		$price = $price * 100;

		$this->set_price( $price );
	}

	/**
	 * Return the price ready to be used in a user input field
	 *
	 * @return string
	 */
	public function get_price_for_user_input() {
		$decimal_sep  = download_monitor()->service( 'settings' )->get_option( 'decimal_separator' );
		$thousand_sep = ( ( '.' === $decimal_sep ) ? ',' : '.' );
		$price        = ( $this->get_price() / 100 );

		return number_format( $price, 2, $decimal_sep, $thousand_sep );
	}

	/**
	 * @return bool
	 */
	public function is_taxable() {
		return $this->taxable;
	}

	/**
	 * @param bool $taxable
	 */
	public function set_taxable( $taxable ) {
		$this->taxable = $taxable;
	}

	/**
	 * @return string
	 */
	public function get_tax_class() {
		return $this->tax_class;
	}

	/**
	 * @param string $tax_class
	 */
	public function set_tax_class( $tax_class ) {
		$this->tax_class = $tax_class;
	}

	/**
	 * Get a secure download link for this download linked to given order
	 *
	 * @param \Never5\DownloadMonitor\Ecommerce\Order\Order $order
	 *
	 * @return string
	 */
	public function get_secure_download_link( $order ) {
		$download_url = $this->get_the_download_link();

		$download_url = add_query_arg( array( 'order_id'   => $order->get_id(),
		                                      'order_hash' => $order->get_hash()
		), $download_url );

		$download_url = apply_filters( 'dlm_secure_download_link', $download_url, $this, $order );

		return $download_url;
	}

}