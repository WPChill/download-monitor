<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

interface Repository {

	/**
	 * Retrieve session
	 *
	 * @param string $key
	 * @param string $hash
	 *
	 * @return Session
	 *
	 * @throws \Exception
	 */
	public function retrieve( $key, $hash );


	/**
	 * Persist session
	 *
	 * @param Session $session
	 *
	 * @return bool
	 */
	public function persist( $session );

	/**
	 * Removes session
	 *
	 * @param string $key
	 * @param string $hash
	 *
	 * @return bool
	 */
	public function remove( $key, $hash );

}