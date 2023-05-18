<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<?php
$i                    = 1;
$already_went_through = false;
// Go through only the first version.
if ( ! $already_went_through ) {
	foreach ( $file_urls as $file_path ) {
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $file_path );

		if ( $restriction ) {
			$new_path = str_replace( DLM_Utils::basename( $file_path ), '', $file_path );
			$allowed_path = get_option( 'dlm_downloads_path', false );
			if ( $allowed_path && '' !== $allowed_path ) {
				// If there is already an allowed path get the longest common path.
				$longest_path = trailingslashit(
					untrailingslashit(
						DLM_Utils::longest_common_path(
							array(
								$allowed_path,
								$new_path
							)
						)
					)
				);
			} else {
				// If there is no allowed path just use the new path.
				$longest_path = trailingslashit( untrailingslashit( $new_path ) );
			}

			echo '<div class="dlm-restricted-path">';
			echo '<h4 class="dlm-restricted-path__restricted_file_path">' .
			     sprintf(
				     esc_html__( 'You\'re trying to serve a file from your server that is not located inside the WordPress 
			     installation or /wp-content/ folder. Clicking on the "add path" button will create a new file path exception. 
			     If you want to learn more about this, %sclick here%s', 'download-monitor' ),
				     '<a href="https://www.download-monitor.com/kb/add-path/" target="_blank">',
				     '</a>' ) .
			     '</h4>';

			echo '</p>';
			echo '<p class="dlm-restricted-path__recommended_allowed_path" >'
			     . esc_html__( 'Recommended path:', 'download-monitor' ) .
			     ' <code>' . esc_html( $longest_path ) . '</code>&nbsp;&nbsp;&nbsp;<button class="button button-primary" id="dlm-add-recommended-path" data-path="' . esc_attr( $longest_path ) . '" data-security="' . wp_create_nonce( 'dlm-ajax-nonce'). '">' .
			     esc_html__( 'Add path', 'download-monitor' ) . '</button></p>';
			echo '<p class="dlm-restricted-path__description">';
			echo esc_html__( 'This will add the download path to the "Other downloads path" setting. ', 'download-monitor' );
			if ( $allowed_path && '' !== $allowed_path ) {
				echo sprintf( esc_html__( 'Keep in mind that it will override the previous value entered there: %s.', 'download-monitor' ), '<code>' . esc_html( $allowed_path ) . '</code>' );
			}
			echo '</p>';
			echo '</div>';
		}
		if ( $i === count( $file_urls ) ) {
			$already_went_through = true;
		}
		$i ++;
	}
}
//list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $file_path );
?>
<div class="dlm-metabox closed downloadable_file" data-file="<?php echo esc_html( $file_id ); ?>">
	<h3>
		<span type="button"
		      class="remove_file dashicons dashicons-trash"></span>
		<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'download-monitor' ); ?>"></div>
		<span class="dlm-version-info">
			<span>#<?php echo esc_html( $file_id ); ?></span>
			<span><span
					class="dashicons dashicons-media-text"></span><?php echo esc_html__( 'Version: ', 'download-monitor' ) . '<span class="dlm-version-info__version">' . ( ( $file_version ) ? esc_html( $file_version ) : esc_html__( 'n/a', 'download-monitor' ) ); ?></span></span>
		<span><span
				class="dashicons dashicons-calendar-alt"></span><?php echo esc_html( date_i18n( $date_format, $file_post_date->format( 'U' ) ) ); ?></span>
		<span><span
				class="dashicons dashicons-download"></span><?php echo sprintf( __( '%s downloads', 'download-monitor' ), ( ( ! empty( $file_download_count ) ) ? esc_html( $file_download_count ) : 0 ) ); ?></span>
		</span>
		<input type="hidden" name="downloadable_file_id[<?php echo esc_attr( $version_increment ); ?>]"
		       value="<?php echo esc_attr( $file_id ); ?>"/>
		<input type="hidden" class="file_menu_order"
		       name="downloadable_file_menu_order[<?php echo esc_attr( $version_increment ); ?>]"
		       value="<?php echo esc_attr( $version_increment ); ?>"/>
	</h3>
	<div class="dlm-metabox-content">
		<tbody>

		<?php do_action( 'dlm_downloadable_file_version_table_start', $file_id, $version_increment ); ?>
		<div class="dlm-file-version__row">
			<div class="dlm-uploading-file hidden">
				<label><?php esc_html_e( 'Uploading file:', 'download-monitor' ) ?> <span></span></label>
				<label
					class="dlm-file-uploaded hidden"><?php esc_html_e( 'File uploaded.', 'download-monitor' ) ?></label>
				<div class="dlm-uploading-progress-bar"></div>
			</div>
			<div
				class="dlm-file-version__drag_and_drop dlm-uploader-container <?php echo ( ! empty( $file_urls ) ) ? 'hidden' : ''; ?>">
				<div class="dlm-file-version__uploader">
					<div id="plupload-upload-ui" class="hide-if-no-js drag-drop">
						<div id="drag-drop-area" style="position: relative;">
							<div class="drag-drop-inside">
								<p class="drag-drop-info"
								   style="letter-spacing: 1px;font-size: 10pt"><?php esc_html_e( 'Drag & Drop here', 'download-monitor' ); ?></p>
								<p>
								</p>
								<p>— or —</p>
								<p>
									<?php
									$buttons = array(
										'upload_file'   => array(
											'text' => __( 'Upload file', 'download-monitor' )
										),
										'media_library' => array(
											'text' => __( 'Media Library', 'download-monitor' ),
											'data' => array(
												'choose' => __( 'Choose a file', 'download-monitor' ),
												'update' => __( 'Insert file URL', 'download-monitor' ),
											)
										),
										'external_source' => array(
											'text' => __( 'Custom URL', 'download-monitor' )
										)
									);

									if ( ! $file_browser ) {
										$buttons['browse_for_file'] = array( 'text' => __( 'Browse for file', 'download-monitor' ) );
									}

									$buttons = apply_filters( 'dlm_downloadable_file_version_buttons', $buttons );

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
							</div>
						</div>
					</div>
				</div>
			</div>
			<div
				class="dlm-file-version__file_present dlm-uploader-container <?php echo ( empty( $file_urls ) ) ? 'hidden' : ''; ?>">
				<label><?php echo esc_html__( 'File URL(s); note: only enter multiple URLs in here if you want to use file mirrors', 'download-monitor' ); ?></label>
				<div class="dlm-uploading-file hidden">
					<label><?php esc_html_e( 'Uploading file:', 'download-monitor' ) ?> <span></span></label>
					<label
						class="dlm-file-uploaded hidden"><?php esc_html_e( 'File uploaded.', 'download-monitor' ) ?></label>
					<div class="dlm-uploading-progress-bar"></div>
				</div>
				<textarea name="downloadable_file_urls[<?php echo esc_attr( $version_increment ); ?>]" wrap="off"
				          class="downloadable_file_urls"
				          cols="5" rows="5"
				          placeholder="<?php echo esc_attr__( 'Enter one file path/URL per line - multiple files will be used as mirrors (chosen at random).', 'download-monitor' ); ?>"><?php echo esc_textarea( implode( "\n", $file_urls ) ); ?></textarea>
				<p>
					<?php
					$buttons = array(
						'upload_file'   => array(
							'text' => __( 'Upload file', 'download-monitor' )
						),
						'media_library' => array(
							'text' => __( 'Media Library', 'download-monitor' ),
							'data' => array(
								'choose' => __( 'Choose a file', 'download-monitor' ),
								'update' => __( 'Insert file URL', 'download-monitor' ),
							)
						)
					);

					if ( ! $file_browser ) {
						$buttons['browse_for_file'] = array( 'text' => __( 'Browse for file', 'download-monitor' ) );
					}

					$buttons = apply_filters( 'dlm_downloadable_file_version_buttons', $buttons );

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
					&nbsp;&nbsp;<?php echo sprintf( esc_html__( 'You can use %sDrag & Drop%s to upload files', 'download-monitor' ), '<strong>', '</strong>' ); ?>
				</p>
			</div>
		</div>
		<div class="dlm-file-version__row ">
			<div class="dlm-file-version__inline">
				<div>
					<label><?php echo esc_html__( 'Version', 'download-monitor' ); ?>:</label>
					<input type="text" class="short"
					       name="downloadable_file_version[<?php echo esc_attr( $version_increment ); ?>]"
					       placeholder="<?php echo esc_attr__( 'n/a', 'download-monitor' ); ?>"
					       value="<?php echo esc_attr( $file_version ); ?>"/>
				</div>
				<div>
					<label><?php echo esc_html__( 'Manual download count', 'download-monitor' ); ?>:</label>
					<div class="wpchill-tooltip"><i>[?]</i>
						<div
							class="wpchill-tooltip-content"><?php esc_html_e( 'Taken into consideration for the total download count ( total = Download Monitor custom table count + manual download count ).', 'download-monitor' ); ?></div>
					</div>
					<input type="text" class="short"
					       name="downloadable_file_download_count[<?php echo esc_attr( $version_increment ); ?>]"
					       placeholder="<?php echo ( isset( $meta_download_count ) ) ? esc_attr( $meta_download_count ) : '0'; ?>"/>
				</div>
				<?php if ( ! empty( $file_post_date->format( 'Y-m-d' ) ) ) {
					?>
					<div class="dlm-file-version__date">
						<label><?php echo esc_html__( 'File Date', 'download-monitor' ); ?>:</label>
						<input type="text" class="date-picker-field"
						       name="downloadable_file_date[<?php echo esc_attr( $version_increment ); ?>]"
						       maxlength="10"
						       value="<?php echo esc_attr( $file_post_date->format( 'Y-m-d' ) ); ?>"/> @
						<input
							type="text" class="hour" placeholder="<?php echo esc_html__( 'h', 'download-monitor' ) ?>"
							name="downloadable_file_date_hour[<?php echo esc_attr( $version_increment ); ?>]"
							maxlength="2"
							size="2"
							value="<?php echo esc_attr( $file_post_date->format( 'H' ) ); ?>"/>:
						<input type="text" class="minute"
						       placeholder="<?php echo esc_attr__( 'm', 'download-monitor' ) ?>"
						       name="downloadable_file_date_minute[<?php echo esc_attr( $version_increment ); ?>]"
						       maxlength="2" size="2"
						       value="<?php echo esc_attr( $file_post_date->format( 'i' ) ); ?>"/>
					</div>
					<?php

				}

				// get available hashes
				$hashes = download_monitor()->service( 'hasher' )->get_available_hashes();

				if ( ! empty( $hashes ) ) {
					?>
					<?php
					foreach ( $hashes as $hash ) {

						$value  = '';
						$method = 'get_' . $hash;
						if ( method_exists( $version, $method ) ) {
							$value = $version->$method();
						}
						?>
						<div>
							<label><?php echo esc_html( strtoupper( $hash ) ); ?> Hash: </label>
							<input type="text" readonly="readonly" value="<?php echo esc_attr( $value ); ?>"/>
						</div>
					<?php } ?>
					<?php
				}
				?>
			</div>
		</div>
		<?php do_action( 'dlm_downloadable_file_version_table_end', $file_id, $version_increment ); ?>

	</div>
</div>
