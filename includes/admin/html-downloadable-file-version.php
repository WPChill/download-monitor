<div class="dlm-metabox closed downloadable_file" data-file="<?php echo $file_id; ?>">
	<h3>
		<button type="button" class="remove_file button"><?php _e( 'Remove', 'download_monitor' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'download_monitor' ); ?>"></div>
		<strong>#<?php echo $file_id; ?> &mdash; <?php echo sprintf( __( 'Version <span class="version">%s</span> (%s)', 'download_monitor' ), ( $file_version ) ? $file_version : __('n/a', 'download_monitor'), date_i18n( get_option( 'date_format' ), strtotime( $file_post_date ) ) ); ?> &mdash; <?php echo sprintf( _n('Downloaded %s time', 'Downloaded %s times', $file_download_count, 'download_monitor'), $file_download_count ); ?></strong>
		<input type="hidden" name="downloadable_file_id[<?php echo $i; ?>]" value="<?php echo $file_id; ?>" />
		<input type="hidden" class="file_menu_order" name="downloadable_file_menu_order[<?php echo $i; ?>]" value="<?php echo $i; ?>" />
	</h3>
	<table cellpadding="0" cellspacing="0" class="dlm-metabox-content">
		<tbody>
			<tr>
				<td width="1%">
					<label><?php _e( 'Version', 'download_monitor' ); ?>:</label>
					<input type="text" class="short" name="downloadable_file_version[<?php echo $i; ?>]" placeholder="<?php _e( 'n/a', 'download_monitor' ); ?>" value="<?php echo $file_version; ?>" />
				</td>
				<td rowspan="3">

					<label><?php _e( 'File URL(s)', 'download_monitor' ); ?>:</label>
					<textarea name="downloadable_file_urls[<?php echo $i; ?>]" wrap="off" class="downloadable_file_urls" cols="5" rows="5" placeholder="<?php _e( 'Enter one file path/URL per line - multiple files will be used as mirrors (chosen at random).', 'download_monitor' ); ?>"><?php echo esc_textarea( implode( "\n", $file_urls ) ); ?></textarea>
					<p>
						<?php
							$buttons = apply_filters( 'dlm_downloadable_file_version_buttons', array(
								'upload_file' => array(
									'text' => __( 'Upload file', 'download_monitor' ),
									'data' => array(
										'choose' => __( 'Choose a file', 'download_monitor' ),
										'update' => __( 'Insert file URL', 'download_monitor' ),
									)
								),
								'browse_for_file' => array(
									'text' => __( 'Browse for file', 'download_monitor' )
								)
							) );

							foreach ( $buttons as $key => $button ) {
								echo '<a href="#" class="button dlm_' . esc_attr( $key ) . '" ';
								if ( ! empty( $button['data'] ) )
									foreach ( $button['data'] as $data_key => $data_value )
										echo 'data-' . esc_attr( $data_key ) . '="' . esc_attr( $data_value ) . '" ';
								echo '>' . esc_html( $button['text'] ) . '</a> ';
							}
						?>
					</p>

				</td>
			</tr>
			<tr>
				<td>
					<label><?php _e( 'Download count', 'download_monitor' ); ?>:</label>
					<input type="text" class="short" name="downloadable_file_download_count[<?php echo $i; ?>]" placeholder="<?php echo $file_download_count; ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label><?php _e('File Date', 'download_monitor'); ?>:</label>
					<input type="text" class="date-picker-field" name="downloadable_file_date[<?php echo $i; ?>]" maxlength="10" value="<?php echo date('Y-m-d', strtotime( $file_post_date ) ); ?>" /> @ <input type="text" class="hour" placeholder="<?php _e('h', 'download_monitor') ?>" name="downloadable_file_date_hour[<?php echo $i; ?>]" maxlength="2" size="2" value="<?php echo date('H', strtotime( $file_post_date ) ); ?>" />:<input type="text" class="minute" placeholder="<?php _e('m', 'download_monitor') ?>" name="downloadable_file_date_minute[<?php echo $i; ?>]" maxlength="2" size="2" value="<?php echo date('i', strtotime( $file_post_date ) ); ?>" />
				</td>
			</tr>
		</tbody>
	</table>
</div>