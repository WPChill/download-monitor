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
		// Polylang plugin selected page compatibility.
		if( function_exists( 'pll_current_language' ) && '' != $this->get_value() ){
			$polylang_lang = pll_current_language();
			$translations = pll_get_post_translations( absint( $this->get_value() ) );
			
			// If a translation for selected page exists, set it as the page id.
			if( isset( $translations[$polylang_lang] ) ){
				$this->set_value( absint( $translations[$polylang_lang] ) );
			}
		}
		?>
		<select id="setting-<?php echo esc_attr( $this->get_name() ); ?>" class="regular-text dlm-lazy-select"
		        name="<?php echo esc_attr( $this->get_name() ); ?>" data-selected="<?php echo esc_attr( $this->get_value() ); ?>">
            <option value="0"><?php echo esc_html__( 'Loading', 'download-monitor'); ?>...</option>
        </select>
		<?php
	}

}