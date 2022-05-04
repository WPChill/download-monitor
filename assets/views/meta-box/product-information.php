<div class="dlm_mb_shop_product_information">

	<?php do_action( 'dlm_mb_product_information_start', $product->get_id(), $product ); ?>

    <p class="dlm_shop_field_row">
        <label class="dlm_shop_field_label"><?php printf( esc_html__( 'Price (%s)', 'download-monitor' ), esc_html( \WPChill\DownloadMonitor\Shop\Services\Services::get()->service( 'currency' )->get_currency_symbol() ) ); ?></label>
        <span class="dlm_shop_field_input">
        <input type="text" name="_dlm_price" value="<?php echo esc_attr( $price ); ?>" class="dlm_shop_input" >
        </span>
    </p>

    <p class="dlm_shop_field_row">
        <label class="dlm_shop_field_label"><?php printf( esc_html__( 'Downloads', 'download-monitor' ), esc_html( \WPChill\DownloadMonitor\Shop\Services\Services::get()->service( 'currency' )->get_currency_symbol() ) ); ?></label>
        <span class="dlm_shop_field_input">

            <select id="dlm_downloads" name="_dlm_downloads[]"
                    multiple="true"
                    data-placeholder="<?php echo esc_attr__( 'Select Downloads&hellip;', 'download-monitor' ); ?>"
                    class="dlm-select-ext dlm_shop_input">
                <?php if ( ! empty( $downloads ) ) : ?>
	                <?php foreach ( $downloads as $download ) : ?>
                        <option value="<?php echo esc_attr( $download->get_id() ); ?>" <?php selected( in_array( $download->get_id(), $current_download_ids ), true ); ?>><?php echo esc_html( $download->get_title() ); ?></option>
	                <?php endforeach; ?>
                <?php endif; ?>
            </select>

        </span>
    </p>

	<?php do_action( 'dlm_mb_product_information_end', $product->get_id(), $product ); ?>

</div>
