<?php
/**
 * Template part for the top downloads header.
 */

if ( ! empty( $dlm_top_downloads['user_logs']['table_headers'] ) ) {
	echo '<div class="dlm-reports-table__line"><div class="dlm-reports-table__filters">';

	foreach ( $dlm_top_downloads['user_logs']['table_headers'] as $key => $table_header ) {
		echo '<div class="total_downloads_table_filters_' . esc_attr( $key ) . '">' . esc_html( $table_header ) . '</div>';
	}

	echo '</div></div><!--/.dlm-reports-table__filters--><!--/.dlm-reports-table__line-->';
}
