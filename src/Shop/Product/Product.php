<?php

namespace Never5\DownloadMonitor\Shop\Product;

class Product {

	/** @var int */
	private $id;

	/** @var string */
	private $title;

	/** @var string */
	private $content;

	/** @var string */
	private $status;

	/** @var int */
	private $author;

	/** @var string */
	private $excerpt;

	/**
	 * @var int Price of DownloadProduct in cents
	 */
	private $price;

	/** @var bool */
	private $taxable;

	/** @var string */
	private $tax_class;

	/** @var int[] array array with download ids */
	private $downloads = array();

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function get_author() {
		return $this->author;
	}

	/**
	 * @param int $author
	 */
	public function set_author( $author ) {
		$this->author = $author;
	}

	/**
	 * @return string
	 */
	public function get_excerpt() {
		return $this->excerpt;
	}

	/**
	 * @param string $excerpt
	 */
	public function set_excerpt( $excerpt ) {
		$this->excerpt = $excerpt;
	}

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
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 * @param \DLM_Download $download
	 *
	 * @return string
	 */
	public function get_secure_download_link( $order, $download ) {
		$download_url = $download->get_the_download_link();

		$download_url = add_query_arg( array( 'order_id'   => $order->get_id(),
		                                      'order_hash' => $order->get_hash()
		), $download_url );

		$download_url = apply_filters( 'dlm_secure_download_link', $download_url, $this, $order );

		return $download_url;
	}

	/**
	 * @return int[]
	 */
	public function get_downloads() {
		return $this->downloads;
	}

	/**
	 * @param int[] $downloads
	 */
	public function set_downloads( $downloads ) {
		$this->downloads = $downloads;
	}
}