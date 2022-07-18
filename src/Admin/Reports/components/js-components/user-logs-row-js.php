<div class='dlm-reports-table__entries'>
	<?php
	foreach ( $dlm_top_downloads['user_logs']['table_row'] as $key => $row ) {
		echo '<div class="' . esc_attr( $key ) . '">' . esc_html( $row ) . '</div>';
	}
	?>
</div>