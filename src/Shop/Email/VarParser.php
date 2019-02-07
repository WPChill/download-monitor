<?php

namespace Never5\DownloadMonitor\Shop\Email;

class VarParser {

	/**
	 * Parse (find and replace) all email variables
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 * @param string $body
	 *
	 * @return string
	 */
	public function parse( $order, $body ) {

		$body = str_ireplace( "%FIRST_NAME%", $order->get_customer()->get_first_name(), $body );

		$body = str_ireplace( "%DOWNLOADS_TABLE%", $this->generate_download_table( $order ), $body );
		$body = str_ireplace( "%DOWNLOADS_TABLE_PLAIN%", $this->generate_download_table_plain( $order ), $body );

		$body = str_ireplace( "%ORDER_TABLE%", $this->generate_order_table( $order ), $body );


		$body = str_ireplace( "%WEBSITE_NAME%", get_bloginfo( 'name' ), $body );

		$body = apply_filters( 'dlm_shop_email_var_parser', $body );

		return $body;
	}

	/**
	 * Build a simplified order items array, used in email template files
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return array
	 */
	private function build_simplified_order_items_array( $order ) {
		$order_items = $order->get_items();
		$html_items  = array();

		if ( count( $order_items ) > 0 ) {

			foreach ( $order_items as $order_item ) {

				$download             = null;
				$version_label        = "-";
				$download_button_html = __( 'Download is no longer available', 'download-monitor' );
				$download_url         = "";

				try {
					/** @var \Never5\DownloadMonitor\Shop\DownloadProduct\DownloadProduct $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $order_item->get_download_id() );

					$version_label        = $download->get_version()->get_version();
					$download_button_html = "<a href='" . $download->get_secure_download_link( $order ) . "' class='dlm-download-button'>" . __( 'Download File', 'download-monitor' ) . "</a>";
					$download_url         = $download->get_secure_download_link( $order );
				} catch ( \Exception $e ) {
				}

				$html_items[] = array(
					'label'        => $order_item->get_label(),
					'version'      => $version_label,
					'button'       => $download_button_html,
					'download_url' => $download_url
				);
			}

		}

		return $html_items;
	}

	/**
	 * Build a simplified order data array, used in email template files
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return array
	 */
	private function build_simplified_order_data_array( $order ) {
		$data = array(
			array(
				'key'   => __( 'Order ID' ),
				'value' => $order->get_id()
			)
		);

		return $data;
	}

	/**
	 * Generate the table with downloads customer just purchased
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return string
	 */
	private function generate_download_table( $order ) {

		ob_start();
		download_monitor()->service( 'template_handler' )->get_template_part( 'shop/email/elements/downloads-table', '', '', array(
			'items' => $this->build_simplified_order_items_array( $order )
		) );
		$output = ob_get_clean();


		return $output;
	}

	/**
	 * Generate a plain text overview with downloads customer just purchased
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return string
	 */
	private function generate_download_table_plain( $order ) {
		ob_start();
		download_monitor()->service( 'template_handler' )->get_template_part( 'shop/email/elements/downloads-table-plain', '', '', array(
			'items' => $this->build_simplified_order_items_array( $order )
		) );
		$output = ob_get_clean();


		return $output;
	}

	/**
	 * Generate the table with downloads customer just purchased
	 *
	 * @param \Never5\DownloadMonitor\Shop\Order\Order $order
	 *
	 * @return string
	 */
	private function generate_order_table( $order ) {

		ob_start();
		download_monitor()->service( 'template_handler' )->get_template_part( 'shop/email/elements/order-table', '', '', array(
			'items' => $this->build_simplified_order_data_array( $order )
		) );
		$output = ob_get_clean();


		return $output;
	}

}

