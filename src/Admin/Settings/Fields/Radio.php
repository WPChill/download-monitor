<?php

class DLM_Admin_Fields_Field_Radio extends DLM_Admin_Fields_Field {

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
	 * @return array
	 */
	public function get_options() {
		return $this->options;
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
	 * @param array $options Radio options
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

	/**
	 * Renders field
	 */
	public function render() {

		if ( '' === $this->get_value() ) {
			$this->set_value( $this->get_default() );
		}

		foreach ( $this->get_options() as $key => $name ) {
			?>
			<label class="dlm-radio-label"><input id="setting-<?php echo esc_attr( $this->get_name() ); ?>"
			                                      name="<?php echo esc_attr( $this->get_name() ); ?>" type="radio"
			                                      value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $this->get_value() ); ?> /><span><?php echo esc_html( $name ); ?></span></label>
			<?php
		}

	}
}
