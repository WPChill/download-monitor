<?php
/**
 * Row for each of the top downloads
 */

if ( isset( $log ) ) {
	$download = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $log['download_id'] ) );
	?>
	<div class='total_downloads_table_entries'>
		<div><?php echo esc_html( $log['download_id'] ); ?></div>
		<div><a href="#"><?php echo esc_html( $download->get_title() ); ?></a></div>
		<div><?php echo $download->get_completed_downloads(); ?></div> <!-- Completed -->
		<div><?php echo $download->get_redirected_downloads(); ?></div>
		<div><?php echo $download->get_failed_downloads(); ?></div>
		<div><?php echo esc_html( $download->get_total_download_count() ); ?></div>
		<div><?php echo esc_html( $download->get_logged_in_downloads() ); ?></div>
		<div><?php echo esc_html( $download->get_non_logged_in_downloads() ); ?></div>
		<div>15%</div>
		<div>1300</div>
	</div><!--/.total_downloads_table_entries-->
	<?php
}
?>
