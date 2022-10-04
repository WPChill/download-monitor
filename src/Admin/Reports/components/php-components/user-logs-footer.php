<?php
/**
 * Template part for top downloads table footer.
 */
?>
</table>
<div class="user-downloads-block-navigation">
	<div class='downloads-block-navigation'>
		<?php esc_html_e( 'Page:', 'download-monitor' ) ?> <input type="number" value="1" class="dlm-reports-current-page"> <span><?php esc_html_e( 'of', 'download-monitor' ) ?></span> <span class="dlm-reports-total-pages">1</span>
		<button class='dashicons dashicons-arrow-left-alt2 hidden' disabled='disabled' data-limit="15"
		        title='Previous 15 downloads'></button>
		<button class='dashicons dashicons-arrow-right-alt2 hidden' data-action='load-more' data-offset="1" data-limit="15"
		        title='Next 15 downloads'></button>
	</div>
</div>

