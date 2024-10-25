<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! isset( $tmpl ) ) {
	// template handler
	$tmpl = new DLM_Template_Handler();
}

?>
<div class="dlm_tc_form">
	<div class='dlm-relative dlm-flex dlm-items-start dlm-mt-4'>
	<?php echo wp_kses_post( $form ); ?>
	</div>
</div>
