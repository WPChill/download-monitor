<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin_Dashboard class.
 */
class DLM_Admin_Dashboard {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {

		if ( ! current_user_can( 'manage_downloads' ) || apply_filters( 'dlm_remove_dashboard_popular_downloads', false ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'dlm_popular_downloads',
			__( 'Top Downloads', 'download-monitor' ),
			array(
				$this,
				'popular_downloads',
			)
		);
	}

	/**
	 * popular_downloads function.
	 *
	 * @access public
	 * @return void
	 */
	public function popular_downloads() {

		$filters = apply_filters(
			'dlm_admin_dashboard_popular_downloads_filters',
			array(
				'no_found_rows' => 1,
				'orderby'       => array(
					'orderby_meta' => 'DESC',
				),
				'meta_query'    => array(
					'orderby_meta' => array(
						'key'  => '_download_count',
						'type' => 'NUMERIC',
					),
					array(
						'key'     => '_download_count',
						'value'   => '0',
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			),
		);

		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $filters, 10 );

		if ( empty( $downloads ) ) {
			echo '<p>' . esc_html__( 'There are no stats available yet!', 'download-monitor' ) . '</p>';

			return;
		}

		$max_count = absint( $downloads[0]->get_download_count() );
		if ( $max_count < 1 ) {
			$max_count = 1;
		}
		?>

        <div class="dlm-reports-top-downloads">
			<div class="dlm-reports-top-downloads__header">
				<div class="dlm-reports-header-left">
					<label><?php esc_html_e( 'Title', 'download-monitor' ); ?></label>
				</div>
				<div class="dlm-reports-header-right">
					<label><?php esc_html_e( 'Downloads', 'download-monitor' ); ?></label>
				</div>
			</div>
			<?php
			if ( $downloads ) {
				$i = 1;
				/** @var DLM_Download $download */
				foreach ( $downloads as $download ) {

					$width = ( $download->get_download_count() * 100 ) / $max_count;

					?>
					<div class="dlm-reports-top-downloads__line">
						<div>
							<span class="dlm-listing-position"><?php echo absint( $i ); ?>.</span>
						</div>
						<div>
							<span class="dlm-reports-top-downloads__overflower" style="width: <?php echo absint( $width ); ?>%;"></span>
							<a href="http://localhost/dev/wp-admin/post.php?post=<?php echo absint(  $download->get_id() ); ?>&amp;action=edit" title="<?php echo sprintf( esc_html__('Click to edit download: %s', 'download-monitor' ), $download->get_title() ); ?>" target="_blank"><?php echo esc_html( $download->get_title() ); ?></a>
						</div>
						<div>
							<?php echo esc_html( $download->get_download_count() ); ?>
						</div>
					</div>
					<?php
					$i++;
				}
			}
			?>
			<!-- @todo: Add a See more link that will link to the Reports page displaying the `All Time` option/shortcut of the reports
						Currently we need to open the datepicker in order to get access to the shortcuts, but we could access the dlmReportsStats and get first and last dates and send them to the datepicker api somehow
			-->
		</div>
		<?php
	}

}
