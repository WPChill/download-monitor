<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

/**
 * Class DLM_Onboarding. Displays the onboarding screen if no Downloads are created.
 *
 * @since 4.7.72
 */
class DLM_Empty_Table extends WP_Posts_List_Table {

	/**
	 * Display the onboarding screen.
	 *
	 * @return void
	 * @since 4.7.70
	 */
	public function display() {

		$new_download_url = admin_url( 'post-new.php?post_type=dlm_download' );
		?>
		<div class="dlm-onboarding-wrapper">

			<div class="dlm-onboarding-title">
				<img src="<?php echo esc_url( DLM_URL ) . 'assets/images/onboarding/WPChill Onboarding Wave.png'; ?>"
					 class="dlm-onboarding-title-icon"/>
				<span><?php esc_html_e( 'Hi, there!', 'download-monitor' ); ?></span>
			</div>
			<div class="dlm-onboarding-text-wrap">
				<p><?php esc_html_e( 'Grow your audience, track download performance and convert your traffic into sales with an easy-to-use digital downloads solution. ', 'download-monitor' ); ?></p>
			</div>
			<div class="dlm-onboarding-banner-wrap">
				<img
					src="<?php echo esc_url( DLM_URL ) . 'assets/images/onboarding/dlm-banner.png'; ?>"
					class="dlm-onboarding-banner"/>
			</div>
			<div class="dlm-onboarding-button-wrap">
				<a href="<?php echo esc_url( $new_download_url ); ?>"
				   class="dlm-onboarding-button"><?php esc_html_e( 'Create your first Download', 'download-monitor' ); ?></a>
			</div>
			<div class="dlm-onboarding-doc-wrap">
				<p class="dlm-onboarding-doc"><?php echo sprintf( esc_html__( 'Need help? Check out %1$s our documentation%2$s.', 'download-monitor' ), '<a href="https://www.download-monitor.com/kb/" target="_blank">', '</a>' ); ?></p>
			</div>
		</div>
		<?php
	}
}

