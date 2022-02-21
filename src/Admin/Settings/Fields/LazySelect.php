<?php

class DLM_Admin_Fields_Field_Lazy_Select extends DLM_Admin_Fields_Field {

	/** @var array */
	private $options;

	/**
	 * DLM_Admin_Fields_Field constructor.
	 *
	 * @param String $name
	 * @param String $value
	 * @param array $options
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
		?>
		<select id="setting-<?php echo esc_attr( $this->get_name() ); ?>" class="regular-text dlm-lazy-select"
		        name="<?php echo esc_attr( $this->get_name() ); ?>" data-selected="<?php echo esc_attr( $this->get_value() ); ?>">
            <option value="0"><?php echo esc_html__( 'Loading', 'download-monitor'); ?>...</option>
        </select>
		<?php
	}

}