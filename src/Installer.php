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

		// No Access Page
		$this->add_no_access_page();
		// Add endpoints
		$dlm_download_handler = new DLM_Download_Handler();
		$dlm_download_handler->add_endpoint();

		// Set default 'No access message'
		$dlm_no_access_error = get_option( 'dlm_no_access_error', '' );
		if ( '' === $dlm_no_access_error ) {
			update_option( 'dlm_no_access_error', sprintf( __( 'You do not have permission to access this download. %1$sGo to homepage%2$s', 'download-monitor' ), '<a href="' . home_url() . '">', '</a>' ) );
		}

		// setup no access page endpoints
		$no_access_page_endpoint = new DLM_Download_No_Access_Page_Endpoint();
		$no_access_page_endpoint->setup();

		$installed_version = get_option( DLM_Constants::OPTION_CURRENT_VERSION );
		$first_install     = ! $installed_version ? true : false;

		if ( $installed_version && version_compare( $installed_version, DLM_UPGRADER_VERSION, '<' ) ) {
			set_transient( 'dlm_needs_upgrade', '1', 30 * DAY_IN_SECONDS );
		}

		update_option( DLM_Constants::OPTION_CURRENT_VERSION, DLM_VERSION );
		// Update options that may have been moved
		$this->update_options();

		// add rewrite rules
		add_rewrite_endpoint( 'download-id', EP_ALL );

		// flush rewrite rules
		flush_rewrite_rules( false );
		flush_rewrite_rules();
		do_action( 'dlm_after_install_setup', $first_install );
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
			$wp_roles->add_cap( 'administrator', 'dlm_use_rest_api_get' );
			$wp_roles->add_cap( 'administrator', 'dlm_use_rest_api_post' );
			$wp_roles->add_cap( 'administrator', 'dlm_use_rest_api_patch' );
			$wp_roles->add_cap( 'administrator', 'dlm_use_rest_api_delete' );
		}
	}

	/**
	 * Creates the shop-related tables in the database.
	 * This is a separate method because it's also called from within the UpgradeManager.
	 */
	public function create_shop_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		$collate = $this->get_db_collate();

		$table_prefix = $wpdb->prefix;

		$tables_sql = array();

		$tables_sql[] = "
		CREATE TABLE IF NOT EXISTS `{$table_prefix}dlm_order` (
		  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		  `status` VARCHAR(255) NOT NULL,
		  `date_created` DATETIME NOT NULL,
		  `date_modified` DATETIME NULL,
		  `currency` VARCHAR(5) NOT NULL,
		  `hash` VARCHAR(255) NOT NULL,
		  PRIMARY KEY (`id`)
		  ) ENGINE = InnoDB {$collate};";

		// order customer.
		$tables_sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}dlm_order_customer` (
		  `first_name` VARCHAR(255) NULL,
		  `last_name` VARCHAR(255) NULL,
		  `company` VARCHAR(255) NULL,
		  `address_1` VARCHAR(255) NULL,
		  `address_2` VARCHAR(255) NULL,
		  `city` VARCHAR(255) NULL,
		  `state` VARCHAR(255) NULL,
		  `postcode` VARCHAR(255) NULL,
		  `country` VARCHAR(5) NULL,
		  `email` VARCHAR(255) NULL,
		  `phone` VARCHAR(50) NULL,
		  `ip_address` VARCHAR(50) NULL,
		  `order_id` INT UNSIGNED NOT NULL,
		  PRIMARY KEY (`order_id`),
		  CONSTRAINT `{$table_prefix}fk_order_customer_order`
		    FOREIGN KEY (`order_id`)
		    REFERENCES `{$table_prefix}dlm_order` (`id`)
		    ON DELETE NO ACTION
		    ON UPDATE NO ACTION
			) ENGINE = InnoDB {$collate};";

		// transaction table.
		$tables_sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}dlm_order_transaction` (
		  `id` INT NOT NULL AUTO_INCREMENT,
		  `date_created` DATETIME NULL,
		  `date_modified` DATETIME NULL,
		  `amount` INT NULL,
		  `status` VARCHAR(50) NULL,
		  `processor` VARCHAR(255) NULL,
		  `processor_nice_name` VARCHAR(255) NULL,
		  `processor_transaction_id` VARCHAR(255) NULL,
		  `processor_status` VARCHAR(255) NULL,
		  `order_id` INT UNSIGNED NOT NULL,
		  PRIMARY KEY (`id`),
		  CONSTRAINT `{$table_prefix}fk_transaction_order1`
		    FOREIGN KEY (`order_id`)
		    REFERENCES `{$table_prefix}dlm_order` (`id`)
		    ON DELETE NO ACTION
		    ON UPDATE NO ACTION
			) ENGINE = InnoDB {$collate};";

		// order items.
		$tables_sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}dlm_order_item` (
		  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		  `order_id` INT UNSIGNED NOT NULL,
		  `label` VARCHAR(255) NULL,
		  `qty` INT NULL,
		  `product_id` INT UNSIGNED NULL,
		  `tax_class` VARCHAR(255) NULL,
		  `tax_total` INT NULL,
		  `subtotal` INT NULL,
		  `total` INT NULL,
		  PRIMARY KEY (`id`),
		  CONSTRAINT `{$table_prefix}fk_order_item_order1`
		    FOREIGN KEY (`order_id`)
		    REFERENCES `{$table_prefix}dlm_order` (`id`)
		    ON DELETE NO ACTION
		    ON UPDATE NO ACTION
			) ENGINE = InnoDB {$collate};";

		// session.
		$tables_sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}dlm_session` (
		  `key` VARCHAR(150) NOT NULL,
		  `hash` VARCHAR(150) NOT NULL,
		  `expiry` DATETIME NOT NULL,
		  `data` LONGTEXT NOT NULL,
		  PRIMARY KEY (`key`)
		  )
	ENGINE = InnoDB {$collate};";

		foreach ( $tables_sql as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Get DB collate
	 *
	 * @return string
	 */
	private function get_db_collate() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		return $collate;
	}

	/**
	 * install_tables function.
	 *
	 * @return void
	 */
	private function install_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		$collate = $this->get_db_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$dlm_log = '
		CREATE TABLE `' . $wpdb->prefix . "download_log` (
			ID bigint(20) NOT NULL auto_increment,
			user_id bigint(20) NOT NULL,
			user_ip varchar(200) NOT NULL,
			uuid varchar(200) NOT NULL,
			user_agent varchar(200) NOT NULL,
			download_id bigint(20) NOT NULL,
			version_id bigint(20) NOT NULL,
			version varchar(200) NOT NULL,
			download_date datetime DEFAULT NULL,
			download_status varchar(200) DEFAULT NULL,
			download_status_message varchar(200) DEFAULT NULL,
			download_location varchar(200) DEFAULT NULL,
			download_category longtext DEFAULT NULL,
			meta_data longtext DEFAULT NULL,
			PRIMARY KEY  (ID),
			KEY attribute_name (download_id)
		) $collate;
		";

		$dlm_reports = "CREATE TABLE `{$wpdb->prefix}dlm_reports_log` (
			date DATE NOT NULL,
			download_ids longtext NULL,
			revenue longtext NULL,
			refunds longtext NULL,
			PRIMARY KEY  (date),
			KEY attribute_name (date)
            ) $collate;";

		$dlm_downloads = "CREATE TABLE `{$wpdb->prefix}dlm_downloads` (
					    ID bigint(20) NOT NULL auto_increment,
						download_id bigint(20) NOT NULL,
						download_count bigint(20) NOT NULL,
						download_versions varchar(200) NOT NULL,
		 				PRIMARY KEY  (ID),
		 				KEY attribute_name (download_id)
		 				) $collate;";

		/**
		 * Cookies table
		 */
		$dlm_cookie = "CREATE TABLE `{$wpdb->prefix}dlm_cookies` (
		 	ID bigint(20) NOT NULL auto_increment,
			hash longtext NULL,
			creation_date datetime DEFAULT NULL,
			expiration_date datetime DEFAULT NULL,
			PRIMARY KEY  (ID),
			KEY attribute_name (ID)
            ) $collate;";

		/**
		 * Cookie meta table
		 */
		$dlm_cookie_meta = "CREATE TABLE `{$wpdb->prefix}dlm_cookiemeta` (
		 	ID bigint(20) NOT NULL auto_increment,
			cookie_id bigint(20) NULL,
			meta_key longtext NULL,
			meta_data longtext NULL,
			PRIMARY KEY  (ID),
			KEY attribute_name (ID)
            ) $collate;";

		/**
		 * Cookie meta table
		 */
		$api_table = "CREATE TABLE `{$wpdb->prefix}dlm_api_keys` (
		 	ID bigint(20) NOT NULL auto_increment,
			user_id bigint(20) NULL,
			public_key longtext NULL,
			token longtext NULL,
			secret_key longtext NULL,
			create_date datetime DEFAULT NULL,
			PRIMARY KEY  (ID),
			KEY attribute_name (ID)
            ) $collate;";

		dbDelta( $dlm_log );
		dbDelta( $dlm_reports );
		dbDelta( $dlm_downloads );
		dbDelta( $dlm_cookie );
		dbDelta( $dlm_cookie_meta );
		dbDelta( $api_table );

		// install shop tables.
		$this->create_shop_tables();
	}

	/**
	 * Protect the upload dir on activation.
	 *
	 * @access public
	 * @return void
	 */
	public function directory_protection() {
		// Install files and folders for uploading files and prevent hotlinking
		$upload_dir = wp_upload_dir();

		$htaccess_content = '# Apache 2.4 and up
		<IfModule mod_authz_core.c>
		Require all denied
		</IfModule>

		# Apache 2.3 and down
		<IfModule !mod_authz_core.c>
		Order Allow,Deny
		Deny from all
		</IfModule>';

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => '.htaccess',
				'content' => $htaccess_content,
			),
			array(
				'base'    => $upload_dir['basedir'] . '/dlm_uploads',
				'file'    => 'index.html',
				'content' => '',
			),
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
	 * Creates No Access page automatically if none is set. Is activated on plugin activation.
	 *
	 * @access public
	 * @return void
	 *
	 * @since  4.8.7
	 */
	public function add_no_access_page() {
		if ( 0 === absint( get_option( 'dlm_no_access_page', 0 ) ) ) {
			$pc = new WPChill\DownloadMonitor\Util\PageCreator();
			$pc->create_no_access_page();
		}
	}

	/**
	 * Update options that may have been moved
	 *
	 * @return void
	 */
	private function update_options() {
		// Update the invoice prefix, that was moved from PayPal to the main settings in the Shop tab
		$paypal_invoice = download_monitor()->service( 'settings' )->get_option( 'gateway_paypal_invoice_prefix' );
		if ( $paypal_invoice ) {
			update_option( 'dlm_invoice_prefix', $paypal_invoice );
			delete_option( 'gateway_paypal_invoice_prefix' );
		}
	}

	/**
	 * Recreate tables in case plugin activation did not work as expected
	 *
	 * @since 5.0.0
	 */
	public function recreate_tables(){
		$this->install_tables();
	}
}
