<?php

namespace Never5\DownloadMonitor\Ecommerce\Session;

class WordPressRepository implements Repository {

	/**
	 * @param string $key
	 * @param string $hash
	 *
	 * @return Session
	 * @throws \Exception
	 */
	public function retrieve( $key, $hash ) {
		global $wpdb;

		// try to fetch session from database
		$r = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . $wpdb->prefix . "dlm_session` WHERE `key` = %s AND `hash` = %s ;",
				$key,
				$hash
			)
		);

		// check if result if found
		if ( null == $r ) {
			throw new \Exception( 'Session not found' );
		}

		// json decode data field
		$data = json_decode( $r->data );

		// create session object
		$session = new Session();
		$session->set_key( $r->key );
		$session->set_hash( $r->hash );
		$session->set_expiry( new \DateTimeImmutable( $r->expiry ) );

		if ( isset( $data->items ) ) {
			$session->set_items( $data->items );
		}

		if ( isset( $data->coupons ) ) {
			$session->set_coupons( $data->coupons );
		}

		return $session;
	}

	public function persist( $session ) {
		global $wpdb;

		// prepare data
		$data = json_encode( array(
			'items'   => $session->get_items(),
			'coupons' => $session->get_coupons()
		) );

		// delete previous session in database
		$wpdb->delete( $wpdb->prefix . 'dlm_session', array(
			'key'  => $session->get_key(),
			'hash' => $session->get_hash()
		), array( '%s', '%s' ) );

		// insert new session
		$wpdb->insert(
			$wpdb->prefix . 'dlm_session',
			array(
				'key'    => $session->get_key(),
				'hash'   => $session->get_hash(),
				'expiry' => $session->get_expiry()->format( 'Y-m-d H:i:s' ),
				'data'   => $data
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

	}

}