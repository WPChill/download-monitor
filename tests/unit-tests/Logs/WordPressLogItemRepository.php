<?php

class DLM_Test_WordPress_Log_Item_Repository extends DLM_Unit_Test_Case {

	/**
	 * Test test_num_rows() without any filters
	 */
	public function test_num_rows() {

		// repo
		$wp_repo = new DLM_WordPress_Log_Item_Repository();

		// dummy log item
		$log = new DLM_Tests_Log_Item_Mock();
		$wp_repo->persist( $log );

		// should have 1 log item in DB now
		$this->assertEquals( $wp_repo->num_rows(), 1 );

		// another new dummy log item
		$log2 = new DLM_Tests_Log_Item_Mock();
		$wp_repo->persist( $log2 );

		// should have 2 log items in DB now
		$this->assertEquals( $wp_repo->num_rows(), 2 );

		// perist an exiting log item, this should NOT increase total count
		$wp_repo->persist( $log );

		// should still have 2 items in DB
		$this->assertEquals( $wp_repo->num_rows(), 2 );

	}

}