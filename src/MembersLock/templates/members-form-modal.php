<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="dlm_tc_form">
	<div class='dlm-relative dlm-flex dlm-items-start dlm-mt-4'>
	
<div class="dlm-flex dlm-min-h-full dlm-flex-1 dlm-flex-col dlm-justify-center">
			<div class="sm:dlm-mx-auto sm:dlm-w-full sm:dlm-max-w-md">				
				<h2 class="dlm-mt-6 dlm-text-center dlm-text-xl dlm-font-bold dlm-leading-9 dlm-tracking-tight dlm-text-gray-900">
				<?php esc_html_e( 'Log in to your account to download the file!', 'download-monitor' ); ?>
				</h2>
			</div>

			<div class="dlm-mt-10 sm:dlm-mx-auto sm:dlm-w-full sm:dlm-max-w-[480px]">
				<div class="dlm-bg-white dlm-px-6 dlm-py-12 dlm-shadow sm:dlm-rounded-lg sm:dlm-px-12">
				<?php
				printf(
					'<form name="%1$s" id="%1$s" action="%2$s" method="post" class="dlm-space-y-6">',
					esc_attr( $args['form_id'] ),
					esc_url( site_url( 'wp-login.php', 'login_post' ) )
				);
				?>
				<div>
					<?php
					printf(
						'<label for="%1$s" class="dlm-block dlm-text-sm dlm-font-medium dlm-leading-6 dlm-text-gray-900">%2$s</label>',
						esc_attr( $args['id_username'] ),
						esc_html( $args['label_username'] )
					);
					?>
					<div class="mt-2">
						<?php
						printf(
							'<input type="text" name="log" id="%1$s" autocomplete="username" required class="input dlm-block dlm-w-full dlm-rounded-md dlm-border-0 dlm-px-1.5 dlm-py-1.5 dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 placeholder:dlm-text-gray-400 focus:dlm-ring-2 focus:dlm-ring-inset focus:dlm-ring-indigo-600 sm:dlm-text-sm sm:dlm-leading-6" value="%3$s" size="20"%4$s />',
							esc_attr( $args['id_username'] ),
							esc_html( $args['label_username'] ),
							esc_attr( $args['value_username'] ),
							( $args['required_username'] ? ' required="required"' : '' )
						);
						?>
					</div>
					</div>

					<div>
					<?php
					printf(
						'<label for="%1$s" class="dlm-block dlm-text-sm dlm-font-medium dlm-leading-6 dlm-text-gray-900">%2$s</label>',
						esc_attr( $args['id_password'] ),
						esc_html( $args['label_password'] )
					);
					?>
					<div class="dlm-mt-2">
					<?php
					printf(
						'<input type="password" name="pwd" id="%1$s" autocomplete="current-password" required spellcheck="false" class="input input dlm-block dlm-w-full dlm-rounded-md dlm-border-0 dlm-px-1.5 dlm-py-1.5 dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 placeholder:dlm-text-gray-400 focus:dlm-ring-2 focus:dlm-ring-inset focus:dlm-ring-indigo-600 sm:dlm-text-sm sm:dlm-leading-6" value="" size="20"%3$s />',
						esc_attr( $args['id_password'] ),
						esc_html( $args['label_password'] ),
						( $args['required_password'] ? ' required="required"' : '' )
					);
					?>
					</div>
					</div>
					<div>
					<?php
					printf(
						'<input type="submit" name="wp-submit" id="%1$s" class="button dlm-flex dlm-w-full dlm-justify-center dlm-rounded-md dlm-bg-indigo-600 dlm-px-3 dlm-py-1.5 dlm-text-sm dlm-font-semibold dlm-leading-6 dlm-text-white dlm-shadow-sm hover:dlm-bg-indigo-500 focus-visible:dlm-outline focus-visible:dlm-outline-2 focus-visible:dlm-outline-offset-2 focus-visible:dlm-outline-indigo-600" value="%2$s" />
						<input type="hidden" name="redirect_to" value="%3$s" />',
						esc_attr( $args['id_submit'] ),
						esc_attr( $args['label_log_in'] ),
						esc_url( $args['redirect'] )
					);
					?>
					</div>
					<?php echo wp_kses_post( $login_form_bottom ); ?>
					<input type="hidden" name="download_id" id="download_id" value="<?php echo absint( $download->get_id() ); ?>">
				</form>
				</div>
			</div>
		</div>
	</div>
</div>
