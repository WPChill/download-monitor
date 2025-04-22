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
		add_action( 'admin_init', array( $this, 'set_monthly_cron_schedule' ) );
		add_action( 'dlm_weekly_license', array( $this, 'general_license_validity' ) );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_CRON_Jobs object.
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

		// Set dlm_weekly cron schedule.
		$schedule['dlm_monthly'] = array(
			'interval' => MONTH_IN_SECONDS,
			'display'  => __( 'DLM Once Monthly', 'download-monitor' ),
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
	 * Set dlm_monthly cron schedule.
	 *
	 * @since 4.9.5
	 */
	public function set_monthly_cron_schedule() {
		if ( ! wp_next_scheduled( 'dlm_monthly_event' ) ) {
			wp_schedule_event( time(), 'dlm_monthly', 'dlm_monthly_event' );
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
		$main_license         = get_option( 'dlm_master_license', false );
		$extensions           = DLM_Admin_Extensions::get_instance();
		$installed_extensions = array();

		// keep the license as key and slug of extensions as value
		$licenses = array();
		// keep the license as key and email of license as value
		$licenses_info = array();

		if ( empty( $extensions->installed_extensions ) ) {
			$product_manager = DLM_Product_Manager::get();
			$product_manager->load_extensions();
			$extensions->installed_extensions = $product_manager->get_products();
		}

		if ( ! empty( $extensions->installed_extensions ) ) {
			foreach ( $extensions->installed_extensions as $extension ) {

				$extension_slug = '';

				if ( method_exists( $extension, 'get_product_id' ) ) {
					$extension_slug = $extension->get_product_id();
				} else {
					// On deactivation hook the $extensions->installed_extensions still contains the old product_id.
					$extension_slug = $extension->product_id;
				}

				$installed_extensions[] = $extension_slug;

				$sl = get_option( $extension_slug . '-license', false );
				$licenses_info[ $sl['key'] ] = $sl['email'];

				if ( $sl && ! isset( $licenses[ $sl['key'] ] ) ) {
					$licenses[ $sl['key'] ] = array( $extension_slug );
				}elseif ( isset( $licenses[ $sl['key'] ] ) ) {
					$licenses[ $sl['key'] ][] = $extension_slug;
				}
			}
		}

		$main_license = json_decode( $main_license, true );
		if ( isset( $main_license['license_key'] ) && '' !== $main_license['license_key'] ) {
			if ( ! isset( $licenses[ $main_license['license_key'] ] ) ) {
				$licenses[ $main_license['license_key'] ] = $installed_extensions;
				$licenses_info[ $main_license['key'] ] = $main_license['email'];
			}
		}

		// If there are no licenses present then we don't need to check anything.
		if ( empty( $licenses ) ) {
			return;
		}
		$i = 0;
		foreach ( $licenses as $license => $slugs ) {
			$license_obj = array( 'license_key' => $license, 'email' => $licenses_info[ $license ] );
			if ( isset( $main_license['license_key'] ) && $main_license['license_key'] == $license ) {
				// if is master license we need to save server response in dlm_master_license option
				$this->check_license( $license_obj, $slugs );
			}else{
				$this->check_license( $license_obj, $slugs, false );
			}
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
		$store_url       = DLM_Product::STORE_URL . '?wc-api=';
		$api_product_ids = implode( ',', $installed_extensions );
		if ( empty( $api_product_ids ) ) {
			// Add default to DLM PRO, as it will be present in every package.
			// @todo: This should be removed when we have a better way to handle this.
			$api_product_ids = 'dlm-pro';
		}
		$api_request = wp_remote_get(
			$store_url . DLM_Product::ENDPOINT_STATUS_CHECK . '&' . http_build_query(
				array(
					'email'          => $license['email'],
					'license_key'    => $license['license_key'],
					'api_product_id' => $api_product_ids,
					'instance'       => site_url(),
					'request'        => 'status_check',
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
