<?php
/**
 * Table for the Top Downloads of the Reports
 */


$dlm_top_downloads = apply_filters(
	'dlm_reports_top_downloads',
	array(
		'table_headers' => array(
			'id'                        => 'ID',
			'title'                     => esc_html__( 'Title', 'download-monitor' ),
			'completed_downloads'       => esc_html__( 'Completed', 'download-monitor' ),
			'failed_downloads'          => esc_html__( 'Failed', 'download-monitor' ),
			'redirected_downloads'      => esc_html__( 'Redirected', 'download-monitor' ),
			'total_downloads'           => esc_html__( 'Total', 'download-monitor' ),
			'logged_in_downloads'       => esc_html__( 'Logged In', 'download-monitor' ),
			'non_logged_in_downloads'   => esc_html__( 'Non Logged In', 'download-monitor' ),
			'percent_downloads'         => esc_html__( '% of total', 'download-monitor' ),
			'content_locking_downloads' => esc_html__( 'Content Locking', 'download-monitor' ),
		)
	)
);

$reports    = DLM_Reports::get_instance();
$total_logs = $reports->get_total_logs_count();

echo '<div class="total_downloads_table_header">
				<h3>Downloads</h3>
				<span class="total_downloads_table_exportcsv"><i class="dashicons dashicons-database-export"></i>export to csv</span>
			</div>';
// Inlcude the table header.
require __DIR__ . '/top-downloads-header.php';

// Loop through the downloads if there are any
if ( isset( $downloads ) && ! empty( $downloads ) ) {
	echo '<div class="total_downloads_table_content">';
	foreach ( $downloads as $log ) {
		// Get markup for each download.
		include __DIR__ . '/top-downloads-row.php';
	}
	echo '</div>';
} else {
	// Include the no downloads message.
	include __DIR__ . '/top-downloads-no-downloads.php';
}

// Inlcude the table footer.
require __DIR__ . '/top-downloads-footer.php';
