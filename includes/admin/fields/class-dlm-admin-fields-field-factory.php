<?php

class DLM_Admin_Fields_Field_Factory {

	/**
	 * @param $option
	 *
	 * @return DLM_Admin_Fields_Field_Text
	 */
	public static function make( $option ) {

		$field = null;

		// get value
		$value = get_option( $option['name'], '' );

		// placeholder
		$placeholder = ( ! empty( $option['placeholder'] ) ) ? $option['placeholder'] : '';

		switch ( $option['type'] ) {
			case 'text':
				$field = new DLM_Admin_Fields_Field_Text( $option['name'], $value, $placeholder );
				break;
			case 'textarea':
				$field = new DLM_Admin_Fields_Field_Textarea( $option['name'], $value, $placeholder );
				break;
			case 'checkbox':
				$field = new DLM_Admin_Fields_Field_Checkbox( $option['name'], $value, $option['cb_label'] );
				break;
			case 'select':
				$field = new DLM_Admin_Fields_Field_Select( $option['name'], $value, $option['options'] );
				break;
			default:
				/**
				 * do_filter: dlm_setting_field_$type: (null) $field, (array) $option, (String) $value, (String) $placeholder
				 */
				$field = apply_filters( 'dlm_setting_field_' . $option['type'], $field, $option, $value, $placeholder );
				
				break;
		}

		return $field;
	}

}