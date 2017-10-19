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
		require_once( 'class-dlm-taxonomy-manager.php' );
		$taxonomy_manager = new DLM_Taxonomy_Manager();
		$taxonomy_manager->setup();

		// Setup Post Types
		require_once( 'class-dlm-post-type-manager.php' );
		$post_type_manager = new DLM_Post_Type_Manager();
		$post_type_manager->setup();

		// Create Database Table
		$this->install_tables();

		// Directory Protection
		$this->directory_protection();

		// Add endpoints
		require_once( 'class-dlm-download-handler.php' );
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
		require_once( 'class-dlm-download-no-access-page-endpoint.php' );
		$no_access_page_endpoint = new DLM_Download_No_Access_Page_Endpoint();
		$no_access_page_endpoint->setup();

		// Set the current version
		require_once( 'class-dlm-constants.php' );
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
	  type varchar(200) NOT NULL default 'download',
	  user_id bigint(20) NOT NULL,
	  user_ip varchar(200) NOT NULL,
	  user_agent varchar(200) NOT NULL,
	  download_id bigint(20) NOT NULL,
	  version_id bigint(20) NOT NULL,
	  version varchar(200) NOT NULL,
	  download_date datetime NOT NULL default '0000-00-00 00:00:00',
	  download_status varchar(200) NULL,
	  download_status_message varchar(200) NULL,
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

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => '.htaccess',
				'content' => 'deny from all'
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