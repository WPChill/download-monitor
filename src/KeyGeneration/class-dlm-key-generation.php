<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Handles WP Rest API Keys and Key Generation
 *
 * @package DLM_Key_Generation
 */
class DLM_Key_Generation {
	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 */
	public static $instance;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		// Load admin hooks.
		$this->load_admin_hooks();
		// Load frontend hooks.
		$this->load_frontend_hooks();
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Key_Generation object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Key_Generation ) ) {
			self::$instance = new DLM_Key_Generation();
		}

		return self::$instance;
	}

	/**
	 * Load admin hooks.
	 *
	 * @since 5.0.0
	 */
	private function load_admin_hooks() {
		// Add admin menu item.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		// Add AJAX action to generate API key.
		add_action( 'wp_ajax_dlm_action_api_key', array( $this, 'ajax_handle_api_key_actions' ) );
		add_action( 'wp_ajax_dlm_keygen_search_users', array( $this, 'ajax_search_users' ) );
	}

	/**
	 * Load frontend hooks.
	 *
	 * @since 5.0.0
	 */
	private function load_frontend_hooks() {
	}

	/**
	 * Add submenu item.
	 *
	 * @since 5.0.0
	 */
	public function add_admin_menu() {
		// Add submenu page.
		add_submenu_page(
			'edit.php?post_type=dlm_download',
			__( 'API Keys', 'download-monitor' ),
			__( 'API Keys', 'download-monitor' ),
			'manage_options',
			'dlm-api-keys',
			array( $this, 'render_api_keys_page' )
		);
	}

	/**
	 * Render API Keys page.
	 *
	 * @since 5.0.0
	 */
	public function render_api_keys_page() {
		$current_user = wp_get_current_user();
		?>
		<div class='wrap'>
			<h1 class='wp-heading-inline'><?php
				echo esc_html__( 'API Keys', 'download-monitor' ); ?></h1>
			<div class="dlm-api-keys-generator">
				<br>
				<select class="dlm-keygen-user-select">
					<option selected="selected" value="<?php echo esc_attr( $current_user->data->ID ); ?>"> <?php echo esc_html( $current_user->data->display_name . '(' . $current_user->data->user_email . ')' ); ?> </option>;
				</select>
				<button class="dlm-keygen-generate button button-secondary">Generate API Key</button>
			</div>
			<?php
			// Add your code here.
			$api_keys_table = new DLM_API_Keys_Table();
			$api_keys_table->prepare_items();
			$api_keys_table->display();
			?>
		</div>
		<?php
	}

	/**
	 * Generate new API keys for a user
	 *
	 * @param  int   $user_id     User ID the key is being generated for.
	 * @param  bool  $regenerate  Regenerate the key for the user.
	 *
	 * @return boolean True if (re)generated successfully, false otherwise.
	 * @since 5.0.0
	 *
	 */
	public function generate_api_key( $user_id = 0, $regenerate = false ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );

		if ( empty( $public_key ) || true === $regenerate ) {
			$new_public_key = $this->generate_public_key( $user->user_email );
			$new_secret_key = $this->generate_private_key( $user->ID );
		} else {
			return false;
		}

		if ( true === $regenerate ) {
			$this->revoke_api_key( $user->ID );
		}

		$api_key = new DLM_API_Key();
		$api_key->set_public_key( $new_public_key );
		$api_key->set_secret_key( $new_secret_key );
		$api_key->set_user_id( $user->ID );
		$api_key->create_key();

		return true;
	}

	/**
	 * Generate the public key for a user
	 *
	 * @param  string  $user_email  The user's email address.
	 *
	 * @return string
	 * @since  5.0.0
	 *
	 */
	public function generate_public_key( $user_email = '' ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$public   = hash( 'md5', $user_email . $auth_key . date( 'U' ) );

		return $public;
	}

	/**
	 * Generate the secret key for a user
	 *
	 * @param  int  $user_id  The user's ID.
	 *
	 * @return string
	 * @since 5.0.0
	 *
	 */
	public function generate_private_key( $user_id = 0 ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secret   = hash( 'md5', $user_id . $auth_key . date( 'U' ) );

		return $secret;
	}

	/**
	 * Revoke a users API keys
	 *
	 * @param  int  $user_id  User ID of user to revoke key for.
	 *
	 * @return string
	 * @throws Exception
	 * @since 5.0.0
	 *
	 */
	public function revoke_api_key( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );
		if ( ! empty( $public_key ) ) {
			$api_key = new DLM_API_Key();
			if ( $api_key->get_key_by_public_key( $public_key ) ) {
				$api_key->delete_key();
				delete_transient( md5( 'edd_api_user_public_key' . $user_id ) );
				delete_transient( md5( 'edd_api_user_secret_key' . $user_id ) );
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Get a user's public key.
	 *
	 * @param  int  $user_id  User ID.
	 *
	 * @return string
	 */
	public function get_user_public_key( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}
		$cache_key       = md5( 'edd_api_user_public_key' . $user_id );
		$user_public_key = get_transient( $cache_key );
		if ( empty( $user_public_key ) ) {
			$sql  = $wpdb->prepare( "SELECT public_key FROM {$wpdb->prefix}dlm_api_keys WHERE user_id = %s", absint( $user_id ) );
			$user_public_key = $wpdb->get_var( $sql  );
			set_transient( $cache_key, $user_public_key, HOUR_IN_SECONDS );
		}

		return $user_public_key;
	}

	/**
	 * Get a users's secret key.
	 *
	 * @param  int  $user_id  User ID.
	 *
	 * @return string
	 */
	public function get_user_secret_key( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}

		$cache_key       = md5( 'edd_api_user_secret_key' . $user_id );
		$user_secret_key = get_transient( $cache_key );

		if ( empty( $user_secret_key ) ) {
			$sql  = $wpdb->prepare( "SELECT secret_key FROM {$wpdb->prefix}dlm_api_keys WHERE user_id = %s", absint( $user_id ) );
			$user_secret_key = $wpdb->get_var( $sql  );
			set_transient( $cache_key, $user_secret_key, HOUR_IN_SECONDS );
		}

		return $user_secret_key;
	}

	/**
	 * Get users for keygen select
	 *
	 *
	 * @return json array
	 */
	public function ajax_search_users() {
		$term = isset($_GET['q']) ? trim(wp_unslash($_GET['q'])) : '';

		check_ajax_referer( 'dlm_ajax_nonce', '_ajax_nonce' );
		
		$args = array(
			'search' => '*' . esc_attr($term) . '*',
			'search_columns' => array('user_login', 'user_nicename', 'user_email', 'display_name'),
			'number' => 10, // Limit the number of results
		);
	
		$user_query = new WP_User_Query($args);
		$users = $user_query->get_results();
	
		$results = array();
	
		if (!empty($users)) {
			foreach ($users as $user) {
				$results[] = array(
					'id' => $user->ID,
					'text' => $user->display_name . '(' . $user->user_email . ')',
				);
			}
		}
	
		wp_send_json($results);
	}

	
	/**
	 * Ajax handler to generate/regenerate/revoke API keys
	 *
	 *
	 * @return bool
	 */
	public function ajax_handle_api_key_actions() {

		if( ! isset( $_POST['dlm_action'] ) || '' == $_POST['dlm_action'] ){
			wp_send_json_error( 'No action given.');
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if( 0 === $user_id ){
			wp_send_json_error( 'User id not set.');
		}

		check_ajax_referer( 'dlm_ajax_nonce', '_ajax_nonce' );

		switch ( $_POST['dlm_action']) {
			case 'generate':
			case 'regenerate':

				$results = $this->generate_api_key( $user_id, true );
			
				if( $results ){
					wp_send_json_success( $results );
				}
				wp_send_json_error( 'API key generation failed.' );
				break;
			case 'revoke':
				if( $this->revoke_api_key( $user_id ) ){
					wp_send_json_success( 'API key revoked successfully'  );
				}
				wp_send_json_error( 'API key revocation failed.' );
				break;
		}
	}
}
