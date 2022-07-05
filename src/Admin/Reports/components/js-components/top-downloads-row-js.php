<div class='total_downloads_table_entries'>
	<?php
	foreach ( $dlm_top_downloads['top_downloads']['table_row'] as $key => $row ) {
		echo '<div class="' . esc_attr( $key ) . '">' . esc_html( $row ) . '</div>';
	}
	?>
</div>