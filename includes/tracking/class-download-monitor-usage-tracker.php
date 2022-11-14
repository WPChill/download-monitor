<?php
/**
 * This is the class that sends all the data back to the home site
 * It also handles opting in and deactivation
 *
 * @version 1.2.5
 *
 * @package dlm
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Download_Monitor_Usage_Tracker' ) ) {

	/**
	 * Main class for our usage tracker
	 */
	class Download_Monitor_Usage_Tracker {

		/**
		 * Current wisdom plugin version.
		 *
		 * @var string
		 */

		private $wisdom_version = '1.2.4';

		/**
		 * Receiver url
		 *
		 * @var string
		 */
		private $home_url = '';

		/**
		 * File path for plugin
		 *
		 * @var string
		 */
		private $plugin_file = '';

		/**
		 * Plugin name
		 *
		 * @var string
		 */
		private $plugin_name = '';

		/**
		 * Plugin options to track
		 *
		 * @var array
		 */
		private $options = array();

		/**
		 * Where user opt-in is requireed
		 *
		 * @var bool
		 */
		private $require_optin = true;

		/**
		 * Whether to include a for when the user deactivates
		 *
		 * @var bool
		 */
		private $include_goodbye_form = true;

		/**
		 * Marketing method:
		 *  0: Don't collect email addresses
		 *  1: Request permission same time as tracking opt-in
		 *  2: Request permission after opt-in
		 *
		 * @var bool
		 */
		private $marketing = false;

		/**
		 * Collect email
		 *
		 * @var bool
		 */
		private $collect_email = false;

		/**
		 * Tracking object
		 *
		 * @var string
		 */
		private $what_am_i = 'plugin';

		/**
		 * If theme and tracking
		 *
		 * @var bool
		 */
		private $theme_allows_tracking = 0;

		/**
		 * Tracking object
		 *
		 * @var string
		 */
		private $active_plugins = array();

		/**
		 * Main construct function for our class
		 *
		 * @param string $_plugin_file          plugin file.
		 * @param string $_home_url             homepage url.
		 * @param array  $_options              array.
		 * @param bool   $_require_optin        defaults to true.
		 * @param bool   $_include_goodbye_form defaults to true.
		 * @param bool   $_marketing            defauts to false.
		 */
		public function __construct(
				$_plugin_file,
				$_home_url,
				$_options,
				$_require_optin = true,
				$_include_goodbye_form = true,
				$_marketing = false ) {

			$this->plugin_file = $_plugin_file;
			$this->home_url    = trailingslashit( $_home_url );

			// If the filename is 'functions' then we're tracking a theme.
			if ( basename( $this->plugin_file, '.php' ) != 'functions' ) {
				$this->plugin_name = 'download-monitor';
			}

			$this->options              = $_options;
			$this->require_optin        = $_require_optin;
			$this->include_goodbye_form = $_include_goodbye_form;
			$this->marketing            = $_marketing;

			$this->active_plugins = get_option( 'active_plugins', array() );


			register_activation_hook( $this->plugin_file, array( $this, 'schedule_tracking' ) );
			register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate_this_plugin' ) );

			// Get it going.
			$this->init();

		}

		/**
		 * Init function hooked to construct.
		 */
		public function init() {

			// Check marketing.
			if ( 3 === $this->marketing ) {
				$this->set_can_collect_email( true, $this->plugin_name );
			}

			// Check whether opt-in is required.
			// If not, then tracking is allowed.
			if ( ! $this->require_optin ) {
				$this->set_can_collect_email( true, $this->plugin_name );
				$this->set_is_tracking_allowed( true );
				$this->update_block_notice();
				$this->do_tracking();
			}

			// Hook our do_tracking function to the weekly action.
			add_filter( 'cron_schedules', array( $this, 'schedule_weekly_event' ) );
			// It's called weekly, but in fact it could be daily, weekly or monthly.
			add_action( 'wpchill_do_weekly_action', array( $this, 'do_tracking' ) );

			// Use this action for local testing.
			//add_action( 'admin_init', array( $this, 'do_tracking' ) );

			// Display the admin notice on activation.
			add_action( 'admin_init', array( $this, 'set_notification_time' ) );
			add_action( 'admin_notices', array( $this, 'optin_notice' ), 8 );
			add_action( 'admin_notices', array( $this, 'marketing_notice' ), 8 );

			// Deactivation.
			add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'filter_action_links' ) );
			add_action( 'admin_footer-plugins.php', array( $this, 'goodbye_ajax' ) );
			add_action( 'wp_ajax_'.$this->plugin_name.'_goodbye_form', array( $this, 'goodbye_form_callback' ) );

			add_filter( 'dlm_uninstall_db_options', array( $this, 'uninstall_options' ) );

			add_filter( 'dlm_settings', array( $this, 'optin_tracking' ), 30, 1 );

			add_filter( 'dlm_settings_defaults', array( $this, 'default_tracking' ) );

		}

		/**
		 * When the plugin is activated
		 * Create scheduled event
		 * And check if tracking is enabled - perhaps the plugin has been reactivated
		 *
		 * @since 1.0.0
		 */
		public function schedule_tracking() {
			if ( ! wp_next_scheduled( 'wpchill_do_weekly_action' ) ) {
				$schedule = $this->get_schedule();
				wp_schedule_event( time(), $schedule, 'wpchill_do_weekly_action' );
			}
			$this->do_tracking();
		}

		/**
		 * Create weekly schedule.
		 *
		 * @param array $schedules schedules intervals.
		 *
		 * @since 1.2.3
		 */
		public function schedule_weekly_event( $schedules ) {
			$schedules['weekly']  = array(
					'interval' => 604800,
					'display'  => __( 'Once Weekly' ),
			);
			$schedules['monthly'] = array(
					'interval' => 2635200,
					'display'  => __( 'Once Monthly' ),
			);
			return $schedules;
		}

		/**
		 * Get how frequently data is tracked back
		 *
		 * @since 1.2.3
		 */
		public function get_schedule() {
			// Could be daily, weekly or monthly.
			$schedule = apply_filters( 'wisdom_filter_schedule_' . $this->plugin_name, 'monthly' );
			return $schedule;
		}

		/**
		 * This is our function to get everything going
		 * Check that user has opted in
		 * Collect data
		 * Then send it back
		 *
		 * // FOR TESTING PURPOSES FORCE IS SET TO TRUE. BY DEFAULT IT IS FALSE .
		 *
		 * @since 1.0.0
		 */
		public function do_tracking() {

			// If the home site hasn't been defined, we just drop out. Nothing much we can do.
			if ( ! $this->home_url ) {
				return;
			}

			// Check to see if the user has opted in to tracking.
			if ( ! $this->get_is_tracking_allowed() ) {
				return;
			}

			// Get our data.
			$body = $this->get_data();

			// Send the data.
			$this->send_data( $body );

		}


		/**
		 * Send the data to the home site
		 *
		 * @since 1.0.0
		 *
		 * @param array $body body of the post.
		 */
		public function send_data( $body ) {

			$request = wp_remote_post(
					esc_url( $this->home_url . '?usage_tracker=hello' ),
					array(
							'method'      => 'POST',
							'timeout'     => 20,
							'redirection' => 5,
							'httpversion' => '1.1',
							'blocking'    => true,
							'body'        => $body,
							'user-agent'  => 'PUT/1.0.0; ' . home_url(),
					)
			);

			$this->set_track_time();

			if ( is_wp_error( $request ) ) {
				return $request;
			}

		}

		/**
		 * Here we collect most of the data
		 *
		 * @since 1.0.0
		 */
		public function get_data() {

			// Use this to pass error messages back if necessary.
			$body['message'] = '';

			// Use this array to send data back.
			$body = array(
					'plugin_slug'    => sanitize_text_field( $this->plugin_name ),
					'url'            => home_url(),
					'site_name'      => get_bloginfo( 'name' ),
					'site_version'   => get_bloginfo( 'version' ),
					'site_language'  => get_bloginfo( 'language' ),
					'charset'        => get_bloginfo( 'charset' ),
					'wisdom_version' => $this->wisdom_version,
					'php_version'    => ( defined( 'PHP_VERSION' ) ) ? PHP_VERSION : phpversion(),
					'multisite'      => is_multisite(),
					'file_location'  => __FILE__,
					'product_type'   => esc_html( $this->what_am_i ),
			);

			// Collect the email if the correct option has been set.
			if ( $this->get_can_collect_email() ) {
				$body['email'] = $this->get_admin_email();
			}
			$body['marketing_method'] = $this->marketing;

			$body['server'] = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash($_SERVER['SERVER_SOFTWARE']) ) : '';

			// Extra PHP fields.
			$body['memory_limit']        = ini_get( 'memory_limit' );
			$body['upload_max_size']     = ini_get( 'upload_max_size' );
			$body['post_max_size']       = ini_get( 'post_max_size' );
			$body['upload_max_filesize'] = ini_get( 'upload_max_filesize' );
			$body['max_execution_time']  = ini_get( 'max_execution_time' );
			$body['max_input_time']      = ini_get( 'max_input_time' );

			// Retrieve current plugin information.
			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$plugins        = array_keys( get_plugins() );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $plugin, $this->active_plugins ) ) {
					// Remove active plugins from list so we can show active and inactive separately.
					unset( $plugins[ $key ] );
				}
			}

			$body['active_plugins']   = $this->active_plugins;
			$body['inactive_plugins'] = $plugins;

			// Check text direction.
			$body['text_direction'] = 'LTR';
			if ( function_exists( 'is_rtl' ) ) {
				if ( is_rtl() ) {
					$body['text_direction'] = 'RTL';
				}
			} else {
				$body['text_direction'] = 'not set';
			}

			/**
			 * Get our plugin data
			 * Currently we grab plugin name and version
			 * Or, return a message if the plugin data is not available
			 *
			 * @since 1.0.0
			 */
			$plugin         = $this->plugin_data();
			$body['status'] = 'Active'; // Never translated.
			if ( empty( $plugin ) ) {
				// We can't find the plugin data.
				// Send a message back to our home site.
				$body['message'] .= esc_html( 'We can\'t detect any product information. This is most probably because you have not included the code snippet.');
				$body['status']   = 'Data not found'; // Never translated.
			} else {
				if ( isset( $plugin['Name'] ) ) {
					$body['plugin'] = sanitize_text_field( $plugin['Name'] );
				}
				if ( isset( $plugin['Version'] ) ) {
					$body['version'] = sanitize_text_field( $plugin['Version'] );
				}
			}

			/**
			 * Get our plugin options
			 *
			 * @since 1.0.0
			 */
			$options        = $this->options;
			$plugin_options = array();
			if ( ! empty( $options ) && is_array( $options ) ) {
				foreach ( $options as $option ) {
					$fields = get_option( $option );
					// Check for permission to send this option.
					if ( isset( $fields['wisdom_registered_setting'] ) ) {
						foreach ( $fields as $key => $value ) {
							$plugin_options[ $key ] = $value;
						}
					}
				}
			}
			$body['plugin_options']        = $this->options; // Returns array.
			$body['plugin_options_fields'] = $plugin_options; // Returns object.

			/**
			 * Get our theme data
			 * Currently we grab theme name and version
			 *
			 * @since 1.0.0
			 */
			$theme = wp_get_theme();
			if ( $theme->Name ) {
				$body['theme'] = sanitize_text_field( $theme->Name );
			}
			if ( $theme->Version ) {
				$body['theme_version'] = sanitize_text_field( $theme->Version );
			}
			if ( $theme->Template ) {
				$body['theme_parent'] = sanitize_text_field( $theme->Template );
			}

			$body['download_monitor'] = $this->get_downloads();
			// Return the data.
			return $body;

		}

		/**
		 * Return plugin data
		 *
		 * @since 1.0.0
		 */
		public function plugin_data() {
			// Being cautious here.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}
			// Retrieve current plugin information.
			$plugin = get_plugin_data( $this->plugin_file );
			return $plugin;
		}

		/**
		 * Deactivating plugin
		 *
		 * @since 1.0.0
		 */
		public function deactivate_this_plugin() {
			// Check to see if the user has opted in to tracking.
			if ( $this->what_am_i == 'theme' ) {
				$allow_tracking = $this->theme_allows_tracking;
			} else {
				$allow_tracking = $this->get_is_tracking_allowed();
			}

			if ( ! $allow_tracking ) {
				return;
			}

			$body                     = $this->get_data();
			$body['status']           = 'Deactivated'; // Never translated.
			$body['deactivated_date'] = time();

			// Add deactivation form data.
			if ( false !== get_option( 'wisdom_deactivation_reason_' . $this->plugin_name ) ) {
				$body['deactivation_reason'] = get_option( 'wisdom_deactivation_reason_' . $this->plugin_name );
			}
			if ( false !== get_option( 'wisdom_deactivation_details_' . $this->plugin_name ) ) {
				$body['deactivation_details'] = get_option( 'wisdom_deactivation_details_' . $this->plugin_name );
			}

			$this->send_data( $body );

			remove_action( 'wpchill_do_weekly_action', array( $this, 'do_tracking' ) );
			// Clear scheduled update.
			if ( ! has_action( 'wpchill_do_weekly_action' ) ) {
				wp_clear_scheduled_hook( 'wpchill_do_weekly_action' );
			}

			// Clear the wisdom_last_track_time value for this plugin.
			// @since 1.2.2
			$track_time = get_option( 'download_monitor_wisdom_last_track_time' );
			if ( isset( $track_time[ $this->plugin_name ] ) ) {
				unset( $track_time[ $this->plugin_name ] );
			}
			update_option( 'download_monitor_wisdom_last_track_time', $track_time );

		}

		/**
		 * Is tracking allowed?
		 *
		 * @since 1.0.0
		 */
		public function get_is_tracking_allowed() {

			// First, check if the user has changed their mind and opted out of tracking.
			if ( $this->has_user_opted_out() ) {
				$this->set_is_tracking_allowed( false, $this->plugin_name );
				return false;
			}

			if ( $this->what_am_i == 'theme' ) {

				$mod = get_theme_mod( 'download_monitor-wisdom-allow-tracking', 0 );
				if ( $mod ) {
					return true;
				}
			} else {

				// The wisdom_allow_tracking option is an array of plugins that are being tracked
				$allow_tracking = get_option( $this->plugin_name . '_wisdom_tracking' );
				if ( isset( $allow_tracking ) && '1' === $allow_tracking ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Set if tracking is allowed
		 * Option is an array of all plugins with tracking permitted
		 * More than one plugin may be using the tracker
		 *
		 * @since 1.0.0
		 * @param $is_allowed   Boolean     true if tracking is allowed, false if not
		 */
		public function set_is_tracking_allowed( $is_allowed, $plugin = null ) {

			if ( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}

			$block_notice = get_option( 'download_monitor_wisdom_block_notice' );
			if ( isset( $block_notice[ $plugin ] ) ) {
				return;
			}

			// The wisdom_allow_tracking option is an array of plugins that are being tracked.
			$allow_tracking = get_option( $this->plugin_name . '_wisdom_tracking' );
			$track          = false;
			// If the user has decided to opt out.
			if ( $this->has_user_opted_out() ) {
				if ( $this->what_am_i == 'theme' ) {
					set_theme_mod( 'download_monitor-wisdom-allow-tracking', 0 );
				} else {
					if ( isset( $allow_tracking ) && '1' == $allow_tracking ) {
						$track = false;
					}
				}

			} else if ( $is_allowed || ! $this->require_optin ) {
				// If the user has agreed to allow tracking or if opt-in is not required

				if ( $this->what_am_i == 'theme' ) {
					set_theme_mod( 'download_monitor-wisdom-allow-tracking', 1 );
				} else {
					if ( ! isset( $allow_tracking ) || '1' != $allow_tracking ) {
						$track = true;
					}
				}

			} else {
				if ( $this->what_am_i == 'theme' ) {
					set_theme_mod( 'download_monitor-wisdom-allow-tracking', 0 );
				} else {
					if ( isset( $allow_tracking ) && '1' === $allow_tracking ) {
						$track = false;
					}
				}

			}

			if ( $track ) {
				update_option( $this->plugin_name . '_wisdom_tracking', '1' );
			} else {
				delete_option( $this->plugin_name . '_wisdom_tracking' );
			}

			//update_option( 'download_monitor_troubleshooting_option', $allow_tracking );
		}

		/**
		 * Has the user opted out of allowing tracking?
		 * Note that themes are opt in / plugins are opt out
		 *
		 * @since 1.1.0
		 * @return Boolean
		 */
		public function has_user_opted_out() {
			// Different opt-out methods for plugins and themes
			if( $this->what_am_i == 'theme' ) {
				// Look for the theme mod
				$mod = get_theme_mod( 'modula-wisdom-allow-tracking', 0 );
				if( false === $mod ) {
					// If the theme mod is not set, then return true - the user has opted out
					return true;
				}
			} else {
				// Iterate through the options that are being tracked looking for wisdom_opt_out setting
				if( ! empty( $this->options ) ) {
					foreach( $this->options as $option_name ) {
						// Check each option
						$options = get_option( $option_name );
						// If we find the setting, return true
						if( ! empty( $options['wisdom_opt_out'] ) ) {
							return true;
						}
					}
				}
			}
			return false;
		}

		/**
		 * Record the time we send tracking data
		 *
		 * @since 1.1.1
		 */
		public function set_track_time() {
			// We've tracked, so record the time.
			$track_times = get_option( 'download_monitor_wisdom_last_track_time', array() );
			// Set different times according to plugin, in case we are tracking multiple plugins.
			$track_times[ $this->plugin_name ] = time();
			update_option( 'download_monitor_wisdom_last_track_time', $track_times );
		}

		/**
		 * Set the time when we can display the opt-in notification
		 * Will display now unless filtered
		 *
		 * @since 1.2.4
		 */
		public function set_notification_time() {
			$notification_times = get_option( 'download_monitor_wisdom_notification_times', array() );
			// Set different times according to plugin, in case we are tracking multiple plugins.
			if ( ! isset( $notification_times[ $this->plugin_name ] ) ) {
				$delay_notification = apply_filters( 'wisdom_delay_notification_' . $this->plugin_name, 0 );
				// We can delay the notification time.
				$notification_time                        = time() + absint( $delay_notification );
				$notification_times[ $this->plugin_name ] = $notification_time;
				update_option( 'download_monitor_wisdom_notification_times', $notification_times );
			}
		}

		/**
		 * Get whether it's time to display the notification
		 *
		 * @since 1.2.4
		 * @return Boolean
		 */
		public function get_is_notification_time() {
			$notification_times = get_option( 'download_monitor_wisdom_notification_times', array() );
			$time               = time();
			// Set different times according to plugin, in case we are tracking multiple plugins.
			if ( isset( $notification_times[ $this->plugin_name ] ) ) {
				$notification_time = $notification_times[ $this->plugin_name ];
				if ( $time >= $notification_time ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Set if we should block the opt-in notice for this plugin
		 * Option is an array of all plugins that have received a response from the user
		 *
		 * @since 1.0.0
		 */
		public function update_block_notice( $plugin = null ) {
			if ( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}
			$block_notice = get_option( 'download_monitor_wisdom_block_notice' );
			if ( empty( $block_notice ) || ! is_array( $block_notice ) ) {
				// If nothing exists in the option yet, start a new array with the plugin name.
				$block_notice = array( $plugin => $plugin );
			} else {
				// Else add the plugin name to the array.
				$block_notice[ $plugin ] = $plugin;
			}
			update_option( 'download_monitor_wisdom_block_notice', $block_notice );
		}

		/**
		 * Can we collect the email address?
		 *
		 * @since 1.0.0
		 */
		public function get_can_collect_email() {
			// The wisdom_collect_email option is an array of plugins that are being tracked.
			$collect_email = get_option( 'download_monitor_wisdom_collect_email' );
			// If this plugin is in the array, then we can collect the email address.
			if ( isset( $collect_email[ $this->plugin_name ] ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Set if user has allowed us to collect their email address
		 * Option is an array of all plugins with email collection permitted
		 * More than one plugin may be using the tracker
		 *
		 * @since 1.0.0
		 * @param $can_collect  Boolean     true if collection is allowed, false if not
		 */
		public function set_can_collect_email( $can_collect, $plugin = null ) {
			if ( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}
			// The wisdom_collect_email option is an array of plugins that are being tracked.
			$collect_email = get_option( 'download_monitor_wisdom_collect_email' );
			// If the user has agreed to allow tracking or if opt-in is not required.
			if ( $can_collect ) {
				if ( empty( $collect_email ) || ! is_array( $collect_email ) ) {
					// If nothing exists in the option yet, start a new array with the plugin name.
					$collect_email = array( $plugin => $plugin );
				} else {
					// Else add the plugin name to the array.
					$collect_email[ $plugin ] = $plugin;
				}
			} else {
				if ( isset( $collect_email[ $plugin ] ) ) {
					unset( $collect_email[ $plugin ] );
				}
			}
			update_option( 'download_monitor_wisdom_collect_email', $collect_email );
		}

		/**
		 * Get the correct email address to use
		 *
		 * @since 1.1.2
		 * @return Email address
		 */
		public function get_admin_email() {
			// The wisdom_collect_email option is an array of plugins that are being tracked.
			$email = get_option( 'download_monitor_wisdom_admin_emails' );
			// If this plugin is in the array, then we can collect the email address.
			if ( isset( $email[ $this->plugin_name ] ) ) {
				return $email[ $this->plugin_name ];
			}
			return false;
		}

		/**
		 * Set the correct email address to use
		 * There might be more than one admin on the site
		 * So we only use the first admin's email address
		 *
		 * @param $email    Email address to set
		 * @param $plugin   Plugin name to set email address for
		 * @since 1.1.2
		 */
		public function set_admin_email( $email = null, $plugin = null ) {
			if ( empty( $plugin ) ) {
				$plugin = $this->plugin_name;
			}
			// If no email address passed, try to get the current user's email.
			if ( empty( $email ) ) {
				// Have to check that current user object is available.
				if ( function_exists( 'wp_get_current_user' ) ) {
					$current_user = wp_get_current_user();
					$email        = $current_user->user_email;
				}
			}
			// The wisdom_admin_emails option is an array of admin email addresses.
			$admin_emails = get_option( 'wisdom_admin_emails' );
			if ( empty( $admin_emails ) || ! is_array( $admin_emails ) ) {
				// If nothing exists in the option yet, start a new array with the plugin name.
				$admin_emails = array( $plugin => sanitize_email( $email ) );
			} elseif ( empty( $admin_emails[ $plugin ] ) ) {
				// Else add the email address to the array, if not already set.
				$admin_emails[ $plugin ] = sanitize_email( $email );
			}
			update_option( 'download_monitor_wisdom_admin_emails', $admin_emails );
		}

		/**
		 * Display the admin notice to users to allow them to opt in
		 *
		 * @since 1.0.0
		 */
		public function optin_notice() {

			if ( function_exists( 'get_current_screen' ) ) {
				$current_screen = get_current_screen();
				if ( 'dlm_download_page_download-monitor-about-page' === $current_screen->base ) {
					return;
				}
			}

			// Check for plugin args.
			if ( isset( $_GET['plugin'] ) && $this->plugin_name === $_GET['plugin'] && isset( $_GET['plugin_action'] ) ) {

				$action = sanitize_text_field( wp_unslash($_GET['plugin_action']) );
				if ( $action === 'yes' ) {
					$this->set_is_tracking_allowed( true, $this->plugin_name );
					// Run this straightaway.
					add_action( 'admin_init', array( $this, 'do_tracking' ) );
				} else {
					$this->set_is_tracking_allowed( false, $this->plugin_name );
				}
				$this->update_block_notice( $this->plugin_name );
			}

			// Is it time to display the notification?.
			$is_time = $this->get_is_notification_time();

			if ( ! $is_time ) {
				return false;
			}

			// Check whether to block the notice, e.g. because we're in a local environment.
			// wisdom_block_notice works the same as wisdom_allow_tracking, an array of plugin names.
			$block_notice = get_option( 'download_monitor_wisdom_block_notice' );

			if ( isset( $block_notice[ $this->plugin_name ] ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// @credit EDD.
			// Don't bother asking user to opt in if they're in local dev.
			$is_local = false;
			if ( stristr( network_site_url( '/' ), '.dev' ) !== false || stristr( network_site_url( '/' ), 'localhost' ) !== false || stristr( network_site_url( '/' ), ':8888' ) !== false ) {
				$is_local = true;
			}
			$is_local = apply_filters( 'wisdom_is_local_' . $this->plugin_name, $is_local );
			if ( $is_local ) {
				$this->update_block_notice();
			} else {

				// Display the notice requesting permission to track.
				// Retrieve current plugin information.
				$plugin      = $this->plugin_data();
				$plugin_name = $plugin['Name'];

				// Args to add to query if user opts in to tracking.
				$yes_args = array(
						'plugin'        => $this->plugin_name,
						'plugin_action' => 'yes',
				);

				// Decide how to request permission to collect email addresses.
				if ( $this->marketing == 1 ) {
					// Option 1 combines permissions to track and collect email.
					$yes_args['marketing_optin'] = 'no';
				} elseif ( $this->marketing == 2 ) {
					// Option 2 enables a second notice that fires after the user opts in to tracking.
					$yes_args['marketing'] = 'no';
				}
				$url_yes = add_query_arg( $yes_args );
				$url_no  = add_query_arg(
						array(
								'plugin'        => $this->plugin_name,
								'plugin_action' => 'no',
						)
				);

				// Decide on notice text.
				if ( $this->marketing != 1 ) {
					// Standard notice text.
					$notice_text = sprintf(
							__( 'Thank you for installing our %1$s. We would like to track its usage on your site. We don\'t record any sensitive data, only information regarding the WordPress environment and %1$s settings, which we will use to help us make improvements to the %1$s. Tracking is completely optional. You can always opt out by going to Settings-> Advanced-> Tracking and uncheck the track data field.', 'download-monitor' ),
							$this->what_am_i
					);
				} else {
					// If we have option 1 for marketing, we include reference to sending product information here.
					$notice_text = sprintf(
							__( 'Thank you for installing our %1$s. We\'d like your permission to track its usage on your site and subscribe you to our newsletter. We won\'t record any sensitive data, only information regarding the WordPress environment and %1$s settings, which we will use to help us make improvements to the %1$s. Tracking is completely optional.You can always opt out by going to Settings-> Advanced-> Tracking and uncheck the track data field.', 'download-monitor' ),
							$this->what_am_i
					);
				}
				// And we allow you to filter the text anyway.
				$notice_text = apply_filters( 'wisdom_notice_text_' . esc_attr( $this->plugin_name ), $notice_text ); ?>

				<div class="notice notice-info updated put-dismiss-notice">
					<p><?php echo '<strong>' . esc_html( $plugin_name ) . '</strong>'; ?></p>
					<p><?php echo esc_html( $notice_text ); ?></p>
					<p>
						<a href="<?php echo esc_url( $url_yes ); ?>" class="button-secondary"><?php echo esc_html__( 'Allow', 'download-monitor' ); ?></a>
						<a href="<?php echo esc_url( $url_no ); ?>" class="button-secondary"><?php echo esc_html__( 'Do Not Allow', 'download-monitor' ); ?></a>
					</p>
				</div>
				<?php
			}

		}

		/**
		 * Display the marketing notice to users if enabled
		 * Only displays after the user has opted in to tracking
		 *
		 * @since 1.0.0
		 */
		public function marketing_notice() {
			// Check if user has opted in to marketing.
			if ( isset( $_GET['marketing_optin'] ) ) {
				// Set marketing optin.
				$this->set_can_collect_email( sanitize_text_field( wp_unslash($_GET['marketing_optin']) ), $this->plugin_name );
				// Do tracking.
				$this->do_tracking();
			} elseif ( isset( $_GET['marketing'] ) && $_GET['marketing'] == 'yes' ) {
				// Display the notice requesting permission to collect email address.
				// Retrieve current plugin information.
				$plugin      = $this->plugin_data();
				$plugin_name = $plugin['Name'];

				$url_yes = add_query_arg(
						array(
								'plugin'          => $this->plugin_name,
								'marketing_optin' => 'yes',
						)
				);
				$url_no  = add_query_arg(
						array(
								'plugin'          => $this->plugin_name,
								'marketing_optin' => 'no',
						)
				);

				$marketing_text = sprintf(
						__( 'Thank you for opting in to tracking. Would you like to receive occasional news about this %s, including details of new features and special offers?', 'download-monitor' ),
						$this->what_am_i
				);
				$marketing_text = apply_filters( 'wisdom_marketing_text_' . esc_attr( $this->plugin_name ), $marketing_text );
				?>

				<div class="notice notice-info updated put-dismiss-notice">
					<p><?php echo '<strong>' . esc_html( $plugin_name ) . '</strong>'; ?></p>
					<p><?php echo esc_html( $marketing_text ); ?></p>
					<p>
						<a href="<?php echo esc_url( $url_yes ); ?>" data-putnotice="yes" class="button-secondary"><?php echo esc_html__( 'Yes Please', 'download-monitor' ); ?></a>
						<a href="<?php echo esc_url( $url_no ); ?>" data-putnotice="no" class="button-secondary"><?php echo esc_html__( 'No Thank You', 'download-monitor' ); ?></a>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Filter the deactivation link to allow us to present a form when the user deactivates the plugin
		 *
		 * @since 1.0.0
		 */
		public function filter_action_links( $links ) {

			// Check to see if the user has opted in to tracking.
			if ( ! $this->get_is_tracking_allowed() ) {
				return $links;
			}

			if ( isset( $links['deactivate'] ) && $this->include_goodbye_form ) {
				$deactivation_link = $links['deactivate'];
				// Insert an onClick action to allow form before deactivating.
				$deactivation_link   = str_replace( '<a ', '<div class="' . esc_attr( $this->plugin_name ) . '-put-goodbye-form-wrapper"><span class="' . esc_attr( $this->plugin_name ) . '-put-goodbye-form" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-form"></span></div><a onclick="javascript:event.preventDefault();" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-link" ', $deactivation_link );
				$links['deactivate'] = $deactivation_link;
			}

			return $links;
		}

		/*
		 * Form text strings
		 * These are non-filterable and used as fallback in case filtered strings aren't set correctly
		 * @since 1.0.0
		 */
		public function form_default_text() {
			$form            = array();
			$form['heading'] = __( 'Sorry to see you go', 'download-monitor' );
			$form['body']    = __( 'Before you deactivate the plugin, would you quickly give us your reason for doing so?', 'download-monitor' );
			$form['options'] = array(
					__( 'Set up is too difficult', 'download-monitor' ),
					__( 'Lack of documentation', 'download-monitor' ),
					__( 'Not the features I wanted', 'download-monitor' ),
					__( 'Found a better plugin', 'download-monitor' ),
					__( 'Installed by mistake', 'download-monitor' ),
					__( 'Only required temporarily', 'download-monitor' ),
					__( 'Didn\'t work', 'download-monitor' ),
			);
			$form['details'] = __( 'Details (optional)', 'download-monitor' );
			return $form;
		}

		/**
		 * Form text strings
		 * These can be filtered
		 * The filter hook must be unique to the plugin
		 *
		 * @since 1.0.0
		 */
		public function form_filterable_text() {
			$form = $this->form_default_text();
			return apply_filters( 'wisdom_form_text_' . esc_attr( $this->plugin_name ), $form );
		}

		/**
		 * Form text strings
		 * These can be filtered
		 *
		 * @since 1.0.0
		 */
		public function goodbye_ajax() {
			global $wp_version;
			// Get our strings for the form.
			$form = $this->form_filterable_text();

			if ( ! isset( $form['heading'] ) || ! isset( $form['body'] ) || ! isset( $form['options'] ) || ! is_array( $form['options'] ) || ! isset( $form['details'] ) ) {
				// If the form hasn't been filtered correctly, we revert to the default form.
				$form = $this->form_default_text();
			}
			// Build the HTML to go in the form.
			$html  = '<div class="' . esc_attr( $this->plugin_name ) . '-put-goodbye-form-head"><strong>' . esc_html( $form['heading'] ) . '</strong></div>';
			$html .= '<div class="' . esc_attr( $this->plugin_name ) . '-put-goodbye-form-body"><p>' . esc_html( $form['body'] ) . '</p>';

			if ( is_array( $form['options'] ) ) {

				$html .= '<div class="' . esc_attr( $this->plugin_name ) . '-put-goodbye-options">';
				foreach ( $form['options'] as $option ) {
					$html .= '<p><input type="checkbox" name="' . esc_attr( $this->plugin_name ) . '-put-goodbye-options[]" id="' . str_replace( ' ', '', esc_attr( $option ) ) . '" value="' . esc_attr( $option ) . '"> <label for="' . str_replace( ' ', '', esc_attr( $option ) ) . '">' . esc_html( $option ) . '</label></p>';
				}
				$html .= '<label for="' . esc_attr( $this->plugin_name ) . '-put-goodbye-reasons">' . esc_html( $form['details'] ) . '</label><textarea name="' . esc_attr( $this->plugin_name ) . '-put-goodbye-reasons" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-reasons" rows="2" style="width:100%"></textarea>';

				$html .= '<hr>';
				if ( function_exists( 'wp_get_current_user' ) ) {
					$current_user = wp_get_current_user();
					$email        = $current_user->user_email;
				}
				$html .= '<p><input type="checkbox" name="' . esc_attr( $this->plugin_name ) . '-put-goodbye-contact-check" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-contact-check" value=""> <label for="' . esc_attr( $this->plugin_name ) . '-put-goodbye-contact-check">' . esc_html__( 'I would like to be contacted.', 'download-monitor' ) . '</label></p>';
				$html .= '<p><input type="email" name="' . esc_attr( $this->plugin_name ) . '-put-goodbye-contact-email" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-contact-email" value="' . $email . '" placeholder="' . esc_html__( 'Email address.', 'download-monitor' ) . '"></p>';
				$html .= '</div><!-- .put-goodbye-options -->';
			}

			$html .= '<a href="#" id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-tracking">' . esc_html__( 'What info do we collect?', 'download-monitor' ) . '</a>';
			$html .= '<div id="' . esc_attr( $this->plugin_name ) . '-put-goodbye-tracking-info"><ul><li><strong>' . esc_html__( 'Plugin Version', 'download-monitor' ) . '</strong><code>' . DLM_VERSION . '</code></li><li><strong>' . esc_html__( 'Plugin Options', 'download-monitor' ) . '</strong><code>current options</code></li><li><strong>' . esc_html__( 'Plugin CPTs', 'download-monitor' ) . '</strong><code>total number of Downloads</code></li><li><strong>' . esc_html__( 'WordPress Version', 'download-monitor' ) . '</strong><code>' . $wp_version . '</code></li><li><strong>' . esc_html__( 'PHP', 'download-monitor' ) . '</strong><code>general PHP information</code></li><li><strong>' . esc_html__( 'Installed plugins', 'download-monitor' ) . '</strong><code>Installed plugins</code></li><li><strong>' . esc_html__( 'Active theme', 'download-monitor' ) . '</strong><code>name, version and parent if set.</code></li><li><strong>' . esc_html__( 'Current Website', 'download-monitor' ) . '</strong><code>' . trailingslashit( get_site_url() ) . '</code></li><li><strong>' . esc_html__( 'Uninstall Reason', 'download-monitor' ) . '</strong><i>' . esc_html__( 'Selected reason from above.', 'download-monitor' ) . '</i></li><li><strong>' . esc_html__( 'Email address', 'download-monitor' ) . '</strong><i>' . esc_html__( 'If specifically provided by you.', 'download-monitor' ) . '</i></li></ul></div>';

			$html .= '</div><!-- .put-goodbye-form-body -->';
			$html .= '<p class="' . esc_attr( $this->plugin_name ) . '-deactivating-spinner"><span class="spinner"></span> ' . esc_html__( 'Submitting form', 'download-monitor' ) . '</p>';
			?>
			<div class="<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form-bg"></div>
			<style type="text/css">
                .<?php echo esc_attr($this->plugin_name); ?>-put-form-active .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-bg {
                    background: rgba(0, 0, 0, .5);
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-wrapper {
                    position: fixed;
                    z-index: 999;
                    display: none;
                    top: 0;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    width: 100vw;
                    height: 100vh;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-form-active .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-wrapper {
                    display: block;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form {
                    display: none;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-form-active .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form {
                    position: absolute;
                    left: 0;
                    right: 0;
                    margin: 0 auto;
                    max-width: 400px;
                    background: #fff;
                    white-space: normal;
                    top: 50%;
                    transform: translateY(-50%);
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-head {
                    background: #0073aa;
                    color: #fff;
                    padding: 8px 18px;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-body {
                    padding: 8px 18px;
                    color: #444;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-deactivating-spinner {
                    display: none;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-deactivating-spinner .spinner {
                    float: none;
                    margin: 4px 4px 0 18px;
                    vertical-align: bottom;
                    visibility: visible;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-form-footer {
                    padding: 8px 18px;
                }

                .<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-options p input, <?php echo esc_attr($this->plugin_name); ?>-put-goodbye-options label {
                    padding: 8px 0;
                }

                #<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-tracking-info:not(.active),
                #<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-contact-email:not(.active) {
                    display: none;
                }

                #<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-tracking-info ul li {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 0;
                    padding: 5px 0;
                    border-bottom: 1px solid #ccc;
                }

                #<?php echo esc_attr($this->plugin_name); ?>-put-goodbye-contact-email {
                    width: 100%;
                    margin-bottom: 8px;
                    padding: 8px 10px;
                }
			</style>
			<script>
				jQuery(document).ready(function ($) {

					var url = document.getElementById("<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-link");

					$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-link").on("click", function () {
						// We'll send the user to this deactivation link when they've completed or dismissed the form.
						$('body').toggleClass('<?php echo esc_attr( $this->plugin_name ); ?>-put-form-active');
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form").fadeIn();
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form").html('<?php echo $html; ?>' + '<div class="<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form-footer"><p><a id="<?php echo esc_attr( $this->plugin_name ); ?>-put-submit-form" class="button primary" href="#"><?php esc_html_e( 'Submit and Deactivate', 'download-monitor' ); ?></a>&nbsp;<a class="secondary button" href="' + url + '"><?php esc_html_e( 'Just Deactivate', 'download-monitor' ); ?></a></p></div>');
					});

					$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form").on("click", "#<?php echo esc_attr( $this->plugin_name ); ?>-put-submit-form", function (e) {
						// As soon as we click, the body of the form should disappear.
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form .<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form-body").fadeOut();
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form .<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form-footer").fadeOut();
						// Fade in spinner.
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form .<?php echo esc_attr( $this->plugin_name ); ?>-deactivating-spinner").fadeIn();
						e.preventDefault();

						var values = new Array();
						$.each($("input[name='<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-options[]']:checked"), function () {
							values.push($(this).val());
						});

						var email = '';
						if ($("input[name='<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-contact-check']").is(':checked')) {
							email = $('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-contact-email').val();
						}

						var details = $('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-reasons').val();

						var data = {
							'action'        : '<?php echo esc_attr( $this->plugin_name ); ?>_goodbye_form',
							'values'        : values,
							'details'       : details,
							'email'         : email,
							'security'      : "<?php echo wp_create_nonce( 'wisdom_goodbye_form' ); ?>",
							'dataType'      : "json"
						}

						$.post(
							ajaxurl,
							data,
							function (response) {
								// Redirect to original deactivation URL.
								window.location.href = url;
							}
						);
					});

					// If we click outside the form, the form will close.
					$('.<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form').on('click', function (e) {
						e.stopPropagation();
					});

					$('.<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form-wrapper').on('click', function () {
						$("#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form").fadeOut();
						$('body').removeClass('<?php echo esc_attr( $this->plugin_name ); ?>-put-form-active');
					});

					// If we click outside the form, the form will close.
					$('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form').on('click', '#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-tracking', function (e) {
						e.preventDefault();
						$('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-tracking-info').toggleClass("active");
					});

					// If we click outside the form, the form will close.
					$('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-form').on('change', '#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-contact-check', function (e) {
						e.preventDefault();
						$('#<?php echo esc_attr( $this->plugin_name ); ?>-put-goodbye-contact-email').toggleClass("active");
					});

				});
			</script>
			<?php
		}

		/**
		 * AJAX callback when the form is submitted
		 *
		 * @since 1.0.0
		 */
		public function goodbye_form_callback() {

			check_ajax_referer( 'wisdom_goodbye_form', 'security' );

			if ( isset( $_POST['values'] ) ) {
				$values = array_map( 'sanitize_text_field', wp_unslash( $_POST['values'] ) );
				$values = json_encode( $values );
				update_option( 'wisdom_deactivation_reason_' . $this->plugin_name, $values );
			}

			if ( isset( $_POST['details'] ) ) {
				$details = sanitize_text_field( wp_unslash($_POST['details']) );
				update_option( 'wisdom_deactivation_details_' . $this->plugin_name, $details );
			}

			if ( isset( $_POST['email'] ) && '' !== $_POST['email'] && is_email( $_POST['email'] ) ) {
				$this->set_can_collect_email( true );
				$this->set_admin_email( $_POST['email'] );
			}

			$this->do_tracking(); // Run this straightaway.
			echo 'success';
			wp_die();
		}

		public function get_downloads() {
			$settings_class     = new DLM_Admin_Settings();
			$args               = array(
					'numberposts' => -1,
					'post_type'   => 'dlm_download',
			);
			$downloads          = get_posts( $args );
			$total_downloads    = count( $downloads );
			$settings           = $settings_class->get_settings();
			$formatted_settings = array();
			$licenses           = array();
			$return_array       = array();

			if ( ! empty( $this->active_plugins ) ) {
				foreach ( $this->active_plugins as $plugin => $value ) {
					if ( 0 === strpos( $value, 'dlm' ) ) {
						$new_val                 = explode( '/', $value );
						$licenses[ $new_val[0] ] = get_option( $new_val[0] . '-license' );
						unset( $licenses[ $new_val[0] ][1] );
					}
				}
			}

			$site_url = base64_encode( site_url() );

			foreach ( $settings as $tab_key => $tab ) {
				foreach ( $tab['sections'] as $section_key => $section ) {
					if ( isset( $section['fields'] ) ) {
						foreach ( $section['fields'] as $field ) {
							if ( isset( $field['name'] ) ) {
								$formatted_settings[ $field['name'] ] = get_option( $field['name'] );
							}
						}
					}
				}
			}

			$return_array['settings']        = $formatted_settings;
			$return_array['licenses']        = $licenses;
			$return_array['site_url']        = $site_url;
			$return_array['total_downloads'] = $total_downloads;

			return $return_array;

		}

		/**
		 * Add tracking options to uninstall process
		 *
		 * @param $options
		 *
		 * @return mixed
		 */
		public function uninstall_options( $options ) {

			$options[] = 'download_monitor_wisdom_last_track_time';
			//$options[] = 'download_monitor_troubleshooting_option';
			$options[] = 'download_monitor_wisdom_notification_times';
			$options[] = 'download_monitor_wisdom_block_notice';
			$options[] = 'download_monitor_wisdom_collect_email';
			$options[] = 'download_monitor_wisdom_admin_emails';
			$options[] = 'wisdom_deactivation_reason_' . $this->plugin_name;
			$options[] = 'wisdom_deactivation_details_' . $this->plugin_name;
			$options[] = $this->plugin_name . '_wisdom_tracking';

			return $options;

		}

		/**
		 * Add the optin setting
		 *
		 * @param $settings
		 *
		 * @return array
		 */
		public function optin_tracking( $settings ) {

			if ( isset( $settings['advanced'] ) ) {

				$settings['advanced']['sections']['tracking'] = array(
						'title'  => __( 'Tracking', 'download-monitor' ),
						'fields' => array(
								array(
										'name'     => $this->plugin_name.'_wisdom_tracking',
										'std'      => '0',
										'label'    => __( 'Track data', 'download-monitor' ),
										'cb_label' => '',
										'desc' => __( 'We would like to track its usage on your site. We don\'t record any sensitive data, only information regarding the WordPress environment and Download Monitor settings, which we will use to help us make improvements.', 'download-monitor' ),
										'type'     => 'checkbox'
								),
						)
				);
			}

			return $settings;

		}

		/**
		 * Default tracking
		 *
		 * @param $defaults
		 *
		 * @return mixed
		 */
		public function default_tracking( $defaults ) {

			$defaults[ $this->plugin_name . '_wisdom_tracking' ] = '1';

			return $defaults;

		}

	}

}
