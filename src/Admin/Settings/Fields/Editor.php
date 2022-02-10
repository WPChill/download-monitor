<?php

class DLM_Admin_Fields_Field_Editor extends DLM_Admin_Fields_Field {

	/**
	 * Renders field
	 */
	public function render() {

		$settings = array(
			'textarea_name' => $this->get_name(),
			'wpautop'       => true,
			'media_buttons' => false,
			'teeny'         => true,
			'tinymce'       => true,
			'quicktags'     => false,
			'textarea_rows' => 10,
		);
		ob_start();
		wp_editor( $this->get_value(), $this->get_name(), $settings );
		$html = ob_get_clean();
		echo $html;

	}

}
