<?php
/**
 * Template part for the top downloads header.
 */

if ( ! empty( $dlm_top_downloads['top_downloads']['table_headers'] ) ) {
	echo '<table class="dlm-reports-table__table"><thead><tr class="dlm-reports-table__filters">';

	foreach ( $dlm_top_downloads['top_downloads']['table_headers'] as $key => $table_header ) {
		echo '<th scope="col"  class="total_downloads_table_filters_' . esc_attr( $key ) . '">' . esc_html( $table_header ) . '</td>';
	}

	echo '</tr></thead><!--/.dlm-reports-dlm-reports-table__filters--><!--/.dlm-reports-dlm-reports-table__table-->';
}
