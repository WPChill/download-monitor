<?php

/**
 * DLM_Admin_Helper
 */
class DLM_Admin_Helper {

	/**
	 * Holds the class object.
	 *
	 * @since 4.4.7
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Primary class constructor.
	 *
	 * @since 4.4.7
	 */
	public function __construct() {
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Admin_Helper object.
	 * @since 4.4.7
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Admin_Helper ) ) {
			self::$instance = new DLM_Admin_Helper();
		}

		return self::$instance;
	}

	/**
	 * Tab navigation display
	 *
	 * @param  mixed  $tabs        Tabs used for settings navigation.
	 * @param  mixed  $active_tab  The active tab.
	 *
	 * @return void
	 */
	public static function dlm_tab_navigation( $tabs, $active_tab ) {
		if ( $tabs ) {
			$i = count( $tabs );
			$j = 1;

			foreach ( $tabs as $tab_id => $tab ) {
				$last_tab = ( $i == $j ) ? ' last_tab' : '';
				$active   = $active_tab == $tab_id ? ' nav-tab-active' : '';
				$j ++;

				if ( isset( $tab['url'] ) ) {
					// For Extensions and Gallery list tabs.
					$url = $tab['url'];
				} else {
					// For Settings tabs.
					$url = admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=' . $tab_id );
				}

				echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active ) . esc_attr( $last_tab ) . '" ' . ( isset( $tab['target'] ) ? 'target="' . esc_attr( $tab['target'] ) . '"' : '' ) . '>';

				if ( isset( $tab['icon'] ) ) {
					echo '<span class="dashicons ' . esc_attr( $tab['icon'] ) . '"></span>';
				}

				// For Extensions and Gallery list tabs.
				if ( isset( $tab['name'] ) ) {
					echo esc_html( $tab['name'] );
				}

				// For Settings tabs.
				if ( isset( $tab['label'] ) ) {
					echo esc_html( $tab['label'] );
				}

				if ( isset( $tab['badge'] ) ) {
					echo '<span class="dlm-badge">' . esc_html( $tab['badge'] ) . '</span>';
				}

				echo '</a>';
			}
		}
	}

	/**
	 * Callback to sort tabs/fields on priority.
	 *
	 * @param  mixed  $a  Current element from array.
	 * @param  mixed  $b  Next element from array.
	 *
	 * @return array
	 */
	public static function sort_data_by_priority( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return - 1;
		}
		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
	}

	/**
	 * Checks if this is one of Download Monitor's page or not
	 *
	 * @return bool
	 *
	 * @since 4.5.4
	 */
	public static function check_if_dlm_page() {
		if ( ! isset( $_GET['post_type'] ) || ( 'dlm_download' !== $_GET['post_type'] && 'dlm_product' !== $_GET['post_type'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Recreates the upgrade environment. Previously declared in DLM_Settings_Page
	 *
	 * @return bool
	 * @since 4.6.4
	 */
	public static function redo_upgrade() {
		global $wp, $wpdb, $pagenow;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		// Drop the dlm_reports_log
		$drop_statement = "DROP TABLE IF EXISTS {$wpdb->prefix}dlm_reports_log,{$wpdb->prefix}dlm_downloads";
		$wpdb->query( $drop_statement );

		// Delete upgrade history and set the need DB pgrade
		delete_option( 'dlm_db_upgraded' );
		delete_transient( 'dlm_db_upgrade_offset' );
		set_transient( 'dlm_needs_upgrade', '1', 30 * DAY_IN_SECONDS );

		return true;
	}

	/**
	 * Check the column type.
	 *
	 * @param  string  $table_name  The table.
	 * @param  string  $col_name    The column.
	 * @param  string  $col_type    The type.
	 *
	 * @return bool|null
	 * @since 4.8.0
	 */
	public static function check_column_type( $table_name, $col_name, $col_type ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
		$results = $wpdb->get_results( "DESC $table_name" );
		if ( empty( $results ) ) {
			return null;
		}

		foreach ( $results as $row ) {
			if ( $row->Field === $col_name ) {
				// Got our column, check the params.
				if ( ( null !== $col_type ) && ( $row->Type !== $col_type ) ) {
					return false;
				}

				return true;
			} // End if found our column.
		}

		return null;
	}

	/**
	 * Check whether the license is valid or not.
	 *
	 * @param  string  $functionality  The functionality.
	 *
	 * @return bool
	 *
	 * @since 3.8.2
	 */
	public function check_license_validity( $functionality ) {
		// Check if license is valid.
		if ( ! class_exists( 'DLM_Product_License' ) ) {
			return false;
		}
		$license = new DLM_Product_License( $functionality );

		if ( ! $license || ! $license->is_active() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the week start day.
	 *
	 * @return string
	 *
	 * @since 4.8.8
	 */
	public static function get_wp_weekstart() {
		$week_start = get_option( 'start_of_week', 0 );

		// It returns either Sunday or Monday because those are the only two options the date-range picker supports.
		return ( 0 === absint( $week_start ) ? 'sunday' : 'monday' );
	}

	/**
	 * Check if the current page is a DLM admin page.
	 *
	 * @return bool
	 *
	 * @since 5.0.0
	 */
	public static function is_dlm_admin_page() {
		global $pagenow;
		$dlm_admin_page = false;
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			// Check to see if it's a page for a download or product.
			if ( isset( $screen->post_type ) && 'dlm_download' === $screen->post_type ) {
				$dlm_admin_page = true;
			}
		}

		if ( 'edit.php' === $pagenow || 'post.php' === $pagenow ) {
			if ( isset( $_GET['post_type'] ) && 'dlm_download' === $_GET['post_type'] ) {
				$dlm_admin_page = true;
			} elseif ( isset( $_GET['post'] ) && 'dlm_download' === get_post_type( $_GET['post'] ) ) {
				$dlm_admin_page = true;
			}
		}

		return $dlm_admin_page;
	}
}
