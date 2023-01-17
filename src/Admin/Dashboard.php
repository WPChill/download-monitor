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
				'no_found_rows'  => 1,
				'order_by_count' => '1',
				'order'          => 'DESC',
				'post_type'      => 'dlm_download',
			)
		);

		// @todo: Seems like from 4.6.x the below "add_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );" & "remove_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );" are not
		// needed anymore, it seems to break order if used. Leaving them here for a while.

		// This is a fix for Custom Posts ordering plugins
		//add_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $filters, 10 );
		// This is a fix for Custom Posts ordering plugins
		//remove_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );

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
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $download->get_id() ) . '&amp;action=edit' ) ); ?>"
							   title="<?php echo sprintf( esc_html__( 'Click to edit download: %s', 'download-monitor' ), esc_html( $download->get_title() ) ); ?>"
							   target="_blank"><?php echo esc_html( $download->get_title() ); ?></a>
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
	        <a href="<?php echo esc_url(
			        add_query_arg(
					        array(
							        'post_type' => 'dlm_download',
							        'page'      => 'download-monitor-reports',
							        'dlm_time'  => 'all-time'
					        ),
					        admin_url( 'edit.php' )
			        )
	        ); ?>"><?php echo esc_html__( 'See more', 'download-monitor' ) ; ?></a>
		</div>
		<?php
	}

	/**
	 * This is a fix for Custom Posts ordering plugins
	 *
	 * @param Object $query
	 * @return void
	 * 
	 * @since 4.5.5
	 */
	public function orderby_fix( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		$query->set(
			'orderby',
			array(
				'orderby_meta' => 'DESC',
			)
		);

		do_action( 'dlm_orderby_dashboard_fix', $query );

	}

}
