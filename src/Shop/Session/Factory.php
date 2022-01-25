<?php

namespace WPChill\DownloadMonitor\Shop\Session;

class Factory {

	/**
	 * Generate key
	 *
	 * @return string
	 */
	private function generate_key() {
		return md5( uniqid( 'dlm_shop_session_key', true ) . isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
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

		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return md5( uniqid( 'dlm_shop_session_hash', true ) . mt_rand( 0, 99 ) . $remote_addr . $nonce . $key );
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

		$session->set_items( array() );
		$session->set_coupons( array() );

		$session->reset_expiry();

		return $session;

	}

}