<div class="dlm_mb_ecommerce">

	<?php do_action( 'dlm_mb_product_information_start', $download->get_id(), $download ); ?>

    <p>
        <label><?php printf( __( 'Price (%s)', 'download-monitor' ), \Never5\DownloadMonitor\Shop\Services\Services::get()->service( 'currency' )->get_currency_symbol() ); ?></label>
        <input type="text" name="_dlm_price" value="<?php echo $price; ?>">
    </p>

    <?php /*
    <p>
        <label><?php _e( 'Taxable', 'download-monitor' ); ?></label>
        <input type="checkbox" name="_dlm_taxable" value="1" <?php checked( true, $taxable ); ?>/>
    </p>

    <p>
        <label><?php _e( 'Tax Class', 'download-monitor' ); ?></label>
        <select name="_dlm_tax_class">
			<?php
			$classes = \Never5\DownloadMonitor\Shop\Services\Services::get()->service( 'tax_class_manager' )->get_tax_rates();
			if ( count( $classes ) > 0 ) {
				foreach ( $classes as $class ) {
					echo "<option value='" . $class . "'" . selected( $tax_class, $class ) . ">" . $class . " " . __( 'rate', 'download-monitor' ) . "</option>";
				}
			}
			?>
        </select>
    </p>
 */ ?>

	<?php do_action( 'dlm_mb_product_information_end', $download->get_id(), $download ); ?>

</div>
