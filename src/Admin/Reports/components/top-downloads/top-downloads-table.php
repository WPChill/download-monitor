<?php
/**
 * Table for the Top Downloads of the Reports
 */

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
