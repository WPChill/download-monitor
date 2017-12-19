<?php

class DLM_LU_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 12 );
	}

	/**
	 * Add settings menu item
	 */
	public function add_admin_menu() {
		// Settings page
		add_submenu_page( '_dlm_not_existing_slug', __( 'Legacy Upgrader', 'download-monitor' ), __( 'Legacy Upgrader', 'download-monitor' ), 'manage_options', 'dlm_legacy_upgrade', array(
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
            <h1><?php _e( 'Download Monitor - Legacy Upgrade', 'download-monitor' ); ?></h1><br/>
            <p><?php printf( __( "Welcome to the Download Monitor Legacy Upgrader. On this page we will upgrade your old Download Monitor (legacy) data so it will work with the latest version. If you're on this page, it should mean that you updated to this version from Download Monitor %s. If you're unsure if this is correct, or you want to read more about the legacy upgrade, we've setup a page that will explain this process in a lot more detail. %sClick here%s if to view that page.", 'download-monitor' ), "<strong>3.x</strong>", "<a href='https://www.download-monitor.com/kb/legacy-upgrade?utm_source=plugin&utm_medium=dlm-lu-upgrade-page&utm_campaign=dlm-lu-more-information' target='_blank'>", "</a>" ); ?></p>

            <div id="dlm-legacy-upgrade-container"></div>
        </div>
		<?php
	}

}