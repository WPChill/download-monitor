<?php

class DLM_Admin_Fields_Field_HtaccessStatus extends DLM_Admin_Fields_Field {

	/**
	 *
	 * @var String
	 */
	private $icon;

	/**
	 *
	 * @var String
	 */
	private $icon_text;

	/**
	 *
	 * @var String
	 */
	private $icon_color;

	/**
	 *
	 * @var String
	 */
	private $link;

	/**
	 *
	 * @var String
	 */
	private $label;

	/**
	 * DLM_Admin_Fields_Field_HtaccessStatus constructor
	 *
	 * @param [type] $icon The icon of the button
	 * @param [type] $icon_color The color of the button
	 * @param [type] $icon_text The text of the button
	 * @param [type] $name The name of the option
	 * @param [type] $link The link of the button
	 * @param [type] $label The label of the setting
	 */
	public function __construct( $icon, $icon_color, $icon_text, $name, $link, $label ) {
		$this->icon       = $icon;
		$this->icon_text  = $icon_text;
		$this->icon_color = $icon_color;
		$this->link       = $link;
		$this->label      = $label;
		parent::__construct( $name, '', '' );
	}

	/**
	 * Generate nonce
	 *
	 * @return string
	 */
	private function generate_nonce() {
		return wp_create_nonce( $this->get_name() );
	}

	/**
	 * Get prepped URL
	 *
	 * @return string
	 */
	private function get_url() {
		return add_query_arg(
			array(
				'dlm_action' => $this->get_name(),
				'dlm_nonce'  => $this->generate_nonce(),
			),
			$this->link
		);
	}
	/**
	 * Renders field
	 */
	public function render() {
		?>
		<p class="dlm_htaccess_notice"><span style="color:<?php echo esc_attr( $this->icon_color ); ?>" class="dashicons <?php echo esc_attr( $this->icon ); ?>"></span> <?php echo wp_kses_post( $this->icon_text ); ?></p>
		<a class="button" href="<?php echo esc_url( $this->get_url() ); ?>"><?php echo wp_kses_post( $this->label ); ?></a>
		<?php
	}

}
