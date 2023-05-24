<?php
/**
 * Extensions Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use \WPChill\DownloadMonitor\Util;

/**
 * DLM_Extensions_Handler Class
 */
class DLM_Extensions_Handler {
	/**
	 * Holds the class object.
	 *
	 * @since 4.8.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * DLM_Extensions_Handler constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_dlm_extension', array( $this, 'handle_extensions' ) );
		add_action( 'wp_ajax_dlm_master_license', array( $this, 'handle_master_license' ) );
	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Extensions_Handler object.
	 * @since 4.8.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Extensions_Handler ) ) {
			self::$instance = new DLM_Extensions_Handler();
		}

		return self::$instance;
	}

	/**
	 * Handle extensions actions
	 *
	 * @param $request
	 *
	 * @return void
	 */
	public function handle_extension_action( $request = 'deactivate', $extension = '' ) {
		$user_license = get_option( 'dlm_master_license', false );

		if ( ! $user_license ) {
			$user_license = '';
			$email        = '';
			$license_key  = '';
		} else {
			$user_license = json_decode( $user_license, true );
			$email        = $user_license['email'];
			$license_key  = $user_license['license_key'];
		}

		$action_trigger = '-ext';

		// Do activate request.
		$api_request = wp_remote_get(
			DLM_Product::STORE_URL . DLM_Product::ENDPOINT_ACTIVATION . '&' . http_build_query(
				array(
					'email'          => $email,
					'licence_key'    => $license_key,
					'api_product_id' => $extension['slug'],
					'request'        => $request,
					'instance'       => site_url(),
					'action_trigger' => $action_trigger,
				),
				'',
				'&'
			)
		);

		// Check request.
		if ( is_wp_error( $api_request ) || wp_remote_retrieve_response_code( $api_request ) != 200 ) {
			wp_send_json_error( array( 'message' => __( 'Could not connect to the license server', 'download-monitor' ) ) );
		}

		$product = new DLM_Product( $extension['slug'], '', $extension['name'] );
		$license = $product->get_license();

		if ( ! empty( $license_key ) ) {
			$license->set_key( $license_key );
		}
		if ( ! empty( $email ) ) {
			$license->set_email( $email );
		}

		if ( ! isset( $api_request['body'] ) ) {
			$license->set_status( 'inactive' );
			return;
		}

		$response_body = json_decode(wp_remote_retrieve_body($api_request), true);

		if ( isset( $response_body['error'] ) ) {
			$license->set_status( 'inactive' );
			return;
		}

		if ( 'deactivate' === $request ) {
			$license->set_status( 'inactive' );
		} else {
			$license->set_status( 'active' );
		}

		$license->store();
		$this->handle_master_license_maybe( $user_license, $request, $extension['slug'] );
	}


	/**
	 * Handle extensions AJAX
	 */
	public function handle_extensions() {

		// Check nonce
		check_ajax_referer( 'dlm-ajax-nonce', 'nonce' );

		// Post vars
		$product_id       = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : 0;
		$key              = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$email            = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
		$extension_action = isset( $_POST['extension_action'] ) ? sanitize_text_field( wp_unslash( $_POST['extension_action'] ) ) : 'activate';

		// Get products
		$products = DLM_Product_Manager::get()->get_products();

		// Check if product exists
		$response = '';
		if ( isset( $products[ $product_id ] ) ) {

			// Get correct product
			/** @var DLM_Product $product */
			$product = $products[ $product_id ];

			// Set new key in license object
			$product->get_license()->set_key( $key );

			// Set new email in license object
			$product->get_license()->set_email( $email );

			if ( 'activate' === $extension_action ) {
				// Try to activate the license
				$response = $product->activate();
			} else {
				// Try to deactivate the license
				$response = $product->deactivate();
			}
		}

		$this->handle_master_license_maybe( false, $extension_action, $product_id );

		// Send JSON
		wp_send_json( $response );
	}

