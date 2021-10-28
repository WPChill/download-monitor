<?php

class DLM_Log_Page {

	/**
	 * Setup log page related hooks
	 */
	public function setup() {

		add_filter( 'dlm_admin_menu_links', array( $this, 'add_logs_menu' ), 30 );
		add_action( 'admin_init', array( $this, 'catch_export_request' ) );
		add_action( 'admin_init', array( $this, 'catch_delete_request' ) );
	}

	/**
	 * Add admin menu item
	 */
	public function add_logs_menu($links) {

		// Logging object
		$logging = new DLM_Logging();

		// Logs page
		if ( $logging->is_logging_enabled() ) {

			$links[] = array(
					'page_title' => __( 'Logs', 'download-monitor' ),
					'menu_title' => __( 'Logs', 'download-monitor' ),
					'capability' => 'manage_options',
					'menu_slug'  => 'download-monitor-logs',
					'function'   => array( $this, 'view' ),
					'priority'   => 50,
			);
		}

		return $links;
	}

	/**
	 * Catch the export request
	 */
	public function catch_export_request() {
		if ( isset( $_GET['dlm_download_logs'] ) ) {
			$exportCSV = new DLM_Log_Export_CSV();
			$exportCSV->run();
		}
	}

	/**
	 * Catch the delete request
	 */
	public function catch_delete_request() {
		if ( isset( $_GET['dlm_delete_logs'] ) ) {

			// check if getter is set
			if ( empty( $_GET['dlm_delete_logs'] ) ) {
				return;
			}

			// check if user has permission to do it
			if ( ! current_user_can( 'manage_downloads' ) ) {
				wp_die( "You're not allowed to delete logs." );
			}

			// check admin referer
			check_admin_referer( 'delete_logs' );

			// do query
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->download_log};" );

		}
    }

	/**
	 * Display the log page
	 */
	public function view() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		$DLM_Logging_List_Table = new DLM_Logging_List_Table();
		$DLM_Logging_List_Table->prepare_items();

		$delete_url = wp_nonce_url( add_query_arg( 'dlm_delete_logs', 'true', admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-logs' ) ), 'delete_logs' );

		?>
        <div class="wrap">
            <div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

            <h1><?php echo esc_html__( 'Download Logs', 'download-monitor' ); ?>
                <a href="<?php echo esc_url( add_query_arg( 'dlm_download_logs', 'true', admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-logs' ) ) ); ?>"
                        class="add-new-h2"><?php echo esc_html__( 'Export CSV', 'download-monitor' ); ?></a>
                <a onclick="return confirm('<?php echo esc_html__( "Are you sure you want to delete ALL log items?", "download-monitor" ); ?>');" href="<?php echo esc_url( $delete_url ); ?>"
                        class="add-new-h2 dlm-delete-logs"><?php echo esc_html__( 'Delete Logs', 'download-monitor' ); ?></a></h1><br/>

            <form id="dlm_logs" method="post">
				<?php $DLM_Logging_List_Table->display() ?>
            </form>
        </div>
		<?php
	}

}