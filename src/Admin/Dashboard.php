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
			__( 'Popular Downloads', 'download-monitor' ),
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
			)
		);

		// This is a fix for Custom Posts ordering plugins
		add_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );

		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $filters, 10 );

		// This is a fix for Custom Posts ordering plugins
		remove_action( 'pre_get_posts', array( $this, 'orderby_fix' ), 15 );

		if ( empty( $downloads ) ) {
			echo '<p>' . esc_html__( 'There are no stats available yet!', 'download-monitor' ) . '</p>';

			return;
		}

		$max_count = absint( $downloads[0]->get_download_count() );
		if ( $max_count < 1 ) {
			$max_count = 1;
		}
		?>
		<table class="download_chart" cellpadding="0" cellspacing="0">
			<thead>
			<tr>
				<th scope="col"><?php echo esc_html__( 'Download', 'download-monitor' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Download count', 'download-monitor' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			if ( $downloads ) {
				/** @var DLM_Download $download */
				foreach ( $downloads as $download ) {

					$width = ( $download->get_download_count() / $max_count ) * 80;

					echo '<tr>
							<th scope="row" style="width:25%;"><a href="' . esc_url( admin_url( 'post.php?post=' . $download->get_id() . '&action=edit' ) ) . '">' . esc_html( $download->get_title() ) . '</a></th>
							<td><span class="bar" style="width:' . esc_attr( $width ) . '%;"></span>' . number_format( $download->get_download_count(), 0, '.', ',' ) . '</td>
						</tr>';
				}
			}
			?>
			</tbody>
		</table>
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
