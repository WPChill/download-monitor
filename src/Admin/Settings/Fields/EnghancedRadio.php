<?php

class DLM_Admin_Fields_Field_EnhancedRadio extends DLM_Admin_Fields_Field {

	/** @var Array */
	private $options;
	/** @var String */
	private $default;

	/**
	 * DLM_Admin_Fields_Field_Radio constructor.
	 *
	 * @param String $name Radio name
	 * @param String $value Radio current value
	 * @param Array $options Radio options
	 * @param String $default Radio default value
	 */
	public function __construct( $name, $value, $options, $default = '' ) {
		$this->set_options( $options );
		$this->set_default( $default );
		parent::__construct( $name, $value, '' );
	}

	/**
	 * @return string
	 */
	public function get_default() {
		return $this->default;
	}

	/**
	 * @return array
	 */
	public function set_default( $default ) {
		$this->default = $default;
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
        if ( '' === $this->get_value() ) {
            $this->set_value($this->get_default());
        }
		foreach ( $this->get_options() as $key => $name ) {
			?>
			<label class="dlm-enhanced-radio-label"><input id="setting-<?php echo esc_attr( $this->get_name() ); ?>"
					  name="<?php echo esc_attr( $this->get_name() ); ?>" type="radio"
					  value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->get_value() ); ?> /><div class="dlm-radio__selectable-area"></div><span><?php echo esc_html( $name ); ?></span></label>
			<?php
		}

	}

}
