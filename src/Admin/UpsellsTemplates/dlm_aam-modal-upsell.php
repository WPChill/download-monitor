<div class="dlm-modal__overlay dlm_aam">
	<div class="dlm-modal__frame">
		<div class="dlm-modal__header">
			<button class="dlm-modal__dismiss">
				<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
			</button>
		</div>
		<div class="dlm-modal__body">
			<div class="dlm-upsells-carousel-wrapper-modal">
				<div class="dlm-upsells-carousel-modal">
					<div class="dlm-upsell-modal dlm-upsell-item-modal">
						<h2><?php esc_html_e( 'Advanced Access Manager', 'download-monitor' ); ?></h2>
						<h4 class="dlm-upsell-description-modal"><?php esc_html_e( 'The Advanced Access Manager extension allows you to create advanced download limitations per download and on a global level. ', 'download-monitor' ); ?></h4>
						<ul class="dlm-upsells-list-modal">
							<li><?php esc_html_e( 'Daily download limitation per Download.', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Global daily download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Monthly download limitation per Download', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Global monthly download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Yearly download limitation per Download', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Date range download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Role download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'IP download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'User download limitation', 'download-monitor' ); ?></li>
							<li><?php esc_html_e( 'Category download limitation', 'download-monitor' ); ?></li>
						</ul>
						<p>
							<?php

							$buttons         = '<a target="_blank" href="https://download-monitor.com/free-vs-pro/?utm_source=dlm-lite&utm_medium=link&utm_campaign=upsell&utm_term=lite-vs-pro"  class="button">' . esc_html__( 'Free vs Premium', 'download-monitor' ) . '</a>';
							$buttons        .= '<a target="_blank" href="https://download-monitor.com/pricing/?utm_source=upsell&utm_medium=popup&utm_campaign=dlm-aam" style="margin-top:10px;" class="button-primary button">' . esc_html__( 'Get Premium!', 'download-monitor' ) . '</a>';

							echo apply_filters( 'dlm_modal_upsell_buttons', $buttons, 'dlm-aam' );

							?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
