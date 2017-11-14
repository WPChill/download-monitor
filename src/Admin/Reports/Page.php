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
	 * Get Reports page URL
	 *
	 * @return string
	 */
	public function get_url() {
		return admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-reports' );
	}

	/**
     * Get log items based on filters
     *
	 * @return array
	 */
	public function get_log_items() {
	    return array();
    }

	/**
     * Get current tab
     *
	 * @return string
	 */
    private function get_current_tab() {
	    return ( ! empty( $_GET['tab'] ) ) ? $_GET['tab'] : "totals";
    }

	/**
     * 
     *
	 * @param $cur
	 */
    private function char_button($cur) {
	    echo "<a href='' class='dlm-reports-header-chart-switcher'></a>";
    }

	/**
	 * Display page
	 */
	public function view() {

		$tabs = array(
			'totals'       => __( 'Totals', 'download-monitor' ),
			'per_download' => __( 'Per Download', 'download-monitor' ),
		);

		$current_tab = $this->get_current_tab();
		$char_type = ( ! empty( $_GET['char_type'] ) ) ? $_GET['char_type'] : "line";

		?>
        <div class="wrap dlm-reports">
            <div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

            <h1><?php _e( 'Download Reports', 'download-monitor' ); ?></h1>

            <h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $tab_key => $tab ) {
					echo "<a href='" . add_query_arg( array( 'tab' => $tab_key ), $this->get_url() ) . "' class='nav-tab" . ( ( $tab_key === $current_tab ) ? " nav-tab-active" : "" ) . "'>" . $tab . "</a>";
				}
				?>
                <?php $this->char_button( $char_type ); ?>
            </h2><br/>

            <div class="dlm-reports-chart">

            </div>
            <div class="dlm-reports-table">

            </div>
        </div>
		<?php
	}
}