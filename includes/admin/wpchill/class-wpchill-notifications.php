<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPChill_Notifications' ) ) {
	//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	class WPChill_Notifications {

		public static $instance;

		public static $notification_prefix   = 'wpchill_notification_';
		public static $blocked_notifications = 'wpchill_blocked_notifications';
		private $hook_name                   = 'wpchill_notifications_remote';

		public function __construct() {

			if ( ! wp_next_scheduled( $this->hook_name ) ) {
				wp_schedule_event( time(), 'daily', $this->hook_name );
			}

			add_action( $this->hook_name, array( $this, 'get_remote_notices' ) );

			if ( ! class_exists( 'WPChill_Rest_Api' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'class-wpchill-rest-api.php';
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'notification_system_scripts' ) );

			new WPChill_Rest_Api();
		}

		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPChill_Notifications ) ) {
				self::$instance = new WPChill_Notifications();
			}

			return self::$instance;
		}

		public static function add_notification( $key, $notification ) {
			$blocked = get_option( self::$blocked_notifications, array() );
			if ( in_array( $key, $blocked, true ) ) {
				return;
			}

			// Set the timestamp but only if not previously set.
			if ( ! isset( $notification['timestamp'] ) ) {
				$notification['timestamp'] = strtotime( current_time( 'mysql' ) );
			}

			update_option( self::$notification_prefix . $key, $notification );
		}

		public static function remove_notification( $key ) {
			delete_option( self::$notification_prefix . $key );
		}

		public function get_notifications() {
			$notifications = array(
				'error'   => array(),
				'warning' => array(),
				'success' => array(),
				'info'    => array(),
			);

			$options = $this->_get_options_wildcard( self::$notification_prefix . '%' );

			foreach ( $options as $option ) {
				$id = explode( '_', $option['option_name'] );
				$id = end( $id );

				if ( ! isset( $option['option_value'] ) ) {
					continue;
				}

				$current_notifications = maybe_unserialize( $option['option_value'] );

				if ( empty( $current_notifications ) || empty( $current_notifications['message'] ) ) {
					continue;
				}

				$status = isset( $current_notifications['status'] ) ? $current_notifications['status'] : 'info';

				if ( isset( $current_notifications['source'] ) && isset( $current_notifications['source']['slug'] ) ) {
					//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					$current_notifications['source']['icon'] = apply_filters( 'wpchill_notification_icon', plugin_dir_url( __FILE__ ) . 'icons/' . $current_notifications['source']['slug'] . '.svg', $current_notifications );
				}

				$time_ago = $current_notifications['timestamp'] ? human_time_diff( $current_notifications['timestamp'], strtotime( current_time( 'mysql' ) ) ) : false;

				$notifications[ $status ][] = array(
					'id'          => $id,
					'title'       => isset( $current_notifications['title'] ) ? $current_notifications['title'] : 'Notification',
					'message'     => $current_notifications['message'],
					'dismissible' => isset( $current_notifications['dismissible'] ) ? $current_notifications['dismissible'] : true,
					'actions'     => isset( $current_notifications['actions'] ) ? $current_notifications['actions'] : array(),
					'timed'       => isset( $current_notifications['timed'] ) ? $current_notifications['timed'] : false,
					'source'      => isset( $current_notifications['source'] ) ? $current_notifications['source'] : array(),
					// Translators: %s represents the time elapsed (e.g., "5 minutes", "2 hours", "1 day").
					'time_ago'    => $time_ago ? sprintf( esc_html__( '%s ago', 'download-monitor' ), $time_ago ) : false,
				);
			}

			//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$notifications = apply_filters( 'wpchill_notifications', $notifications );

			return $notifications;
		}

		private function _get_options_wildcard( $option_pattern ) {
			global $wpdb;

			$options = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",
					$option_pattern
				),
				ARRAY_A
			);

			return $options;
		}

		public function clear_notification( $key, $permanent = false ) {
			if ( $permanent ) {
				$blocked   = get_option( self::$blocked_notifications, array() );
				$blocked[] = $key;
				update_option( self::$blocked_notifications, $blocked );
			}

			delete_option( self::$notification_prefix . $key );
		}

		public function clear_notifications( $prefix = false ) {
			$slug    = $prefix ? $prefix : self::$notification_prefix;
			$options = $this->_get_options_wildcard( $slug . '%' );

			foreach ( $options as $option ) {
				if ( isset( $option['option_name'] ) ) {
					delete_option( $option['option_name'] );
				}
			}
		}

		public function get_remote_notices() {
			$response = wp_remote_get( 'https://download-monitor.com/wp-json/notifications/v1/get' );

			if ( is_wp_error( $response ) ) {
				return;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
				return;
			}

			$body          = wp_remote_retrieve_body( $response );
			$notifications = json_decode( $body, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return;
			}

			foreach ( $notifications as $key => $notification ) {
				$this->add_notification( $key, $notification );
			}
		}

		public function notification_system_scripts() {

			if ( ! $this->is_wpchill_admin_page() || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$wpchill_path = plugin_dir_path( __FILE__ );
			$wpchill_url  = plugin_dir_url( __FILE__ );

			$asset_file = require $wpchill_path . 'scripts/notification-system/notification-system.asset.php';
			$enqueue    = array(
				'handle'       => 'wpchill-notification-system',
				'dependencies' => $asset_file['dependencies'],
				'version'      => $asset_file['version'],
				'script'       => $wpchill_url . 'scripts/notification-system/notification-system.js',
				'style'        => $wpchill_url . 'scripts/notification-system/notification-system.css',
			);

			wp_enqueue_script(
				$enqueue['handle'],
				$enqueue['script'],
				$enqueue['dependencies'],
				$enqueue['version'],
				true
			);

			wp_enqueue_style(
				$enqueue['handle'],
				$enqueue['style'],
				array( 'wp-components' ),
				$enqueue['version']
			);
		}

		/**
		 * Check if we are on a wpchill plugin admin page
		 *
		 * @return bool
		 *
		 */
		private function is_wpchill_admin_page() {
			$screen = get_current_screen();

			//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$allowed_screens = apply_filters( 'wpchill_notifications_allowed_screens', array( 'modula-gallery', 'modula-albums', 'dlm_download', 'wpm-testimonial' ) );

			foreach ( $allowed_screens as $allowed_screen ) {
				if ( false !== strpos( $screen->id, $allowed_screen ) ) {
					return true;
				}
			}

			return false;
		}
	}
	WPChill_Notifications::get_instance();
}
