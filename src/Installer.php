<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Installer {

	/**
	 * Install all requirements for Download Monitor
	 */
	public function install() {

		// Init User Roles
		$this->init_user_roles();

		// Setup Taxonomies
		$taxonomy_manager = new DLM_Taxonomy_Manager();
		$taxonomy_manager->setup();

		// Setup Post Types
		$post_type_manager = new DLM_Post_Type_Manager();
		$post_type_manager->setup();

		// Create Database Table
		$this->install_tables();

		// Directory Protection
		$this->directory_protection();

		// Add endpoints
		$dlm_download_handler = new DLM_Download_Handler();
		$dlm_download_handler->add_endpoint();

		// Set default 'No access message'
		$dlm_no_access_error = get_option( 'dlm_no_access_error', '' );
		if ( '' === $dlm_no_access_error ) {
			update_option( 'dlm_no_access_error', sprintf( __( 'You do not have permission to access this download. %sGo to homepage%s', 'download-monitor' ), '<a href="' . home_url() . '">', '</a>' ) );
		}

		// create no access page
		$this->create_no_access_page();

		// setup no access page endpoints
		$no_access_page_endpoint = new DLM_Download_No_Access_Page_Endpoint();
		$no_access_page_endpoint->setup();

		// Set the current version
		update_option( DLM_Constants::OPTION_CURRENT_VERSION, DLM_VERSION );

		// add rewrite rules
		add_rewrite_endpoint( 'download-id', EP_ALL );

		// flush rewrite rules
		flush_rewrite_rules();
	}


	/**
	 * Init user roles
	 *
	 * @return void
	 */
	public function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'administrator', 'manage_downloads' );
			$wp_roles->add_cap( 'administrator', 'dlm_manage_logs' );
			$wp_roles->add_cap( 'administrator', 'dlm_view_reports' );
		}
	}

	/**
	 * install_tables function.
	 *
	 * @return void
	 */
	private function install_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$dlm_tables = "
	CREATE TABLE `" . $wpdb->prefix . "download_log` (
	  ID bigint(20) NOT NULL auto_increment,
	  user_id bigint(20) NOT NULL,
	  user_ip varchar(200) NOT NULL,
	  user_agent varchar(200) NOT NULL,
	  download_id bigint(20) NOT NULL,
	  version_id bigint(20) NOT NULL,
	  version varchar(200) NOT NULL,
	  download_date datetime DEFAULT NULL,
	  download_status varchar(200) DEFAULT NULL,
	  download_status_message varchar(200) DEFAULT NULL,
	  meta_data longtext DEFAULT NULL,
	  PRIMARY KEY  (ID),
	  KEY attribute_name (download_id)
	) $collate;
	";
		dbDelta( $dlm_tables );
	}

	/**
	 * Protect the upload dir on activation.
	 *
	 * @access public
	 * @return void
	 */
	private function directory_protection() {

		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$htaccess_content = "# Apache 2.4 and up
<IfModule mod_authz_core.c>
Require all denied
</IfModule>

# Apache 2.3 and down
<IfModule !mod_authz_core.c>
Order Allow,Deny
Deny from all
</IfModule>";

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => '.htaccess',
				'content' => $htaccess_content
			),
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => 'index.html',
				'content' => ''
			)
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 * Create no access page
	 */
	public function create_no_access_page() {

		// create cars listing page if not exists
		$listings_slug = sanitize_title( __( 'No Access', 'download-monitor' ) );
		$listings_page = get_page_by_path( $listings_slug );

		// check if listings page exists
		if ( null == $listings_page ) {

			// create page
			$page_id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_title'   => __( 'No Access', 'download-monitor' ),
				'post_content' => '[dlm_no_access]',
				'post_status'  => 'publish'
			) );

			if ( ! is_wp_error( $page_id ) ) {
				// set page id as dlm_no_access_page
				update_option( 'dlm_no_access_page', absint( $page_id ) );
			}


		}

	}

}