<?php

class DLM_CRON_Jobs {

	/**
	 * Holds the class object.
	 *
	 * @since 4.4.7
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	private function __construct() {
		// Set the weekly interval.
		add_filter( 'cron_schedules', array( $this, 'create_weekly_cron_schedule' ) );
		add_action( 'admin_init', array( $this, 'set_weekly_cron_schedule' ) );
		add_action( 'dlm_weekly_license', array( $this, 'general_license_validity' ) );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Admin_Helper object.
	 * @since 4.4.7
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_CRON_Jobs ) ) {
			self::$instance = new DLM_CRON_Jobs();
		}

		return self::$instance;

	}

	/**
	 * Create dlm_weekly cron schedule.
	 *
	 * @param array $schedule Array of schedules.
	 *
	 * @return array
	 * @since 4.8.6
	 */
	public function create_weekly_cron_schedule( $schedule ) {
		// Set dlm_weekly cron schedule.
		$schedule['dlm_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'DLM Once Weekly', 'download-monitor' ),
		);

		return $schedule;
	}

	/**
	 * Set dlm_weekly cron schedule.
	 *
	 * @since 4.8.6
	 */
	public function set_weekly_cron_schedule() {

		if ( ! wp_next_scheduled( 'dlm_weekly_license' ) ) {
			wp_schedule_event( time(), 'dlm_weekly', 'dlm_weekly_license' );
		}
	}

	/**
	 * Check for license validity - the weekly cron job.
	 *
	 * @return void
	 * @since 4.8.6
	 */
	public function general_license_validity() {
		if ( ! class_exists( 'DLM_Product_Manager' ) || ! class_exists( 'DLM_Product_License' ) || ! class_exists( 'DLM_Admin_Helper' ) ) {
			return;
		}
		$user_license         = get_option( 'dlm_master_license', false );
		$extensions           = DLM_Admin_Extensions::get_instance();
		$installed_extensions = array();
		$prev_license         = array();
		$do_each              = false;
		$addon_licenses       = array();
		$no_licenses          = true;
		$do_master            = false;
		$license              = '';

		if ( empty( $extensions->installed_extensions ) ) {
			$product_manager = DLM_Product_Manager::get();
			$product_manager->load_extensions();
			$extensions->installed_extensions = $product_manager->get_products();
		}

		if ( ! empty( $extensions->installed_extensions ) ) {
			foreach ( $extensions->installed_extensions as $extension ) {
				if ( method_exists( $extension, 'get_product_id' ) ) {
					$installed_extensions[] = $extension->get_product_id();
				} else {
					// On deactivation hook the $extensions->installed_extensions still contains the old product_id.
					$installed_extensions[] = $extension->product_id;
				}
			}
		}

		if ( $user_license ) {
			$user_license = json_decode( $user_license, true );
			if ( isset( $user_license['license_key'] ) && '' !== $user_license['license_key'] ) {
				$do_master   = true;
				$no_licenses = false;
			}
		}

		if ( $do_master ) {
			$license = $user_license;
		} else {
			if ( ! empty( $installed_extensions ) ) {
				foreach ( $installed_extensions as $extension ) {
					$sl = get_option( $extension . '-license', false );
					if ( $sl ) {
						$addon_licenses[ $extension ] = $sl;
						$no_licenses = false;
						if ( ! empty( $prev_license ) && $prev_license['key'] !== $sl['key'] ) {
							$do_each = true;
						}
						$prev_license = $sl;
					}
				}
				$license = $prev_license;
				$license['license_key'] = $license['key'];
			}
		}

		// If there are no licenses present then we don't need to check anything.
		if ( $no_licenses ) {
			return;
		}

		// Let's see if we need to check each license, or we can check the master license.
		if ( $do_each ) {
			if ( ! empty( $addon_licenses ) ) {
				foreach ( $addon_licenses as $slug => $object ) {
					$object['license_key'] = $object['key'];
					$this->check_license( $object, array( $slug ), false );
				}
			}
		} else {
			$this->check_license( $license, $installed_extensions );
		}
	}

	/**
	 * Check license
	 *
	 * @param array $license License data.
	 * @param array $installed_extensions Array of installed extensions.
	 * @param bool $save_license Whether to save the license or not.
	 *
	 * @return void
	 * @since 4.8.6
	 */
	private function check_license(  $license, $installed_extensions, $save_license = true ){

		$api_request = wp_remote_get(
			DLM_Product::STORE_URL . DLM_Product::ENDPOINT_STATUS_CHECK . '&' . http_build_query(
				array(
					'email'          => $license['email'],
					'license_key'    => $license['license_key'],
					'api_product_id' => 'dlm-captcha',
					'instance'       => site_url(),
					'request'        => 'status_check'
				),
				'',
				'&'
			)
		);

		// Check request.
		if ( is_wp_error( $api_request ) || wp_remote_retrieve_response_code( $api_request ) != 200 ) {
			return;
		}

		$response = json_decode( $api_request['body'], true );

		if ( isset( $response['error'] ) ) {
			$this->deactivate_license( $license, $response, $installed_extensions, $save_license );
		}
	}

	/**
	 * Deactivate license
	 *
	 * @param array $user_license User license data.
	 * @param array $response Response data.
	 * @param array $extensions Array of installed extensions.
	 * @param bool $save_license Whether to save the license or not.
	 *
	 * @return void
	 * @since 4.8.6
	 */
	private function deactivate_license( $user_license, $response, $extensions, $save_license = true ) {
		$response_error_codes = array(
			'110' => 'expired',
			'101' => 'invalid',
			'111' => 'invalid_order',
			'104' => 'no_rights'
		);
		$email = $user_license['email'];
		$license_key = $user_license['license_key'];
		$data = array(
			'email'          => $email,
			'license_key'    => $license_key,
			'status'         => 'inactive',
			'license_status' => $response_error_codes[ strval( $response['error_code'] ) ]
		);

		// And error has been triggered, maybe license expired or not valid.
		foreach ( $extensions as $prod_id ) {
			$product = new DLM_Product( $prod_id, '', '' );
			$license = $product->get_license();
			$license->set_status( 'inactive' );
			$license->set_license_status( isset( $response_error_codes[ strval( $response['error_code'] ) ] ) ? $response_error_codes[ strval( $response['error_code'] ) ] : 'inactive' );
			$license->set_key( $license_key );
			$license->set_email( $email );
			$license->store();
		}

		if ( $save_license ) {
			update_option( 'dlm_master_license', json_encode( $data ) );
		}
	}
}
