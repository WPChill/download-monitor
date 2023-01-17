<?php

class DLM_Admin_Fields_Field_Accordion extends DLM_Admin_Fields_Field {

	/** @var Object */
	private $options;

	/** @var String */
	private $title;

	/**
	 * DLM_Admin_Fields_Field_Accordion constructor.
	 *
	 * @param String $name Group name
	 * @param Array  $options Options to be rendered
	 * @param String $title Group Title
	 */
	public function __construct( $name, $options, $title ) {
		$this->options = $options;
		$this->title   = $title;
		parent::__construct( $name, '', '' );
	}

	/**
	 * Renders field
	 */
	public function render() {

		$html = '<div class="meta-box-sortables dlm-accordeon-group dlm-groupped-settings__box">';
		$html  .= '<div class="postbox closed">';
		$html .= '<div class="postbox-header">';
		$html .= '<h2 class="hndle">' . esc_html( $this->title ) . '</h2>';
		$html .= '<div class="handle-actions"><button type="button" class="handlediv" aria-expanded="false"><span class="toggle-indicator" aria-hidden="false"></span></button></div>';
		$html .= '</div>';
		$html .= '<div class="inside dlm-accordeon-group__content">';

		foreach ( $this->options as $option ) {

				// get value
			$value = get_option( $option['name'], '' );

			// placeholder
			$placeholder = ( ! empty( $option['placeholder'] ) ) ? $option['placeholder'] : '';

			switch ( $option['type'] ) {
				case 'text':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Text( $option['name'], $value, $placeholder );
					$content = $field->render();
					echo $content;
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'password':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Password( $option['name'], $value, $placeholder );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'textarea':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Textarea( $option['name'], $value, $placeholder );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'editor':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Editor( $option['name'], $value, $placeholder );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' .wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'checkbox':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Checkbox( $option['name'], $value, $option['cb_label'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'radio':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Radio( $option['name'], $value, $option['options'], $option['std'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'enhanced_radio':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_EnhancedRadio( $option['name'], $value, $option['options'], $option['std'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'select':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Select( $option['name'], $value, $option['options'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'lazy_select':

					if ( isset( $option['name'] ) ) {
						$tr_id = 'id="' . $option['name'] . '_wrapp"';
					}else{
						$tr_id = '';
					}

					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix" ' . $tr_id . '>';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$content  = '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['title'] ) . '</div>';
					$content .= '<div class="dlm-accordeon-group__setting-content">';
					$field    = new DLM_Admin_Fields_Field_Lazy_Select( $option['name'], $value, $option['options'] );
					$content .= $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'action_button':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_ActionButton( $option['name'], $option['link'], $option['label'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'action_button':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_InstallPlugin( $option['name'], $option['link'], $option['label'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'desc':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Desc( $option['name'], $option['text'], $placeholder );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'title':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new DLM_Admin_Fields_Field_Title( $option['title'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				case 'gateway_overview':
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					$field   = new \Never5\DownloadMonitor\Shop\Admin\Fields\GatewayOverview( $option['gateways'] );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ). '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();
					break;
				default:
					ob_start();
					echo '<div class="dlm-accordeon-group__setting wp-clearfix">';
					echo '<div class="dlm-accordeon-group__setting-title">' . esc_html( $option['label'] ) . '</div>';
					echo '<div class="dlm-accordeon-group__setting-content">';
					/**
					 * do_filter: dlm_setting_field_$type: (null) $field, (array) $option, (String) $value, (String) $placeholder
					 */
					$field   = null;
					$field   = apply_filters( 'dlm_setting_field_' . $option['type'], $field, $option, $value, $placeholder );
					$content = $field->render();
					echo ( isset( $option['desc'] ) ) ? '<p class="description">' . wp_kses_post( $option['desc'] ) . '</p>' : '';
					echo '</div>'; // .dlm-accordeon-group__setting-content
					echo '</div>'; // .dlm-accordeon-group__setting
					$html .= ob_get_clean();

					break;
			}
		}
		$html .= '</div>'; // .inside .dlm-accordeon-group__content
		$html .= '</div>'; // .postbox
		$html .= '</div>'; // #poststuff

		echo $html;

	}

}
