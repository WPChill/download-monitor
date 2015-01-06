<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Autoloader {

	private $path;

	/**
	 * The Constructor, sets the path of the class directory.
	 *
	 * @param $path
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}


	/**
	 * Autoloader load method. Load the class.
	 *
	 * @param $class_name
	 */
	public function load( $class_name ) {

		// Only load DLM
		if ( 0 === strpos( $class_name, 'DLM_' ) ) {

			// String to lower
			$class_name = strtolower( $class_name );

			// Format file name
			$file_name = 'class-' . str_ireplace( '_', '-', str_ireplace( 'DLM_', 'dlm-', $class_name ) ) . '.php';

			// Setup the file path
			$file_path = $this->path;

			// Check if we need to extend the class path
			if ( strpos( $class_name, 'dlm_admin' ) === 0 ) {
				$file_path .= 'admin/';
			} else if ( strpos( $class_name, 'dlm_widget' ) === 0 ) {
				$file_path .= 'widgets/';
			} else if ( strpos( $class_name, 'dlm_product' ) === 0 ) {
				$file_path .= 'product/';
			}

			// Append file name to clas path
			$file_path .= $file_name;

			// Check & load file
			if ( file_exists( $file_path ) ) {
				require_once( $file_path );
			}

		}

	}

}