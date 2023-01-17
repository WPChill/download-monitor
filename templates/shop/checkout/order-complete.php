<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var \WPChill\DownloadMonitor\Shop\Order\Order $order */
?>
<div class="dlm-checkout dlm-checkout-complete">
    <p><?php echo esc_html__( 'Thank you for your order. Please find your order details below.', 'download-monitor' ); ?></p>

	<?php
	if ( $order != null ) :
		?>

		<?php
		/**
		 * Order details table
		 */
		?>
        <div class="dlm-checkout-complete-order-details">
            <h2><?php echo esc_html__( "Order Details", 'download-monitor' ); ?></h2>
            <table cellpadding="0" cellspacing="0" border="0">
                <tbody>
                <tr>
                    <th><?php echo esc_html__( "Order ID", 'download-monitor' ); ?></th>
                    <td><?php echo esc_html( $order->get_id() ); ?></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( "Order Status", 'download-monitor' ); ?></th>
                    <td><?php echo esc_html( $order->get_status()->get_label() ); ?></td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( "Order Date", 'download-monitor' ); ?></th>
                    <td><?php echo esc_html( $order->get_date_created()->format( 'Y-h-d H:i:s' ) ); ?></td>
                </tr>
                </tbody>
            </table>
        </div>

		<?php
		/**
		 * Downloadable files table
		 */

		if ( $order->get_status()->get_key() === 'completed' ) :

			?>
            <div class="dlm-checkout-complete-files">
                <h2>Your Products</h2>
				<?php
				$order_items = $order->get_items();

				if ( count( $order_items ) > 0 ) : ?>

					<?php foreach ( $order_items as $order_item ) : ?>

						<?php
						try {
							$product = \WPChill\DownloadMonitor\Shop\Services\Services::get()->service( 'product_repository' )->retrieve_single( $order_item->get_product_id() );
						} catch ( \Exception $exception ) {
							continue;
						}
						$allowed_kses_html = array(
							'input' => array(
								'type' 		  	=> array(),
								'name' 		 	 => array(),
								'id'    	  	=> array(),
								'class' 	  	=> array(),
								'value' 	  	=> array(),
								'placeholder'	=> array(), 
							),
							'form' => array(
								'method' 		=> array(),
								'action' 		=> array(),
								'id'    		=> array(),
								'class' 		=> array(),
							),
							'a' => array(
								'href' 		=> array(),
								'style' 		=> array(),
								'id'    		=> array(),
								'class' 		=> array(),
							),
							'p' => array(
								'style' 		=> array(),
								'id'    		=> array(),
								'class' 		=> array(),
							),
							'div' => array(
								'style' 		=> array(),
								'id'    		=> array(),
								'class' 		=> array(),
								'data-sitekey'  => array(),
							),
						);
						?>

                        <h3><?php echo esc_html( $product->get_title() ); ?></h3>
						<?php
						$downloads = $product->get_downloads();
						if ( ! empty( $downloads ) ) :
							?>
                            <table cellpadding="0" cellspacing="0" border="0">
                                <thead>
                                <tr>
                                    <th><?php echo esc_html__( "Download name", 'download-monitor' ); ?></th>
                                    <th><?php echo esc_html__( "Download version", 'download-monitor' ); ?></th>
                                    <th>&nbsp;</th>
                                </tr>
                                </thead>
                                <tbody>
								<?php foreach ( $downloads as $download ) : ?>
									<?php
									$download_title       = "-";
									$version_label        = "-";
									$download_button_html = esc_html__( 'Download is no longer available', 'download-monitor' );

									if ( $download->exists() ) {
										$download_title       = $download->get_title();
										$version_label        = $download->get_version()->get_version();
										$download_button_html = "<a href='" . esc_url( $product->get_secure_download_link( $order, $download ) ). "' class='dlm-checkout-download-button'>" . __( 'Download File', 'download-monitor' ) . "</a>";
										$download_button_html = apply_filters( 'dlm_completed_order_download', $download_button_html,  $download );
									}

									?>
                                    <tr>
                                        <td><?php echo esc_html( $download_title ); ?></td>
                                        <td><?php echo esc_html( $version_label ); ?></td>
                                        <td><?php echo wp_kses( $download_button_html, $allowed_kses_html ); ?></td>
                                    </tr>
								<?php endforeach; ?>
                                </tbody>
                            </table>
						<?php endif; ?>
					<?php endforeach; ?>


				<?php else: ?>
                    <p> <?php echo esc_html__( 'No items found.', 'download-monitor' ); ?></p>
				<?php endif; ?>
            </div>

		<?php endif; ?>

	<?php endif; ?>
</div>


