<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

class Manager {

	/** @var Session */
	private $session_cache = null;

	/**
	 * Get session from cookie
	 *
	 * @return Session
	 */
	private function get_session_from_cookie() {
		return new Session();
	}

	/**
	 * Check if there's a session reference cookie available.
	 * If there is, try to fetch that session from DB.
	 * If there is no cookie, or fetching failed, return new (empty) session.
	 *
	 * @return Session
	 */
	public function get_session() {
		if ( null === $this->session_cache ) {
			$this->session_cache = $this->get_session_from_cookie();
		}

		return $this->session_cache;
	}

	/**
	 * Persist the session in the database and store a session reference in the cookie
	 *
	 * @param Session $session
	 */
	public function persist_session( $session ) {

	}

}