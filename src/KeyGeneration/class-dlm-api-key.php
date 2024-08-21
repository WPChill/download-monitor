<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The DLM_API_Key object.
 *
 * @package DLM_Key_Generation
 */
class DLM_API_Key {

	private $public_key;
	private $secret_key;
	private $token;
	private $user_id;
	private $id;
	private $create_date;

	/**
	 * Constructor.
	 *
	 * @param  array  $data  The key data.
	 *
	 * @since 5.0.0
	 */
	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->set_data( $data );
		}
	}

	/**
	 * Set the data for the key.
	 *
	 * @param  int  $id  The key ID.
	 *
	 * @since 5.0.0
	 */
	public function get_key_by_id( $id ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dlm_api_keys WHERE ID = %s", sanitize_key( $id ) );
		$data = $wpdb->get_row( $sql );
		if ( ! $data ) {
			return false;
		}
		$this->set_data( $data );

		return true;
	}

	/**
	 * Set the data for the key.
	 *
	 * @param  string  $public_key  The public key.
	 *
	 * @since 5.0.0
	 */
	public function get_key_by_public_key( $public_key ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dlm_api_keys WHERE public_key = %s", sanitize_key( $public_key ) );
		$data = $wpdb->get_row( $sql );
		if ( ! $data ) {
			return false;
		}
		$this->set_data( $data );

		return true;
	}

	/**
	 * Set the data for the key.
	 *
	 * @param  int  $user_id  The user ID.
	 *
	 * @since 5.0.0
	 */
	public function get_key_by_user_id( $user_id ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dlm_api_keys WHERE user_id = %s", sanitize_key( $user_id ) );
		$data = $wpdb->get_row( $sql );
		if ( ! $data ) {
			return false;
		}
		$this->set_data( $data );

		return true;
	}

	/**
	 * Set the data for the key.
	 *
	 * @param  string  $token  The token.
	 *
	 * @since 5.0.0
	 */
	public function get_key_by_token( $token ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dlm_api_keys WHERE token = %s", sanitize_key( $token ) );
		$data = $wpdb->get_row( $sql );
		if ( ! $data ) {
			return false;
		}
		$this->set_data( $data );

		return true;
	}

	/**
	 * Set the data for the key.
	 *
	 * @param  string  $secret  The secret key.
	 *
	 * @since 5.0.0
	 */
	public function get_key_by_secret_key( $secret ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dlm_api_keys WHERE secret_key = %s", sanitize_key( $secret ) );
		$data = $wpdb->get_row( $sql );
		if ( ! $data ) {
			return false;
		}
		$this->set_data( $data );

		return true;
	}

	/**
	 * Set the data for the key.
	 *
	 *
	 * @since 5.0.0
	 */
	public function set_data( $data ) {
		$this->set_public_key( $data->public_key );
		$this->set_secret_key( $data->secret_key );
		$this->set_token();
		$this->set_user_id( $data->user_id );
		$this->set_id( $data->ID );
		$this->set_creation_date( $data->create_date );
	}

	/**
	 * Set the key ID.
	 *
	 * @param  int  $id  The key ID.
	 *
	 * @since 5.0.0
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the key ID.
	 *
	 * @return int The key ID.
	 * @since 5.0.0
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the public key.
	 *
	 * @param  string  $public_key  The public key.
	 *
	 * @since 5.0.0
	 */
	public function set_public_key( $public_key ) {
		$this->public_key = $public_key;
	}

	/**
	 * Get the public key.
	 *
	 * @return string The public key.
	 * @since 5.0.0
	 */
	public function get_public_key() {
		return $this->public_key;
	}

	/**
	 * Set the secret key.
	 *
	 * @param  string  $secret_key  The secret key.
	 *
	 * @since 5.0.0
	 */
	public function set_secret_key( $secret_key ) {
		$this->secret_key = $secret_key;
	}

	/**
	 * Set the creation date.
	 *
	 * @param  string  $creation_date  The creation date.
	 *
	 * @since 5.0.0
	 */
	public function set_creation_date( $creation_date ) {
		$this->create_date = $creation_date;
	}

	/**
	 * Get the secret key.
	 *
	 * @return string The secret key.
	 * @since 5.0.0
	 */
	public function get_secret_key() {
		return $this->secret_key;
	}

	/**
	 * Get the creation date.
	 *
	 * @return string The creation date.
	 * @since 5.0.0
	 */
	public function get_creation_date() {
		return $this->create_date;
	}

	/**
	 * Set the token.
	 *
	 * @since 5.0.0
	 */
	private function set_token() {
		$this->token = hash( 'md5', $this->get_secret_key() . $this->get_public_key() );
	}

	/**
	 * Get the token.
	 *
	 * @return string The token.
	 * @since 5.0.0
	 */
	public function get_token() {
		return $this->token;
	}

	/**
	 * Set the user ID.
	 *
	 * @param  int  $user_id  The user ID.
	 *
	 * @since 5.0.0
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the user ID.
	 *
	 * @return int The user ID.
	 * @since 5.0.0
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Get the user.
	 *
	 * @return WP_User The user.
	 * @since 5.0.0
	 */
	public function get_user() {
		return get_user_by( 'id', $this->user_id );
	}

	/**
	 * Update the Key
	 *
	 *
	 * @since 5.0.0
	 */
	public function update_key() {
		global $wpdb;
		$this->set_token();
		$data = array(
			'public_key' => $this->public_key,
			'secret_key' => $this->secret_key,
			'token'      => $this->token,
			'user_id'    => $this->user_id,
		);
		$wpdb->update( $wpdb->dlm_api_keys, $data, array( 'id' => $this->id ) );
	}

	/**
	 * Delete the Key
	 *
	 *
	 * @since 5.0.0
	 */
	public function delete_key() {
		global $wpdb;
		$wpdb->delete( $wpdb->dlm_api_keys, array( 'id' => $this->id ) );
	}

	/**
	 * Create the Key
	 *
	 * @return int The key ID.
	 *
	 * @since 5.0.0
	 */
	public function create_key() {
		global $wpdb;
		// If the key already exists, update it.
		if ( $this->id ) {
			$this->update_key();

			return $this->id;
		}
		$this->set_token();
		$data = array(
			'public_key'  => $this->public_key,
			'secret_key'  => $this->secret_key,
			'token'       => $this->token,
			'user_id'     => $this->user_id,
			'create_date' => current_time( 'mysql' ),
		);

		// Insert the data and return the ID.
		return $wpdb->insert( $wpdb->dlm_api_keys, $data );
	}
}
