<?php
/**
 * Template part for the top downloads header.
 */

if ( ! empty( $dlm_top_downloads['user_logs']['table_headers'] ) ) {
	echo '<table class="dlm-reports-table__table"><thead><tr class="dlm-reports-table__filters">';

	foreach ( $dlm_top_downloads['user_logs']['table_headers'] as $key => $table_header ) {
		echo '<th scope="col"  class="total_downloads_table_filters_' . esc_attr( $key ) . '">' . ( 'download_date' === $key ? '<a href="#">' . esc_html( $table_header ) . '</a><span class="dashicons dashicons-arrow-down"></span>' : esc_html( $table_header ) ) . '</th>';
	}

	echo '</tr></thead><!--/.dlm-reports-dlm-reports-table__filters--><!--/.dlm-reports-dlm-reports-table__table-->';
}
