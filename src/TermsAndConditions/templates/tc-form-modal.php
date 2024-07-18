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
		<div class='dlm-flex dlm-h-6 dlm-items-center'>
			<input type="checkbox" name="tc_accepted" class="dlm-h-4 dlm-w-4 dlm-rounded dlm-border-gray-300 dlm-text-indigo-600 focus:dlm-ring-indigo-600 tc_accepted" id="tc_accepted_<?php echo $download->get_id(); ?>" value="1"/>
		</div>
		<div class='dlm-ml-3 dlm-text-sm dlm-leading-6'>
			<label class='dlm-font-medium dlm-text-gray-900' for="tc_accepted_<?php echo $download->get_id(); ?>"><?php echo wp_kses_post( $unlock_text ); ?></label>
		</div>
	</div>
	<?php
	$tmpl->get_template_part( 'content-download', dlm_get_default_download_template(), '', array( 'dlm_download' => $download ) );
	?>
</div>