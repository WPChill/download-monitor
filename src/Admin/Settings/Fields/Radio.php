<?php

class DLM_Admin_Fields_Field_Radio extends DLM_Admin_Fields_Field {

	/** @var String */
	private $options;

	/**
	 * DLM_Admin_Fields_Field_Radio constructor.
	 *
	 * @param String $name Radio name
	 * @param String $value Radio current value
	 * @param String $options Radio options
	 */
	public function __construct( $name, $value, $options ) {
		$this->options = $options;
		parent::__construct( $name, $value, '' );
	}

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

	/**
	 * Renders field
	 */
	public function render() {

		foreach ( $this->get_options() as $key => $name ) {
			?>
			<label class="dlm-radio-label"><input id="setting-<?php echo esc_attr( $this->get_name() ); ?>"
					  name="<?php echo esc_attr( $this->get_name() ); ?>" type="radio"
					  value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->get_value() ); ?> /><div class="dlm-radio__selectable-area"></div><span><?php echo esc_html( $name ); ?></span></label>
			<?php
		}

	}

}
