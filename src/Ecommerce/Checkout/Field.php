<?php

namespace Never5\DownloadMonitor\Ecommerce\Checkout;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Field {

	private $fields;

	/**
	 * Field constructor.
	 */
	public function __construct() {

		$this->fields = array(
			array(
				'name'        => 'first_name',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array( 'half', 'first' ),
				'label'       => __( 'First name', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
				'row-type'    => 'half'
			),
			array(
				'name'        => 'last_name',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array( 'half', 'last' ),
				'label'       => __( 'Last name', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
			array(
				'name'        => 'company',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'Company name', 'download-monitor' ),
				'placeholder' => "",
				'required'    => false,
			),
			array(
				'name'        => 'email',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'Email address', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
			array(
				'name'        => 'address_1',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'Address', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
			array(
				'name'        => 'postcode',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'Postcode / ZIP', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
			array(
				'name'        => 'city',
				'type'        => 'text',
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'City', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
			array(
				'name'        => 'country',
				'type'        => 'select',
				'options'     => Services::get()->service( 'country' )->get_countries(),
				'class'       => array(),
				'row-class'   => array(),
				'label'       => __( 'Country', 'download-monitor' ),
				'placeholder' => "",
				'required'    => true,
			),
		);

	}

	/**
	 * Generate field on options
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	private function do_field( $options ) {
		switch ( $options['type'] ) {
			case 'select':
				$return = sprintf( '<select name="dlm_%s">', $options['name'] );
				if ( ! empty( $options['options'] ) ) {
					foreach ( $options['options'] as $k => $v ) {
						$return .= sprintf( '<option value="%s">%s</option>', esc_attr( $k ), esc_html( $v ) );
					}
				}
				$return .= '</select>';

				return $return;
				break;
			case 'text':
			default:
				return sprintf( '<input type="text" id="dlm_%s" name="dlm_%s" value="" placeholder="%s" />', esc_attr( $options['name'] ), esc_attr( $options['name'] ), esc_attr( $options['placeholder'] ) );
				break;
		}

		return '';
	}

	/**
	 * Returns fields
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Generate field based on given options
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function generate( $options ) {
		$output = "";

		$row_class = 'dlm-checkout-row' . ( isset( $options['row-class'] ) ? ' dlm-checkout-row-' . implode( ' dlm-checkout-row-', $options['row-class'] ) : '' );

		$output .= '<div class="' . esc_attr( $row_class ) . '">';

		$output .= '<label for="dlm_' . esc_attr( $options['name'] ) . '">';

		$output .= esc_html( $options['label'] );

		if ( isset( $options['required'] ) && true === $options['required'] ) {
			$output .= '<span class="dlm-checkout-required">*</span>';
		}

		$output .= '</label>';

		$output .= '<span class="dlm-checkout-input-wrapper">';

		$output .= $this->do_field( $options );

		$output .= '</span>';
		$output .= '</div>';

		return $output;

	}

	/**
	 * Generate and output all checkout fields
	 */
	public function output_all_fields() {
		$fields = $this->get_fields();

		foreach ( $fields as $field ) {
			echo $this->generate( $field );
		}

	}

}