<?php

class DLM_Admin_Fields_Field_Callback extends DLM_Admin_Fields_Field {
	/** @var Object */
	private $option;

	/**
	 * DLM_Admin_Fields_Field_Accordion constructor.
	 *
	 * @param  Array  $option  Options to be rendered
	 */
	public function __construct( $option ) {
		$this->option = $option;

		parent::__construct( $this->option['name'], '', '' );
	}

	/**
	 * Renders field
	 *
	 * The Button is quite an odd 'field'. It's basically just an a tag.
	 */
	public function render() {
		if ( empty( $this->option['callback'] ) ) {
			echo __( 'No callback function exists.', 'download-monitor' );
		}
		call_user_func( $this->option['callback'] );
	}
}