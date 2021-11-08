<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="dlm-metabox closed downloadable_file" data-file="<?php echo esc_html( $file_id ); ?>">
	<h3>
		<button type="button" class="remove_file button"><?php echo esc_html__( 'Remove', 'download-monitor' ); ?></button>
		<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'download-monitor' ); ?>"></div>
		<strong>#<?php echo esc_html( $file_id ); ?> &mdash; <?php echo sprintf( wp_kses_post( __( 'Version <span class="version">%s</span> (%s)', 'download-monitor' ) ), ( $file_version ) ? esc_html( $file_version ) : esc_html__( 'n/a', 'download-monitor' ), esc_html( date_i18n( get_option( 'date_format' ) ), $file_post_date->format( 'U' ) ) ); ?> &mdash; <?php echo sprintf( _n( 'Downloaded %s time', 'Downloaded %s times', $file_download_count, 'download-monitor' ), esc_html( $file_download_count ) ); ?></strong>
		<input type="hidden" name="downloadable_file_id[<?php echo esc_attr( $version_increment ); ?>]" value="<?php echo esc_attr( $file_id ); ?>"/>
		<input type="hidden" class="file_menu_order" name="downloadable_file_menu_order[<?php echo esc_attr( $version_increment ); ?>]"
		       value="<?php echo esc_attr( $version_increment ); ?>"/>
	</h3>
	<table cellpadding="0" cellspacing="0" class="dlm-metabox-content">
		<tbody>

		<?php do_action( 'dlm_downloadable_file_version_table_start', $file_id, $version_increment ); ?>

		<tr>
			<td width="1%">
				<label><?php echo esc_html__( 'Version', 'download-monitor' ); ?>:</label>
				<input type="text" class="short" name="downloadable_file_version[<?php echo esc_attr( $version_increment ); ?>]"
				       placeholder="<?php echo esc_attr__( 'n/a', 'download-monitor' ); ?>" value="<?php echo esc_attr( $file_version ); ?>"/>
			</td>
			<td rowspan="3">

				<label><?php echo esc_html__( 'File URL(s); note: only enter multiple URLs in here if you want to use file mirrors', 'download-monitor' ); ?></label>

				<textarea name="downloadable_file_urls[<?php echo esc_attr( $version_increment ); ?>]" wrap="off" class="downloadable_file_urls"
				          cols="5" rows="5"
				          placeholder="<?php echo esc_attr__( 'Enter one file path/URL per line - multiple files will be used as mirrors (chosen at random).', 'download-monitor' ); ?>"><?php echo esc_textarea( implode( "\n", $file_urls ) ); ?></textarea>

				<p>
					<?php
					$buttons = apply_filters( 'dlm_downloadable_file_version_buttons', array(
						'upload_file'     => array(
							'text' => __( 'Upload file', 'download-monitor' ),
							'data' => array(
								'choose' => __( 'Choose a file', 'download-monitor' ),
								'update' => __( 'Insert file URL', 'download-monitor' ),
							)
						),
						'browse_for_file' => array(
							'text' => __( 'Browse for file', 'download-monitor' )
						)
					) );

					foreach ( $buttons as $key => $button ) {
						echo '<a href="#" class="button dlm_' . esc_attr( $key ) . '" ';
						if ( ! empty( $button['data'] ) ) {
							foreach ( $button['data'] as $data_key => $data_value ) {
								echo 'data-' . esc_attr( $data_key ) . '="' . esc_attr( $data_value ) . '" ';
							}
						}
						echo '>' . esc_html( $button['text'] ) . '</a> ';
					}
					?>
				</p>

			</td>
		</tr>
		<tr>
			<td>
				<label><?php echo esc_html__( 'Download count', 'download-monitor' ); ?>:</label>
				<input type="text" class="short" name="downloadable_file_download_count[<?php echo esc_attr( $version_increment ); ?>]"
				       placeholder="<?php echo esc_attr( $file_download_count ); ?>"/>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php echo esc_html__( 'File Date', 'download-monitor' ); ?>:</label>
				<input type="text" class="date-picker-field" name="downloadable_file_date[<?php echo esc_attr( $version_increment ); ?>]"
				       maxlength="10" value="<?php echo esc_attr( $file_post_date->format('Y-m-d') ); ?>"/> @ <input
					type="text" class="hour" placeholder="<?php echo esc_html__( 'h', 'download-monitor' ) ?>"
					name="downloadable_file_date_hour[<?php echo esc_attr( $version_increment ); ?>]" maxlength="2" size="2"
					value="<?php echo esc_attr( $file_post_date->format( 'H' ) ); ?>"/>:<input type="text" class="minute"
				                                                                              placeholder="<?php echo esc_attr__( 'm', 'download-monitor' ) ?>"
				                                                                              name="downloadable_file_date_minute[<?php echo esc_attr( $version_increment ); ?>]"
				                                                                              maxlength="2" size="2"
				                                                                              value="<?php echo esc_attr( $file_post_date->format('i') ); ?>"/>
			</td>
		</tr>

		<?php

        // get available hashes
		$hashes = download_monitor()->service( 'hasher' )->get_available_hashes();

		if ( ! empty( $hashes ) ) {
			?>
            <tr>
				<?php
                $hi = 0;
				foreach ( $hashes as $hash ) {
					if ( $hi > 0 && ( $hi % 2 ) == 0 ) {
						?></tr><tr><?php
					}
					$hi ++;
					$value  = "";
					$method = 'get_' . $hash;
					if ( method_exists( $version, $method ) ) {
						$value = $version->$method();
					}
					?>
                    <td>
                        <label><?php echo esc_html( strtoupper( $hash ) ); ?> Hash</label>
                        <input type="text" readonly="readonly" value="<?php echo esc_attr( $value ); ?>"/>
                    </td>
				<?php } ?>
            </tr>
			<?php
		}

		?>



		<?php do_action( 'dlm_downloadable_file_version_table_end', $file_id, $version_increment ); ?>

		</tbody>
	</table>
</div>
