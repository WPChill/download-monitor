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

		if ( ! current_user_can( 'manage_downloads' ) ) {
			return;
		}

		wp_add_dashboard_widget( 'dlm_popular_downloads', __( 'Popular Downloads', 'download-monitor' ), array(
			$this,
			'popular_downloads'
		) );
	}

	/**
	 * popular_downloads function.
	 *
	 * @access public
	 * @return void
	 */
	public function popular_downloads() {

		$filters = array(
			'no_found_rows'  => 1,
			'posts_per_page' => 10,
			'orderby'    => 'meta_value_num',
			'order'      => 'desc',
			'meta_query' => array(
				array(
					'key'     => '_download_count',
					'value'   => '0',
					'compare' => '>'
				)
			),
			'meta_key'       => '_download_count'
		);
		$downloads = download_monitor()->service('download_repository')->retrieve($filters);

		if ( empty( $downloads ) ) {
			echo '<p>' . __( 'There are no stats available yet!', 'download-monitor' ) . '</p>';

			return;
		}

		$max_count = $downloads[0]->get_download_count();
		?>
		<table class="download_chart" cellpadding="0" cellspacing="0">
			<thead>
			<tr>
				<th scope="col"><?php _e( 'Download', "download_monitor" ); ?></th>
				<th scope="col"><?php _e( 'Download count', "download_monitor" ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			if ( $downloads ) {
			    /** @var DLM_Download $download */
				foreach ( $downloads as $download ) {

					$width = $download->get_download_count() / ( $max_count ? $max_count : 1 ) * 80;

					echo '<tr>
							<th scope="row" style="width:25%;"><a href="' . admin_url( 'post.php?post=' . $download->get_id() . '&action=edit' ) . '">' . $download->get_title() . '</a></th>
							<td><span class="bar" style="width:' . $width . '%;"></span>' . number_format( $download->get_download_count(), 0, '.', ',' ) . '</td>
						</tr>';
				}
			}
			?>
			</tbody>
		</table>
	<?php
	}

}