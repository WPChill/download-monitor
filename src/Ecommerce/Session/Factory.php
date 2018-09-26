<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

class Factory {

	/**
	 * Generate key
	 *
	 * @return string
	 */
	private function generate_key() {
		return md5( uniqid( 'dlm_ecommerce_session_key', true ) . $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Generate hash
	 *
	 * @param $key
	 *
	 * @return string
	 */
	private function generate_hash( $key ) {
		$nonce = ( defined( 'NONCE_SALT' ) ? NONCE_SALT : 'nononce' );

		return md5( uniqid( 'dlm_ecommerce_session_hash', true ) . mt_rand( 0, 99 ) . $_SERVER['REMOTE_ADDR'] . $nonce . $key );
	}

	/**
	 * Make new session with unique key and hash
	 *
	 * @return Session
	 */
	public function make() {

		$session = new Session();
		$session->set_key( $this->generate_key() );
		$session->set_hash( $this->generate_hash( $session->get_key() ) );

		try {
			$expiry = new \DateTimeImmutable();
			$expiry = $expiry->modify( '+' . apply_filters( 'dlm_ecommerce_session_expiry_days', 7 ) . 'days' );
			$session->set_expiry( $expiry );
		} catch ( \Exception $e ) {

		}

		return $session;

	}

}