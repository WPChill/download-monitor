<?php

class DLM_Reports_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 12 );
	}

	/**
	 * Add settings menu item
	 */
	public function add_admin_menu() {
		// Settings page
		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Reports', 'download-monitor' ), __( 'Reports', 'download-monitor' ), 'dlm_view_reports', 'download-monitor-reports', array(
			$this,
			'view'
		) );
	}

	/**
	 * Display page
	 */
	public function view() {
		?>
        <div class="wrap">
            <div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

            <h1><?php _e( 'Download Reports', 'download-monitor' ); ?></h1>

            <p>Soon</p>
        </div>
		<?php
	}
}