<div class="dlm-modal__overlay dlm_lm">
	<div class="dlm-modal__frame dlm-lm-modal">
		<div class="dlm-modal__header">
			<button class="dlm-modal__dismiss">
				<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
			</button>
		</div>
		<div class="dlm-modal__body">
			<div class="dlm-upsells-carousel-wrapper-modal">
				<div class="dlm-upsells-carousel-modal">
					<div class="dlm-upsell-modal dlm-upsell-item-modal">
						<h2><?php esc_html_e( 'Document Library Manager', 'download-monitor' ); ?></h2>
						<h4 class="dlm-upsell-description-modal"><?php esc_html_e( 'The Document Library Manager extension allows you to display your downloads in a modern table or grid view.', 'download-monitor' ); ?></h4>
						<ul class="dlm-upsells-list-modal">
							<li>Two view types: table or grid.</li>
							<li>Find documents faster than ever.</li>
							<li>Sort, filter or search your documents.</li>
							<li>Add pagination to your listing.</li>
							<li>Publish documents through a single shortcode.</li>
							<li>Gutenberg block for a more visual configuration.</li>
							<li>File type icons or featured images available for your downloads.</li>
						</ul>
						<div class="dlm-upsells-banner-modal">
							<img class="dlm-upsells-banner-modal" src="<?php echo esc_url( DLM_URL . 'assets/images/upsells/document-library-manager-table.png' ); ?>" />
						</div>
						<p>
							<?php

							$buttons         = '<a target="_blank" href="https://download-monitor.com/free-vs-pro/?utm_source=dlm-lite&utm_medium=link&utm_campaign=upsell&utm_term=lite-vs-pro"  class="button">' . esc_html__( 'Free vs Premium', 'download-monitor' ) . '</a>';
							$buttons        .= '<a target="_blank" href="https://download-monitor.com/pricing/?utm_source=upsell&utm_medium=popup&utm_campaign=dlm-lm" style="margin-top:10px;" class="button-primary button">' . esc_html__( 'Get Premium!', 'download-monitor' ) . '</a>';

							echo apply_filters( 'dlm_modal_upsell_buttons', $buttons, 'dlm-lm' );

							?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
