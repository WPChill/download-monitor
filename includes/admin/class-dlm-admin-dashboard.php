<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * DLM_Admin_Dashboard class.
 */
class DLM_Admin_Dashboard {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		if ( ! current_user_can( 'manage_downloads' ) )
			return;

		wp_add_dashboard_widget( 'dlm_popular_downloads', __( 'Popular Downloads', 'download-monitor' ), array( $this, 'popular_downloads' ) );
	}

	/**
	 * popular_downloads function.
	 *
	 * @access public
	 * @return void
	 */
	public function popular_downloads() {

		$args = array(
    		'post_status' 	 => 'publish',
    		'post_type'      => 'dlm_download',
    		'no_found_rows'  => 1,
    		'posts_per_page' => 10,
    		'orderby' 		 => 'meta_value_num',
    		'order'          => 'desc',
    		'meta_query'     => array(
    			array(
	    			'key'     => '_download_count',
	    			'value'   => '0',
	    			'compare' => '>'
    			)
    		),
    		'meta_key'       => '_download_count',
    		'fields'         => 'ids'
    	);

    	$download_ids = get_posts( $args );

    	if ( empty( $download_ids ) ) {
    		echo '<p>' . __( 'There are no stats available yet!', 'download-monitor' ) . '</p>';
    		return;
    	}

    	$downloads    = array();

    	foreach ( $download_ids as $download_id ) {
    		$downloads[ $download_id ] = get_post_meta( $download_id, '_download_count', true );
    	}

    	if ( $downloads )
    		$max_count = max( $downloads );
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
			    	if ( $downloads ) foreach ( $downloads as $download_id => $count ) {
				    	$download = new DLM_Download( $download_id );

				    	$width = $count / ( $max_count ? $max_count : 1 ) * 67;

						echo '<tr>
							<th scope="row" style="width:25%;"><a href="' . admin_url( 'post.php?post=' . $download_id . '&action=edit' ) . '">' . $download->get_the_title() . '</a></th>
							<td><span class="bar" style="width:' . $width . '%;"></span>' . number_format( $count, 0, '.', ',' ) . '</td>
						</tr>';
			    	}
			    ?>
			</tbody>
    	</table>
    	<?php
	}

}

new DLM_Admin_Dashboard();