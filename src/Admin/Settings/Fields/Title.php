<?php

class DLM_Admin_Fields_Field_Title extends DLM_Admin_Fields_Field {

	/** @var string */
	private $link;

	/** @var string */
	private $title;

	/** @var string */
	private $name;

	/**
	 * DLM_Admin_Fields_Field_Title constructor.
	 *
	 * @param String $title
	 * @param String $name
	 */
	public function __construct( $name, $title ) {
		$this->title = $title;
		$this->name  = $name;
		parent::__construct( '', '', '' );
	}

	/**
	 * Renders field
	 */
	public function render() {
		?>
        <h3 data-setting="<?php echo esc_attr( $this->name ); ?>"><?php echo esc_html( $this->title ); ?></h3>
		<?php
	}

}