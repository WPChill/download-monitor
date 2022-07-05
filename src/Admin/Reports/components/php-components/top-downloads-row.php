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
		<div><?php echo (int) $download->get_completed_downloads(); ?></div> <!-- Completed -->
		<div><?php echo (int) $download->get_redirected_downloads(); ?></div><!-- Redirected -->
		<div><?php echo (int) $download->get_failed_downloads(); ?></div><!-- Failed -->
		<div><?php echo (int) $download->get_total_download_count(); ?></div><!-- Total downloads, including failed ones -->
		<div><?php echo (int) $download->get_logged_in_downloads(); ?></div><!-- Logged in downloads -->
		<div><?php echo (int) $download->get_non_logged_in_downloads(); ?></div><!-- Non-logged in downloads -->
		<div><?php echo ( isset( $total_logs ) && 0 !== (int) $total_logs ) ? number_format( floatval( ( $download->get_total_download_count() * 100 ) / $total_logs ), 2 ) : '--'; ?>
			%
		</div><!-- Get percent of total downloads -->
		<div>1300</div>
	</div><!--/.total_downloads_table_entries-->
	<?php
}
?>
