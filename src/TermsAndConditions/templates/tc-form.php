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

	<p>
		<input type="checkbox" name="tc_accepted" class="tc_accepted" id="tc_accepted_<?php echo $download->get_id(); ?>" value="1"/>
		<label for="tc_accepted_<?php echo $download->get_id(); ?>"><?php echo wp_kses_post( $unlock_text ); ?></label>
	</p>

	<?php
	$tmpl->get_template_part( 'content-download', dlm_get_default_download_template(), '', array( 'dlm_download' => $download ) );
	?>
</div>