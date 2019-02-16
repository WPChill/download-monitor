<?php

class DLM_Test_Mock_Redirect {

	/**
	 * Redirect method
	 *
	 * @param string $url
	 */
	public function redirect( $url ) {

		$testCase = new \WP_UnitTestCase();

		$testCase->go_to( $url );
	}

}