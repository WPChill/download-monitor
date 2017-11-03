<?php

class DLM_Tests_Log_Item extends DLM_Unit_Test_Case {

	/**
	 * Test get_id() and set_id() methods
	 */
	public function test_id_getter_and_setter() {
		$log_item = new DLM_Log_Item();
		$log_item->set_id( 1 );
		$this->assertEquals( $log_item->get_id(), 1 );
	}

	/**
	 * Test get_user_id() and set_user_id() methods
	 */
	public function test_user_id_getter_and_setter() {
		$log_item = new DLM_Log_Item();
		$log_item->set_user_id( 1 );
		$this->assertEquals( $log_item->get_user_id(), 1 );
	}

}