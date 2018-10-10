<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

use Never5\DownloadMonitor\Ecommerce\Services\Services;

class Manager {

	const COOKIE_NAME = 'dlm_session';

	/** @var Session */
	private $current_session = null;

	/**
	 * Get session from cookie
	 *
	 * @return Session
	 */
	private function get_session_from_cookie() {

		$session = null;

		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$cookie_data = json_decode( base64_decode( $_COOKIE[ self::COOKIE_NAME ] ), true );

			if ( is_array( $cookie_data ) && ! empty( $cookie_data['key'] ) && ! empty( $cookie_data['hash'] ) ) {

				try {
					$session = Services::get()->service( 'session_repository' )->retrieve( $cookie_data['key'], $cookie_data['hash'] );
				} catch ( \Exception $exception ) {
				}
			}
		}

		// if no session if found, use factory to create a new one.
		if ( null === $session ) {
			$session = Services::get()->service( 'session_factory' )->make();
		}


		return $session;
	}

	/**
	 * Check if there's a session reference cookie available.
	 * If there is, try to fetch that session from DB.
	 * If there is no cookie, or fetching failed, return new (empty) session.
	 *
	 * @return Session
	 */
	public function get_session() {
		if ( null === $this->current_session ) {
			$this->current_session = $this->get_session_from_cookie();
		}

		if ( null === $this->current_session ) {
			$this->current_session = new Session();
		}

		return $this->current_session;
	}

	/**
	 * @param $session
	 */
	public function set_session( $session ) {
		$this->current_session = $session;
	}

	/**
	 * Persist the session in the database and store a session reference in the cookie
	 *
	 * @param Session $session
	 */
	public function persist_session( $session ) {

		// don't persist empty sessions
		if ( 0 == count( $session->get_items() ) ) {
			return;
		}

		// can't set cookies when headers are already sent
		if ( headers_sent( $file, $line ) ) {
			\DLM_Debug_Logger::log( sprintf( "Couldn't set DLM Session cookie. Headers already set at %s:%s", $file, $line ) );

			return;
		}

		// store session in database
		Services::get()->service( 'session_repository' )->persist( $session );

		// set the actual cookie
		setcookie( self::COOKIE_NAME, base64_encode( json_encode( array(
			'key'  => $session->get_key(),
			'hash' => $session->get_hash()
		) ) ), $session->get_expiry()->getTimestamp(), COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, false, true );
	}

}