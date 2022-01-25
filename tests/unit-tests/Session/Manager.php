<?php

namespace WPChill\DownloadMonitor\Tests\Session;

use WPChill\DownloadMonitor\Shop\Services\Services;

class Manager extends \DLM_Unit_Test_Case {

	/**
	 * Test get_session
	 */
	public function test_get_session() {

		$session = Services::get()->service( 'session' )->get_session();

		$this->assertNotNull( $session );
	}

	/**
	 * Test set_session
	 */
	public function test_set_session() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		Services::get()->service( 'session' )->set_session( $session );

		$this->assertEquals( $session->get_key(), Services::get()->service( 'session' )->get_session()->get_key() );
	}

	/**
	 * Test persist_session
	 */
	public function test_persist_session() {

		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		Services::get()->service( 'session' )->persist_session( $session );

		$this->assertEquals( $session->get_key(), Services::get()->service( 'session' )->get_session()->get_key() );
	}

	/**
	 * Test destroy_session
	 */
	public function test_destroy_session() {

		// make session
		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		/** @var \WPChill\DownloadMonitor\Shop\Session\Manager $session_manager */
		$session_manager = Services::get()->service( 'session' );

		// set session
		$session_manager->set_session( $session );

		// destroy session
		$session_manager->destroy_session( $session );

		// get_session() should be a new session with different key
		$this->assertNotEquals( $session->get_key(), $session_manager->get_session()->get_key() );
	}

	/**
	 * Test destroy_current_session
	 */
	public function test_destroy_current_session() {
		// make session
		/** @var \WPChill\DownloadMonitor\Shop\Session\Session $session */
		$session = Services::get()->service( 'session_factory' )->make();

		/** @var \WPChill\DownloadMonitor\Shop\Session\Manager $session_manager */
		$session_manager = Services::get()->service( 'session' );

		// set session
		$session_manager->set_session( $session );

		// destroy session
		$session_manager->destroy_current_session();

		// get_session() should be a new session with different key
		$this->assertNotEquals( $session->get_key(), $session_manager->get_session()->get_key() );
	}

}