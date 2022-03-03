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
	 *
	 * @var String
	 */
	private $disabled;

	/**
	 * DLM_Admin_Fields_Field_HtaccessStatus constructor
	 *
	 * @param Array $option Array containing all options of the button
	 */
	public function __construct( $option ) {

		$this->icon       = isset( $option['icon'] ) ? $option['icon'] : '';
		$this->icon_text  = isset( $option['icon-text'] ) ? $option['icon-text'] : '';
		$this->icon_color = isset( $option['icon-color'] ) ? $option['icon-color'] : '';
		$this->link       = isset( $option['link'] ) ? $option['link'] : '';
		$this->label      = isset( $option['label'] ) ? $option['label'] : '';
		$this->disabled   = ( isset( $option['disabled'] ) && 'true' === $option['disabled'] ) ? true : false;
		$name             = isset( $option['name'] ) ? $option['name'] : '';

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
		<a class="button" <?php echo ( $this->disabled ) ? 'disabled="disabled"' : ''; ?> href="<?php echo esc_url( $this->get_url() ); ?>"><?php echo wp_kses_post( $this->label ); ?></a>
		<?php
	}

}
