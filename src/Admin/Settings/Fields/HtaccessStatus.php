<?php

class DLM_Admin_Fields_Field_HtaccessStatus extends DLM_Admin_Fields_Field {

	/**
	 * DLM_Admin_Fields_Field constructor.
	 *
	 * @param String $name
	 * @param String $link
	 * @param String $label
	 */
	public function __construct( $icon, $iconColor, $iconText, $name, $link, $label) {
		$this->icon = $icon;
		$this->icon_text = $iconText;
		$this->icon_color = $iconColor;
        $this->link  = $link;
		$this->label = $label;
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
		return add_query_arg( array(
			'dlm_action' => $this->get_name(),
			'dlm_nonce'  => $this->generate_nonce()
		), $this->link );
    }
	/**
	 * Renders field
	 */
	public function render() {
		?>
		<p class="dlm_htaccess_notice"><span style="color:<?php echo esc_attr( $this->icon_color );?>" class="dashicons <?php echo esc_attr( $this->icon ); ?>"></span> <?php echo esc_html( $this->icon_text ); ?></p>
		<a class="button" href="<?php echo esc_url( $this->get_url() ); ?>"><?php echo esc_html( $this->label ); ?></a>
        <?php
	}

}