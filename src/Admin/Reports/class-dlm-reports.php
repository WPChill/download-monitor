<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DLM_Reports' ) ) {

	/**
	 * DLM_Reports
	 *
	 * @since 5.1.0
	 */
	class DLM_Reports {

		/**
		 * DLM_Reports constructor.
		 *
		 * @since 5.1.0
		 */
		public function __construct() {
			add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );
			add_action( 'admin_enqueue_scripts', array( $this, 'reports_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'reports_widget_scripts' ) );
			add_filter( 'dlm_header_logo_text', array( $this, 'add_page_title' ) );

			add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		}

		/**
		 * Add settings menu item
		 *
		 * @param mixed $links The links for the menu.
		 *
		 * @return array
		 */
		public function add_admin_menu( $links ) {
			// If Reports are disabled don't add the menu item.
			if ( ! DLM_Logging::is_logging_enabled() ) {
				return $links;
			}

			// Reports page.
			$links[] = array(
				'page_title' => __( 'Reports', 'download-monitor' ),
				'menu_title' => __( 'Reports', 'download-monitor' ),
				'capability' => 'dlm_view_reports',
				'menu_slug'  => 'download-monitor-reports',
				'function'   => array( $this, 'view' ),
				'priority'   => 50,
			);

			return $links;
		}

		/**
		 * Create React root for reports page
		 * @since 5.1.0
		 * @return void
		 */
		public function view() {
			?>
				<div class="dlm-reports-page-body">
					<?php do_action( 'dlm_reports_page_start' ); ?>
						<div id="dlm_reports_page"></div>
					<?php do_action( 'dlm_reports_page_end' ); ?>
				</div>
			<?php
		}

		/**
		 * Get our stats for the bar chart
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_graph_downloads_data( $request ) {
			global $wpdb;

			$data = array();

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return $data;
			}

			$start_date = null;
			$end_date   = null;

			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$downloads_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->dlm_reports} WHERE `date` BETWEEN %s AND %s ORDER BY `date` ASC;",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

			$data['downloads_data'] = array();
			foreach ( $downloads_data as $download_data ) {
				$downloads = json_decode( $download_data['download_ids'], true );
				$count     = 0;
				foreach ( $downloads as $download ) {
					$count += absint( $download['downloads'] );
				}
				$data['downloads_data'][] = array(
					'date'      => $download_data['date'],
					'downloads' => $count,
				);
			}

			$data = apply_filters( 'dlm_reports_graph_downloads_data', $data, $request );

			return $data;
		}

		/**
		 * Get our stats for the downloads table
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_table_downloads_data( $request ) {
			global $wpdb;

			$data = array();

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->dlm_reports ) ) {
				return $data;
			}

			$start_date = null;
			$end_date   = null;

			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$where = $wpdb->prepare(
				'WHERE DATE(download_date) BETWEEN %s AND %s',
				$start_date,
				$end_date
			);

			// We set the columns here so we can filter and add or remove later.
			$default_select_columns = array(
				'COUNT(*) as total',
				'SUM(download_status = "completed") as completed',
				'SUM(download_status = "redirected") as redirected',
				'SUM(download_status = "failed") as failed',
				'SUM(user_id != 0) as logged_in_downloads',
				'SUM(user_id = 0) as logged_out_downloads',
			);

			$select_columns = apply_filters( 'dlm_table_downloads_select_columns', $default_select_columns );
			$select_sql     = implode( ",\n\t", $select_columns );

			$query = "
				SELECT
				download_id,
					$select_sql
				FROM {$wpdb->download_log}
				$where
				GROUP BY download_id
			";

			$data = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$stats = array();

			// Add the title.
			foreach ( $data as $key => $row ) {
				$data[ $key ]['title'] = get_the_title( $row['download_id'] );
			}

			$data = apply_filters( 'dlm_reports_table_downloads_data', $data, $request );

			return $data;
		}


		/**
		 * Get data for the overview cards.
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_overview_card_stats( $request ) {

			global $wpdb;

			$stats = array(
				'total'        => 0,
				'today'        => 0,
				'average'      => 0,
				'most_popular' => null,
			);

			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$today = current_time( 'Y-m-d' );

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `date`, `download_ids` FROM {$wpdb->dlm_reports} WHERE `date` BETWEEN %s AND %s",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

			$total_downloads = 0;
			$today_downloads = 0;
			$download_totals = array(); // key = download_id, value = ['total' => x, 'title' => y]
			$day_count       = 0;
			$has_today       = false;

			foreach ( $rows as $row ) {
				++$day_count;
				$date = $row['date'];

				if ( $date === $today ) {
					$has_today = true;
				}

				if ( empty( $row['download_ids'] ) ) {
					continue;
				}

				$downloads = json_decode( $row['download_ids'], true );
				if ( ! is_array( $downloads ) ) {
					continue;
				}

				$day_total = 0;

				foreach ( $downloads as $id => $data ) {
					$id    = (int) $id;
					$count = isset( $data['downloads'] ) ? (int) $data['downloads'] : 0;
					$title = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';

					$day_total += $count;

					if ( ! isset( $download_totals[ $id ] ) ) {
						$download_totals[ $id ] = array(
							'total' => 0,
							'title' => $title,
						);
					}

					$download_totals[ $id ]['total'] += $count;
				}

				$total_downloads += $day_total;

				if ( $date === $today ) {
					$today_downloads = $day_total;
				}
			}

			// If we do not have today in the initial period, query for it.
			if ( ! $has_today ) {
				$today_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT `download_ids` FROM {$wpdb->dlm_reports} WHERE `date` = %s",
						$today
					),
					ARRAY_A
				);

				if ( ! empty( $today_row['download_ids'] ) ) {
					$downloads = json_decode( $today_row['download_ids'], true );
					if ( is_array( $downloads ) ) {
						$day_total = 0;
						foreach ( $downloads as $id => $data ) {
							$count      = isset( $data['downloads'] ) ? (int) $data['downloads'] : 0;
							$day_total += $count;
						}
						$today_downloads = $day_total;
					}
				}
			}

			// Most popular
			$most_popular = null;
			foreach ( $download_totals as $id => $info ) {
				if ( is_null( $most_popular ) || $info['total'] > $most_popular['total'] ) {
					$most_popular = array(
						'id'    => $id,
						'title' => $info['title'],
						'total' => $info['total'],
					);
				}
			}

			$stats['total']        = $total_downloads;
			$stats['today']        = $today_downloads;
			$stats['average']      = $day_count > 0 ? floatval( number_format( $total_downloads / $day_count, 2, '.', '' ) ) : 0;
			$stats['most_popular'] = $most_popular;

			return apply_filters( 'dlm_reports_card_stats', $stats, $request );
		}

		/**
		 * Get data for the detailed view user cards.
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_users_card_stats( $request ) {
			global $wpdb;

			$stats = array(
				'logged_in'   => 0,
				'logged_out'  => 0,
				'most_active' => null,
			);

			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, COUNT(*) as total
					FROM {$wpdb->download_log}
					WHERE DATE(download_date) BETWEEN %s AND %s
					GROUP BY user_id",
					$start_date,
					$end_date
				),
				ARRAY_A
			);

			$max_total   = 0;
			$most_active = null;
			$logged_in   = 0;
			$logged_out  = 0;

			foreach ( $results as $row ) {
				$user_id = (int) $row['user_id'];
				$total   = (int) $row['total'];

				if ( 0 === $user_id ) {
					$logged_out += $total;
				} else {
					$logged_in += $total;
					if ( $total > $max_total ) {
						$max_total   = $total;
						$most_active = array(
							'id'    => $user_id,
							'total' => $total,
						);
					}
				}
			}

			if ( $most_active ) {
				$user                = get_user_by( 'id', $most_active['id'] );
				$most_active['name'] = $user ? $user->display_name : 'User #' . $most_active['id'];
			}

			$stats['logged_in']   = $logged_in;
			$stats['logged_out']  = $logged_out;
			$stats['most_active'] = $most_active;

			return apply_filters( 'dlm_reports_user_card_stats', $stats, $request );
		}

		/**
		 * Get our stats for the downloads table
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_users_downloads_data( $request ) {
			global $wpdb;

			$data = array();

			if ( ! DLM_Logging::is_logging_enabled() || ! DLM_Utils::table_checker( $wpdb->download_log ) ) {
				return $data;
			}

			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			$where = $wpdb->prepare(
				'WHERE DATE(download_date) BETWEEN %s AND %s',
				$start_date,
				$end_date
			);

			// We set the columns here so we can filter and add or remove later.
			$default_select_columns = array(
				'user_id',
				'user_ip',
				'download_status',
				'download_date',
			);

			$select_columns = apply_filters( 'dlm_users_downloads_select_columns', $default_select_columns );
			$select_sql     = implode( ",\n\t", $select_columns );

			$query = "
				SELECT
				ID,
				download_id,
					$select_sql
				FROM {$wpdb->download_log}
				$where
				ORDER BY ID desc
			";

			$data = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$stats = array();

			// Add the title.
			foreach ( $data as $key => $row ) {
				$data[ $key ]['title'] = get_the_title( $row['download_id'] );
			}

			$data = apply_filters( 'dlm_reports_users_table_downloads_data', $data, $request );

			return $data;
		}


		/**
		 * Get all users data that have downloaded.
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public static function get_user_data( $request ) {
			global $wpdb;
			$allowed_roles = apply_filters(
				'dlm_reports_allowed_roles',
				array(
					'administrator',
					'editor',
					'author',
					'contributor',
					'subscriber',
					'customer',
					'shop_manager',
				)
			);

			$users      = array();
			$users_data = array();
			$start      = $request->get_param( 'start' );
			$end        = $request->get_param( 'end' );

			if ( isset( $start, $end ) ) {
				$start_date = sanitize_text_field( $start );
				$end_date   = sanitize_text_field( $end );
			} else {
				$end_date   = current_time( 'Y-m-d' );
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
			}

			// Retrieve only users that have downloaded something, we don't want to show users that have not downloaded anything.
			$users = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT dlm_logs.user_id as ID, wp_users.user_nicename, wp_users.user_url, wp_users.user_registered, wp_users.display_name, wp_users.user_email, wp_users_meta.meta_value as roles
					FROM {$wpdb->download_log} dlm_logs
					LEFT JOIN {$wpdb->users} wp_users ON dlm_logs.user_id = wp_users.ID AND wp_users.ID IS NOT NULL
					LEFT JOIN {$wpdb->usermeta} wp_users_meta ON dlm_logs.user_id = wp_users_meta.user_id
					WHERE dlm_logs.user_id != 0
					AND wp_users_meta.meta_key = %s
					AND DATE(dlm_logs.download_date) BETWEEN %s AND %s
					ORDER BY dlm_logs.user_id DESC",
					$wpdb->prefix . 'capabilities',
					$start_date,
					$end_date
				)
			);

			if ( ! empty( $users ) ) {
				// Cycle through users and get their data.
				foreach ( $users as $user ) {
					$user_roles              = array_keys( unserialize( $user->roles ) );
					$user_roles              = array_intersect( $user_roles, $allowed_roles );
					$user_roles              = is_array( $user_roles ) ? implode( ',', $user_roles ) : '';
					$users_data[ $user->ID ] = array(
						'nicename'     => $user->user_nicename,
						'url'          => $user->user_url,
						'registered'   => $user->user_registered,
						'display_name' => $user->display_name,
						'email'        => $user->user_email,
						'role'         => $user_roles,
					);
				}
			}

			return apply_filters( 'dlm_reports_users_data', $users_data, $request );
		}

		/**
		 * Enqueues the react script for reports page.
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public function reports_scripts() {

			if ( ! isset( $_GET['page'] ) || 'download-monitor-reports' !== $_GET['page'] ) {
				return;
			}

			$asset_file = require plugin_dir_path( DLM_PLUGIN_FILE ) . 'assets/js/reports/reports.asset.php';
			$enqueue    = array(
				'handle'       => 'dlm-reports-app',
				'dependencies' => $asset_file['dependencies'],
				'version'      => $asset_file['version'],
				'script'       => DLM_URL . 'assets/js/reports/reports.js',
				'style'        => DLM_URL . 'assets/js/reports/reports.css',
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
				array(),
				$enqueue['version']
			);
		}

		/**
		 * Register dashboard widget
		 * @since 5.1.0
		 * @return void
		 */
		public function register_dashboard_widget() {
			if ( ! current_user_can( 'manage_downloads' ) || apply_filters( 'dlm_remove_dashboard_popular_downloads', false ) ) {
				return;
			}

			wp_add_dashboard_widget(
				'dlm_popular_downloads',
				__( 'Downloads', 'download-monitor' ),
				array(
					$this,
					'dashboard_widget_root',
				)
			);
		}

		/**
		 * Create React root for dashboard page widget.
		 * @since 5.1.0
		 * @return void
		 */
		public function dashboard_widget_root() {
			?>
				<div class="dlm-reports-widget-body">
					<?php do_action( 'dlm_reports_widget_start' ); ?>
						<div id="dlm_reports_widget"></div>
					<?php do_action( 'dlm_reports_widget_end' ); ?>
				</div>
			<?php
		}

		/**
		 * Enqueues the react script for reports dashboard page widget.
		 *
		 * @return array
		 * @since 5.1.0
		 */
		public function reports_widget_scripts( $hook ) {

			if ( 'index.php' !== $hook ) {
				return;
			}

			$asset_file = require plugin_dir_path( DLM_PLUGIN_FILE ) . 'assets/js/reports-widget/reports-widget.asset.php';
			$enqueue    = array(
				'handle'       => 'dlm-reports-dashboard-widget',
				'dependencies' => $asset_file['dependencies'],
				'version'      => $asset_file['version'],
				'script'       => DLM_URL . 'assets/js/reports-widget/reports-widget.js',
				'style'        => DLM_URL . 'assets/js/reports-widget/reports-widget.css',
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
				array(),
				$enqueue['version']
			);
		}

		public function add_page_title( $title ) {
			if ( ! isset( $_GET['page'] ) || 'download-monitor-reports' !== $_GET['page'] ) {
				return $title;
			}

			return __( 'Download Monitor', 'download-monitor' );
		}
	}
}