	/**
	 * Handle extensions AJAX
	 *
	 * @since 4.8.0
	 */
	public function handle_master_license( $data_request = false ) {

		$ajax_request = false;
		if ( ! $data_request ) {
			// Check nonce.
			check_ajax_referer( 'dlm-ajax-nonce', 'nonce' );
			$data_request = $_POST;
			$ajax_request = true;
		}

		if ( ! isset( $data_request['key'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing license key.', 'download-monitor' ) ) );
		}

		if ( ! isset( $data_request['email'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing email address.', 'download-monitor' ) ) );
		}

		if ( ! isset( $data_request['extension_action'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong, please try again.', 'download-monitor' ) ) );
		}

		// Post vars.
		$license_key          = isset( $data_request['key'] ) ? sanitize_text_field( wp_unslash( $data_request['key'] ) ) : '';
		$email                = isset( $data_request['email'] ) ? sanitize_text_field( wp_unslash( $data_request['email'] ) ) : '';
		$request              = isset( $data_request['extension_action'] ) ? sanitize_text_field( wp_unslash( $data_request['extension_action'] ) ) : '';
		$action_trigger       = isset( $data_request['action_trigger'] ) ? sanitize_text_field( wp_unslash( $data_request['action_trigger'] ) ) : '';
		$installed_extensions = array();
		$data                 = array(
			'email'       => $email,
			'license_key' => $license_key,
			'status'      => ( 'activate' === $request ) ? 'active' : 'inactive',
		);
		$extensions = DLM_Admin_Extensions::get_instance();

		if ( empty( $extensions->installed_extensions ) ) {
			$product_manager = DLM_Product_Manager::get();
			$product_manager->load_extensions();
			$extensions->installed_extensions = $product_manager->get_products();
		}

		foreach ( $extensions->installed_extensions as $extension ) {
			if ( method_exists( $extension, 'get_product_id' ) ) {
				$installed_extensions[] = $extension->get_product_id();
			} else {
				// On deactivation hook the $extensions->installed_extensions still contains the old product_id.
				$installed_extensions[] = $extension->product_id;
			}
		}

		// Do activate request.
		$api_request = wp_remote_get(
			DLM_Product::STORE_URL . DLM_Product::ENDPOINT_ACTIVATION . '&' . http_build_query(
				array(
					'email'          => $email,
					'licence_key'    => $license_key,
					'api_product_id' => implode( ',', $installed_extensions ),
					'request'        => $request,
					'instance'       => site_url(),
					'action_trigger' => $action_trigger,
				),
				'',
				'&'
			)
		);

		// Check request.
		if ( is_wp_error( $api_request ) || wp_remote_retrieve_response_code( $api_request ) != 200 ) {
			update_option( 'dlm_master_license', json_encode( $data ) );
			if ( $ajax_request ) {
				wp_send_json_error( array( 'message' => __( 'Could not connect to the license server', 'download-monitor' ) ) );
			}
		}

		$activated_extensions = json_decode( wp_remote_retrieve_body( $api_request ), true );
		update_option( 'dlm_master_license', json_encode( $data ) );

		// Get products.
		$products = DLM_Product_Manager::get()->get_products();

		if ( ! empty( $activated_extensions ) ) {
			foreach ( $activated_extensions as $key => $extension ) {
				if ( ! isset( $products[ $key ] ) ) {
					continue;
				}
				$product = new DLM_Product( $key, '', $extension );
				$license = $product->get_license();
				$license->set_key( $license_key );
				$license->set_email( $email );
				if ( 'activate' === $request ) {
					$license->set_status( 'active' );
				} else {
					$license->set_status( 'inactive' );
				}
				$license->store();
			}
		}

		if ( $ajax_request ) {
			// Send JSON.
			wp_send_json_success( array( 'message' => __( 'Master license updated', 'download-monitor' ) ) );
		}
	}

	/**
	 * Activate/Deactivate master license maybe
	 *
	 * @param array|bool $master_license The master license.
	 * @param string     $request Request action.
	 *
	 * @return void
	 */
	private function handle_master_license_maybe( $master_license, $request, $current_extension = false ) {
		$extensions          = DLM_Admin_Extensions::get_instance();
		$all_activated       = true;
		$licensed_extensions = $extensions->get_licensed_extensions();
		// Check if master license is set.
		if ( ! $master_license ) {
			$master_license = get_option( 'dlm_master_license', false );
			if ( $master_license ) {
				$master_license = json_decode( $master_license, true );
			} else {
				return;
			}
		}
		if ( 'deactivate' === $request ) {
			$all_activated = false;
		} else {
			foreach ( $extensions->installed_extensions as $extension ) {
				if ( $current_extension && $current_extension === $extension->product_id ) {
					continue;
				}
				if ( ! in_array( $extension->product_id, $licensed_extensions ) ) {
					$all_activated = false;
				}
			}
		}

		$master_license['status'] = ( $all_activated ) ? 'active' : 'inactive';
		update_option( 'dlm_master_license', json_encode( $master_license ) );
	}
}
