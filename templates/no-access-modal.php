<?php
/**
 * No Access Modal template, used to display the no access modal. This template can be overridden by copying it to
 * yourtheme/download-monitor/no-access-modal.php. Please note that this template is used for all no access modals and
 * locked downloads.
 *
 * More info on overriding template files can be found here: https://www.download-monitor.com/kb/overriding-content-templates/
 *
 * @param string $title   The title of the modal.
 *                        Default: __( 'No Access', 'download-monitor' )
 *                        Filters: 'dlm_modal_title'
 *                        Example: add_filter( 'dlm_modal_title', function( $title ) { return 'My Custom Title'; } );
 * @param string $content The content of the modal.
 *                        Default: __( 'You do not have permission to download this file.', 'download-monitor' )
 * @param string $icon    The icon to display in the modal.
 *                        Default: 'alert'
 *                        action: 'dlm_modal_icon_' . $icon
 *                        Example: add_action( 'dlm_modal_icon_warning', function( $title, $content ) { echo 'My Warning Icon'; } );
 *
 *
 * @since 4.9.0
 * @version 5.0.14
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<div id='dlm-no-access-modal' class="dlm-no-access-modal">
	<div class='dlm-relative dlm-z-10' aria-labelledby='modal-title' role='dialog' aria-modal='true'>
		<div class='dlm-fixed dlm-inset-0 dlm-bg-gray-500 dlm-bg-opacity-75 dlm-transition-opacity'></div>
		<div class='dlm-fixed dlm-inset-0 dlm-z-10 dlm-w-screen dlm-overflow-y-auto'>
			<div
				class='dlm-no-access-modal-window dlm-flex dlm-min-h-full dlm-items-center dlm-justify-center dlm-p-4 dlm-text-center sm:dlm-items-center sm:dlm-p-0'>
				<div
					class='dlm-relative dlm-transform dlm-overflow-hidden dlm-rounded-lg dlm-bg-white dlm-text-left dlm-shadow-xl dlm-transition-all sm:dlm-my-8 sm:dlm-w-full sm:dlm-max-w-fit'>
					<?php
					if ( ! empty( $title ) || ! empty( $icon ) ) {
						?>
						<div class='dlm-bg-white dlm-px-4 dlm-pb-4 dlm-pt-5 sm:dlm-p-6 sm:dlm-pb-4'>
							<div class='sm:dlm-flex sm:dlm-items-start'>
								<?php
								if ( isset( $icon ) ) {
									switch ( $icon ) {
										case 'alert':
											?>
											<div
												class='dlm-flex dlm-h-12 dlm-w-12 dlm-flex-shrink-0 dlm-items-center dlm-justify-center dlm-rounded-full dlm-bg-red-100 sm:dlm-mx-0 sm:dlm-h-10 sm:dlm-w-10'>
												<svg class='dlm-h-6 dlm-w-6 dlm-text-red-600' fill='none'
													viewBox='0 0 24 24'
													stroke-width='1.5'
													stroke='currentColor' aria-hidden='true'>
													<path stroke-linecap='round' stroke-linejoin='round'
															d='M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'/>
												</svg>
											</div>
											<?php
											break;
										case 'success':
											?>
											<div
												class='dlm-flex dlm-h-12 dlm-w-12 dlm-flex-shrink-0 dlm-items-center dlm-justify-center dlm-rounded-full dlm-bg-green-100 sm:dlm-mx-0 sm:dlm-h-10 sm:dlm-w-10'>
												<svg class='h-6 w-6 text-green-600' fill='none' viewBox='0 0 24 24'
													stroke-width='1.5' stroke='currentColor' aria-hidden='true'>
													<path stroke-linecap='round' stroke-linejoin='round'
															d='M4.5 12.75l6 6 9-13.5'/>
												</svg>
											</div>
											<?php
											break;
										default:
											do_action( 'dlm_modal_icon_' . $icon, $title, $content );
											break;
									}
								}
								if ( ! empty( $title ) ) {
									?>
								<div class='dlm-mt-3 dlm-text-center sm:dlm-ml-4 sm:dlm-mt-0 sm:dlm-text-left'>
									<h3 class='dlm-text-base dlm-font-semibold dlm-leading-6 dlm-text-gray-900'
										id='modal-title'>
										<?php echo esc_html( $title ); ?></h3>
								</div>
									<?php
								}
								?>
							</div>
						</div>
					<?php } ?>
					<div class='dlm-m-2'>
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - Content is escaped in the extensions ?>
					</div>
					<div class='dlm-px-4 dlm-py-3 sm:dlm-flex sm:dlm-flex-row-reverse sm:dlm-px-6'>
						<button type='button'
								class='dlm-mt-3 dlm-inline-flex dlm-justify-center dlm-rounded-md dlm-bg-white dlm-px-3 dlm-py-2 dlm-text-sm dlm-font-semibold dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 hover:dlm-bg-grey-50 sm:dlm-mt-0 sm:dlm-w-auto dlm-no-access-modal-close'><?php echo esc_html__( 'Close', 'download-monitor' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
