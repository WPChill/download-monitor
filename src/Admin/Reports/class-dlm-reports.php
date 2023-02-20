<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports' ) ) {

	/**
	 * DLM_Reports
	 *
	 * @since 4.6.0
	 */
	class DLM_Reports {

		/**
		 * Holds the class object.
		 *
		 * @since 4.6.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * @var array
		 */
		private $reports_headers = array();

		/**
		 * PHP info used to set the limit for SQL queries.
		 *
		 * @var array
		 */
		public  $php_info = array();

		/**
		 * The time format
		 *
		 * @var string
		 */
		public $date_format = 'Y-m-d H:i:s';

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 4.6.0
		 */
		public function __construct() {

			$this->date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

			$memory_limit = ini_get( 'memory_limit' );
			if ( preg_match( '/^(\d+)(.)$/', $memory_limit, $matches ) ) {
				if ( 'M' === $matches[2] ) {
					$memory_limit = $matches[1];
				} else if ( 'K' === $matches[2] ) {
					$memory_limit = $matches[1] / 1024;
				} else if ( 'G' === $matches[2] ) {
					$memory_limit = $matches[1] * 1024;
				}
			}

			$this->php_info = array(
				'memory_limit'       => absint( $memory_limit ),
				'max_execution_time' => ini_get( 'max_execution_time' ),
				'retrieved_rows'     => 10000
			);

			if ( 40 < $this->php_info['memory_limit'] ) {
				if ( 80 <= $this->php_info['memory_limit'] ) {
					$this->php_info['retrieved_rows'] = 30000;
				}

				if ( 120 <= $this->php_info['memory_limit'] ) {
					$this->php_info['retrieved_rows'] = 40000;
				}
				if ( 150 <= $this->php_info['memory_limit'] ) {
					$this->php_info['retrieved_rows'] = 60000;
				}

				if ( 200 <= $this->php_info['memory_limit'] ) {
					$this->php_info['retrieved_rows'] = 100000;
				}

				if ( 500 <= $this->php_info['memory_limit'] ) {
					$this->php_info['retrieved_rows'] = 150000;
				}
			}

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'create_global_variable' ) );
			add_action( 'wp_ajax_dlm_update_report_setting', array( $this, 'save_reports_settings' ) );
			add_action( 'wp_ajax_dlm_top_downloads_reports', array( $this, 'get_ajax_top_downloads_markup' ) );
			add_action( 'init', array( $this, 'set_table_headers' ), 30 );

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The DLM_Reports object.
		 *
		 * @since 4.6.0
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Reports ) ) {
				self::$instance = new DLM_Reports();
			}

			return self::$instance;

		}

		/**
		 * Set table headers
		 *
		 * @return void
		 */
		public function set_table_headers() {
			$this->reports_headers = apply_filters(
				'dlm_reports_templates',
				array(
					'top_downloads' => array(
						'table_headers' => array(
							'id'              => array(
								'title' => __( 'ID', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'title'           => array(
								'title' => __( 'Title', 'download-monitor' ),
								'sort'  => true,
								'class' => '',
							),
							'total_downloads' => array(
								'title' => __( 'Total', 'download-monitor' ),
								'sort'  => true,
								'class' => '',
							),
						)
					),
					'user_logs' => array(
						'table_headers' => array(
							'user'          => array(
								'title' => esc_html__( 'User', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'ip'            => array(
								'title' => esc_html__( 'IP', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'role'          => array(
								'title' => esc_html__( 'Role', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'download'      => array(
								'title' => esc_html__( 'Download', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'status'        => array(
								'title' => esc_html__( 'Status', 'download-monitor' ),
								'sort'  => false,
								'class' => '',
							),
							'download_date' => array(
								'title' => esc_html__( 'Download date', 'download-monitor' ),
								'sort'  => true,
								'class' => '',
							)
						)
					),
				)
			);
		}

		/**
		 * Set our global variable dlmReportsStats so we can manipulate given data
		 *
		 * @since 4.6.0
		 */
		public function create_global_variable() {
			$current_user_can = '&user_can_view_reports=' . apply_filters( 'dlm_user_can_view_reports', current_user_can( 'dlm_view_reports' ) );

			$rest_route_download_reports = rest_url() . 'download-monitor/v1/download_reports?_wpnonce=' . wp_create_nonce( 'wp_rest' ) . $current_user_can;
			$rest_route_user_reports     = rest_url() . 'download-monitor/v1/user_reports?_wpnonce=' . wp_create_nonce( 'wp_rest' ) . $current_user_can;
			$rest_route_user_data        = rest_url() . 'download-monitor/v1/user_data?_wpnonce=' . wp_create_nonce( 'wp_rest' ) . $current_user_can;
			$rest_route_templates        = rest_url() . 'download-monitor/v1/templates?_wpnonce=' . wp_create_nonce( 'wp_rest' ) . $current_user_can;

			$cpt_fields = apply_filters( 'dlm_reports_downloads_cpt', array(
				'author',
				'id',
				'title',
				'slug'
			) );
			$rest_rout_downloadscpt = rest_url() . 'wp/v2/dlm_download?_fields=' . implode( ',', $cpt_fields ) . '&_wpnonce=' . wp_create_nonce( 'wp_rest' ) . $current_user_can;
			// Let's add the global variable that will hold our reporst class and the routes.
			wp_add_inline_script( 'dlm_reports', 'let dlmReportsInstance = {}; dlm_admin_url = "' . admin_url() . '" ; const dlmDownloadReportsAPI ="' . $rest_route_download_reports . '"; const dlmUserReportsAPI ="' . $rest_route_user_reports . '"; const dlmUserDataAPI ="' . $rest_route_user_data . '"; const dlmTemplates = "' . $rest_route_templates . '"; const dlmDownloadsCptApiapi = "' . $rest_rout_downloadscpt . '"; const dlmPHPinfo =  ' . wp_json_encode( $this->php_info ) . ';', 'before' );
		}

		/**
		 * Register DLM Logs Routes
		 *
		 * @since 4.6.0
		 */
		public function register_routes() {

			// The REST route for downloads reports.
			register_rest_route(
				'download-monitor/v1',
				'/download_reports',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_stats' ),
					'permission_callback' => array( $this, 'check_api_rights' ),
				)
			);

			// The REST route for user reports.
			register_rest_route(
				'download-monitor/v1',
				'/user_reports',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'user_reports_stats' ),
					'permission_callback' => array( $this, 'check_api_rights' ),
				)
			);

			// The REST route for users data.
			register_rest_route(
				'download-monitor/v1',
				'/user_data',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'user_data_stats' ),
					'permission_callback' => array( $this, 'check_api_rights' ),
				)
			);
		}

		/**
		 * Get our stats for the chart
		 *
		 * @return WP_REST_Response
		 * @since 4.6.0
		 */
		public function rest_stats() {

			return $this->respond( $this->report_stats() );
		}

		/**
		 * Get our stats for the user reports
		 *
		 * @return WP_REST_Response
		 * @since 4.6.0
		 */
		public function user_reports_stats() {

			return $this->respond( $this->get_user_reports() );
		}


		/**
		 * Get our user data
		 *
		 * @return WP_REST_Response
		 * @since 4.6.0
		 */
		public function user_data_stats() {

			return $this->respond( $this->get_user_data() );
		}

		/**
		 * Send our data
		 *
		 * @param $data JSON data received from report_stats.
		 *
		 * @return WP_REST_Response
		 * @since 4.6.0
		 */
		public function respond( $data ) {

			$result = new \WP_REST_Response( $data, 200 );

			$result->set_headers(
				array(
					// @todo : comment this and if people complain about the performance, we can add it back.
					//'Cache-Control' => 'max-age=3600, s-max-age=3600',
					'Content-Type'  => 'application/json',
				)
			);

			return $result;
		}

		/**
		 * Return stats
		 *
		 * @retun array
		 * @since 4.6.0
		 */
		public function report_stats() {

			global $wpdb;

			check_ajax_referer( 'wp_rest' );

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return array(
					'stats'  => array(),
					'offset' => 0,
					'done'   => true,
				);
			}

			$offset       = isset( $_REQUEST['offset'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['offset'] ) ) ) : 0;
			$count        = isset( $_REQUEST['limit'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['limit'] ) ) : 1000;
			$offset_limit = $offset * $count;
			$stats        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->dlm_reports} LIMIT {$offset_limit}, {$count};", null ), ARRAY_A );

			return array(
				'stats'  => $stats,
				'offset' => ( absint( $count ) === count( $stats ) ) ? $offset + 1 : '',
				'done'   => absint( $count ) > count( $stats ),
			);
		}

		/**
		 * Return user reports stats
		 *
		 * @retun array
		 * @since 4.6.0
		 */
		public function get_user_reports() {

			global $wpdb;

			check_ajax_referer( 'wp_rest' );

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return array(
					'logs'   => array(),
					'offset' => 1,
					'done'   => true,
				);
			}

			$offset       = isset( $_REQUEST['offset'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['offset'] ) ) ) : 0;
			$count        = isset( $_REQUEST['limit'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['limit'] ) ) : $this->php_info['retrieved_rows'];
			$offset_limit = $offset * $count;

			$table_columns = apply_filters(
				'dlm_download_log_columns',
				array(
					'user_id',
					'user_ip',
					'download_id',
					'download_date',
					'download_status'
				)
			);
			$table_columns = sanitize_text_field( implode( ',', wp_unslash( $table_columns ) ) );
			$downloads     = $wpdb->get_results( $wpdb->prepare( 'SELECT ' . $table_columns . ' FROM ' . $wpdb->download_log . " ORDER BY ID desc LIMIT {$offset_limit}, {$count};" ), ARRAY_A );

			$downloads = array_map( array( $this, 'date_creator' ), $downloads );

			return array(
				'logs'   => $downloads,
				'offset' => ( absint( $count ) === count( $downloads ) ) ? $offset + 1 : '',
				'done'   => absint( $count ) > count( $downloads ),
			);

		}

		/**
		 * Create WordPress generated date
		 *
		 * @param $element
		 *
		 * @return mixed
		 * @since 4.7.4
		 */
		public function date_creator( $element ) {
			// Set UTC timezone bacause in the DB it is stored based on the timezone in the settings.
			$element['display_date'] = wp_date( $this->date_format, strtotime( $element['download_date'] ), new DateTimeZone('UTC') );

			return $element;
		}

		/**
		 * Return user data
		 *
		 * @retun array
		 * @since 4.6.0
		 */
		public function get_user_data() {

			global $wpdb;

			check_ajax_referer( 'wp_rest' );

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return array();
			}

			$users_data = array();

			$offset       = isset( $_REQUEST['offset'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['offset'] ) ) ) : 0;
			$count        = isset( $_REQUEST['limit'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['limit'] ) ) : 5000;
			$offset_limit = $offset * $count;

			$args = array(
				'number' => $count,
				'offset' => $offset_limit
			);
			$users      = get_users( $args );
			foreach ( $users as $user ) {
				$user_data    = $user->data;
				$users_data[] = array(
					'id'           => $user_data->ID,
					'nicename'     => $user_data->user_nicename,
					'url'          => $user_data->user_url,
					'registered'   => $user_data->user_registered,
					'display_name' => $user_data->display_name,
					'email'        => $user_data->user_email,
					'role'         => ( ( ! in_array( 'administrator', $user->roles, true ) ) ? $user->roles : '' ),
				);
			}

			return array(
				'logs'   => $users_data,
				'offset' => ( absint( $count ) === count( $users ) ) ? $offset + 1 : '',
				'done'   => absint( $count ) > count( $users ),
			);
		}

		/**
		 * Save reports settings
		 *
		 * @return void
		 * @since 4.6.0
		 */
		public function save_reports_settings() {

			if ( ! isset( $_POST['_ajax_nonce'] ) ) {
				wp_send_json_error( 'No nonce' );
			}

			check_ajax_referer( 'dlm_reports_nonce' );
			$option = ( isset( $_POST['name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

			if ( isset( $_POST['checked'] ) && 'true' === $_POST['checked'] ) {
				$value = 'on';
			} else {
				$value = 'off';
			}

			if ( isset( $_POST['value'] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST['value'] ) );
			}

			update_option( $option, $value );
			die();
		}

		/**
		 * Get top downloads HTML markup
		 *
		 * @return string
		 * @since 4.6.0
		 */
		public function get_top_downloads_markup( $offset = 0, $limit = 15 ) {
			global $wpdb;

			$downloads = $wpdb->get_results( 'SELECT COUNT(ID) as downloads, download_id, download_status FROM ' . $wpdb->download_log . ' GROUP BY download_id ORDER BY downloads desc LIMIT  ' . absint( $offset ) . ' , ' . absint( $limit ) . ';', ARRAY_A );

			ob_start();
			$dlm_top_downloads = $this->reports_headers;
			include __DIR__ . '/components/php-components/top-downloads-table.php';
			return ob_get_clean();
		}

		/**
		 * Get top downloads HTML markup
		 *
		 * @return string
		 * @since 4.6.0
		 */
		public function header_top_downloads_markup() {

			ob_start();
			$dlm_top_downloads = $this->reports_headers;
			include __DIR__ . '/components/php-components/top-downloads-header.php';
			return ob_get_clean();
		}

		/**
		 * Get top downloads HTML markup
		 *
		 * @return string
		 * @since 4.6.0
		 */
		public function footer_top_downloads_markup() {

			ob_start();
			$dlm_top_downloads = $this->reports_headers;
			include __DIR__ . '/components/php-components/top-downloads-footer.php';
			return ob_get_clean();
		}

		/**
		 * Get top downloads HTML markup
		 *
		 * @return string
		 * @since 4.6.0
		 */
		public function header_user_logs_markup() {

			ob_start();
			$dlm_top_downloads = $this->reports_headers;
			include __DIR__ . '/components/php-components/user-logs-header.php';
			return ob_get_clean();
		}

		/**
		 * Get top downloads HTML markup
		 *
		 * @return string
		 * @since 4.6.0
		 */
		public function footer_user_logs_markup() {

			ob_start();
			$dlm_top_downloads = $this->reports_headers;
			include __DIR__ . '/components/php-components/user-logs-footer.php';
			return ob_get_clean();
		}

		/**
		 * Check permissions to display data
		 *
		 * @param array $request The request.
		 *
		 * @return bool|WP_Error
		 * @since 4.7.70
		 */
		public function check_api_rights( $request ) {

			if ( ! isset( $request['user_can_view_reports'] ) || ! (bool) $request['user_can_view_reports'] ||
			     ! is_user_logged_in() || ! current_user_can( 'dlm_view_reports' ) ) {
				return new WP_Error(
					'rest_forbidden_context',
					esc_html__( 'Sorry, you are not allowed to see data from this endpoint.', 'download-monitor' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}

			return true;
		}
	}
}
