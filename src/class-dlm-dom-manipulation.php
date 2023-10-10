<?php

/**
 *  The DOM Manipulation class based on the DOMDocument class
 */
class DLM_DOM_Manipulation {

	/**
	 * Holds the class object.
	 *
	 * @var
	 * @since 4.9.0
	 */
	public static $instance;

	/**
	 * Holds the DOMDocument object
	 *
	 * @var DOMDocument
	 * @since 4.9.0
	 */
	public $dom;

	/**
	 * The DOM Parser
	 *
	 * @var DOMDocument
	 * @since 4.9.0
	 */
	private $dom_parser;

	private function __construct() {
		$this->dom_parser = new DOMDocument();
	}

	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_DOM_Manipulation ) ) {
			self::$instance = new DLM_DOM_Manipulation();
		}

		return self::$instance;

	}

	/**
	 * Load the DOM
	 *
	 * @param $dom
	 *
	 * @return void
	 * @since 4.9.0
	 */
	public function load_dom( $dom ) {
		$this->dom = $dom;
		$this->dom_parser->loadHTML( $this->dom );

	}

	/**
	 * Remove the scripts from the DOM
	 *
	 * @param array $non_allowed_scripts The scripts that need to be removed.
	 *
	 * @return void
	 * @since 4.9.0
	 */
	public function remove_scripts( array $non_allowed_scripts ) {
		// Get all script elements
		$scripts = $this->dom_parser->getElementsByTagName( 'script' );

		// Iterate through the script elements
		foreach ( $scripts as $script ) {
			$src = $script->getAttribute( 'src' );
			foreach ( $non_allowed_scripts as $n_script ) {
				// Check if the src attribute starts with "jquery"
				if ( false !== strpos( $src, $n_script ) ) {
					// If it doesn't, remove the script element
					$script->parentNode->removeChild( $script );
				}
			}
		}
	}

	/**
	 * Sets the classes for the input elements
	 *
	 * @return void
	 * @since 4.9.0
	 */
	public function set_form_elements_classes() {
		// Get all input elements
		$inputs = $this->dom_parser->getElementsByTagName( 'input' );

		// Iterate through the input elements
		foreach ( $inputs as $input ) {
			$classes = '';
			$type    = $input->getAttribute( 'type' );

			switch ( $type ) {
				case 'text' :
				case 'email' :
				case 'number' :
					$class   = $input->getAttribute( 'class' );
					$classes = $class . ' dlm-rounded-md border-0 dlm-p-1.5 dlm-text-gray-900 dlm-shadow-sm dlm-ring-1 dlm-ring-inset dlm-ring-gray-300 placeholder:dlm-text-gray-400 focus:dlm-ring-2 focus:dlm-ring-inset focus:dlm-ring-indigo-600 sm:dlm-text-sm sm:dlm-leading-6';
					break;
				case 'submit' :
					$class   = $input->getAttribute( 'class' );
					$classes = $class . ' dlm-rounded dlm-bg-indigo-600 dlm-px-2 dlm-py-1 dlm-text-sm dlm-font-semibold dlm-text-white dlm-shadow-sm hover:dlm-bg-indigo-500 focus-visible:dlm-outline focus-visible:dlm-outline-2 focus-visible:dlm-outline-offset-2 focus-visible:dlm-outline-indigo-600';
					break;
				case 'checkbox' :
				case 'radio' :
					$class   = $input->getAttribute( 'class' );
					$classes = $class . ' dlm-h-4 dlm-w-4 dlm-rounded dlm-border-gray-300 dlm-text-indigo-600 focus:dlm-ring-indigo-600';
					break;
				default:
					$class   = $input->getAttribute( 'class' );
					$classes = $class . ' ' . apply_filters( 'dlm_input_' . $type . '_modal_classes', 'dlm-rounded dlm-border-gray-300 focus:dlm-ring-indigo-600' );
					break;
			}

			$input->setAttribute( 'class', $classes );
		}
	}

	/**
	 * Add classes to
	 *
	 * @param string $element    The element that needs to be modified.
	 * @param string $class_list The classes that need to be added.
	 *
	 * @return void
	 */
	public function add_custom_class( string $element, string $class_list ) {

		// Get all elements
		$labels = $this->dom_parser->getElementsByTagName( $element );

		// Iterate through the input elements
		foreach ( $labels as $label ) {
			$classes = '';

			$class   = $label->getAttribute( 'class' );
			$classes = $class . ' ' . $class_list;

			$label->setAttribute( 'class', $classes );
		}
	}

	/**
	 * Returns the HTML
	 *
	 * @return false|string
	 * @since 4.9.0
	 */
	public function get_html() {
		return $this->dom_parser->saveHTML();
	}
}
