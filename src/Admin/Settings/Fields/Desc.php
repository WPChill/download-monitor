<?php

class DLM_Admin_Fields_Field_Desc extends DLM_Admin_Fields_Field {

	/**
	 * Renders field
	 */
	public function render() {
		?>
		<p class="dlm-setting-field-description"><?php echo wp_kses_post( $this->get_value() ); ?></p>
		<?php
	}

}