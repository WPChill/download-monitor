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
			case 'password':
				$field = new DLM_Admin_Fields_Field_Password( $option['name'], $value, $placeholder );
				break;
			case 'textarea':
				$field = new DLM_Admin_Fields_Field_Textarea( $option['name'], $value, $placeholder );
				break;
			case 'editor':
				$field = new DLM_Admin_Fields_Field_Editor( $option['name'], $value, $placeholder );
				break;
			case 'checkbox':
				$field = new DLM_Admin_Fields_Field_Checkbox( $option['name'], $value, $option['cb_label'] );
				break;
			case 'radio':
				$field = new DLM_Admin_Fields_Field_Radio( $option['name'], $value, $option['options'], $option['std'] );
				break;
			case 'enhanced_raio':
				$field = new DLM_Admin_Fields_Field_EnhancedRadio( $option['name'], $value, $option['options'] );
				break;
			case 'select':
				$field = new DLM_Admin_Fields_Field_Select( $option['name'], $value, $option['options'] );
				break;
			case 'lazy_select':
				$field = new DLM_Admin_Fields_Field_Lazy_Select( $option['name'], $value, $option['options'] );
				break;
			case 'action_button':
				$field = new DLM_Admin_Fields_Field_ActionButton( $option['name'], $option['link'], $option['label'] );
				break;
			case 'install_plugin':
				$field = new DLM_Admin_Fields_Field_InstallPlugin( $option['name'], $option['link'], $option['label'] );
				break;
			case 'desc':
				$field = new DLM_Admin_Fields_Field_Desc( $option['name'], $option['text'], $placeholder );
				break;
			case 'title':
				$field = new DLM_Admin_Fields_Field_Title( $option['title'] );
				break;
			case 'gateway_overview':
				$field = new \WPChill\DownloadMonitor\Shop\Admin\Fields\GatewayOverview( $option['gateways'] );
				break;
			case 'htaccess_status':
				$field = new DLM_Admin_Fields_Field_HtaccessStatus( $option );
				break;
			case 'group':
				$field = new DLM_Admin_Fields_Field_Accordion( $option['name'], $option['options'], $option['title'] );
				break;
			case 'callback':
				$field = new DLM_Admin_Fields_Field_Callback( $option );
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