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
			'display'  => __( 'Once Weekly', 'download-monitor' ),
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

		$product_manager   = DLM_Product_Manager::get();
		$extension_handler = DLM_Extensions_Handler::get_instance();

		// Log to file also.
		global $wp_filesystem;
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
		$wp_filesystem->put_contents( __DIR__ . '/log_file.txt', 'start here ===>' );

		$user_license = get_option( 'dlm_master_license', false );
		if ( $user_license ) {
			$api_request = wp_remote_get(
				DLM_Product::STORE_URL . DLM_Product::ENDPOINT_ACTIVATION . '&' . http_build_query(
					array(
						'email'          => $user_license['email'],
						'licence_key'    => $user_license['key'],
						'api_product_id' => '',
						'instance'       => site_url(),
					),
					'',
					'&'
				)
			);
			$wp_filesystem->put_contents( __DIR__ . '/log_file.txt', $api_request['body'] );

			// Check request.
			if ( is_wp_error( $api_request ) || wp_remote_retrieve_response_code( $api_request ) != 200 ) {
				$extension_handler->handle_master_license(
					array(
						'key'              => $user_license['key'],
						'email'            => $user_license['email'],
						'extension_action' => 'deactivate'
					) );
			}
			$response = json_decode( $api_request['body'], true );
			if ( isset( $response['error'] ) ) {
				$this->deactivate_license();
			}
		} else {
			$product_manager->load_extensions();
			$extensions = $product_manager->get_products();
			if ( ! empty( $extensions ) ) {
				foreach ( $extensions as $slug => $extension ) {
					$old_text = $wp_filesystem->get_contents( __DIR__ . '/log_file.txt' );
					$text     = $old_text ? $old_text . "\n" . $slug : $slug;
					// Need double quotes around the \n to make it work.
					$wp_filesystem->put_contents( __DIR__ . '/log_file.txt', $text );
					$result = $extension_handler->handle_extension_action( 'deactivate', array(
						'slug' => $slug,
						'name' => $extension->get_product_name()
					) );
					$wp_filesystem->put_contents( __DIR__ . '/log_file.txt', $result );
				}
			}
		}
	}

	public function deactivate_license(){
		$user_license = get_option( 'dlm_master_license', false );
		// If no license found, skip this.
		if ( ! $user_license ) {
			return;
		}

		$user_license = json_decode( $user_license, true );
		$email        = $user_license['email'];
		$license_key  = $user_license['license_key'];
		$data_request = array(
			'key'   => $license_key,
			'email' => $email,
		);
		if ( ! isset( $data_request['key'] ) || ! isset( $data_request['email'] ) || ! isset( $data_request['extension_action'] ) ) {
			return;
		}

		// Post vars.
		$license_key          = isset( $data_request['key'] ) ? sanitize_text_field( wp_unslash( $data_request['key'] ) ) : '';
		$email                = isset( $data_request['email'] ) ? sanitize_text_field( wp_unslash( $data_request['email'] ) ) : '';
		$installed_extensions = array();
		$data                 = array(
			'email'       => $email,
			'license_key' => $license_key,
			'status'      => 'inactive',
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
			DLM_Product::STORE_URL . DLM_Product::ENDPOINT_STATUS_CHECK . '&' . http_build_query(
				array(
					'email'          => $email,
					'licence_key'    => $license_key,
					'api_product_id' => implode( ',', $installed_extensions ),
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

		// And error has been triggered, maybe license expired or not valid.
		if ( isset( $activated_extensions['error'] ) ) {
			foreach ( $installed_extensions as $prod_id ) {
				$product = new DLM_Product( $prod_id, '', '' );
				$license = $product->get_license();
				$license->set_status( 'inactive' );
				$license->store();
			}
			$data['status'] = 'inactive';
			update_option( 'dlm_master_license', json_encode( $data ) );
			wp_send_json_error( array( 'message' => $activated_extensions['error'] ) );
		}
	}
}
