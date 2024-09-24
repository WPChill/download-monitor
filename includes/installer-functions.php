<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Plugin activation hook.
 * When site is multisite and plugin is network activated, installer will run for each blog
 *
 * @param bool $network_wide
 */
function _download_monitor_install( $network_wide = false ) {

	download_monitor_delete_cached_scripts();
	// Let's delete the extensions transient so that it's refreshed when plugin is installed/activated, this is to ensure
	// that the extensions list is always up-to-date.
	delete_transient( 'dlm_extension_json' );
	delete_transient( 'dlm_pro_extensions' );

	// DLM Installer.
	$installer = new DLM_Installer();

	// check if.
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	// check if it's multisite.
	if ( is_multisite() && true === $network_wide ) {

		// get websites
		//$sites = wp_get_sites(); // Deprecated since 4.6.
		$sites = get_sites();

		// loop
		if ( count( $sites ) > 0 ) {
			foreach ( $sites as $site ) {

				// switch to blog.
				switch_to_blog( $site->blog_id );

				// run installer on blog.
				$installer->install();

				// restore current blog.
				restore_current_blog();
			}
		}
	} else {
		// no multisite so do normal install.
		$installer->install();
	}
}

/**
 * Run installer for new blogs on multisite when plugin is network activated
 *
 * @param $blog_id
 * @param $user_id
 * @param $domain
 * @param $path
 * @param $site_id
 * @param $meta
 */
function _download_monitor_mu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	// check if plugin is network activated
	if ( is_plugin_active_for_network( 'download-monitor/download-monitor.php' ) ) {

		// DLM Installer
		$installer = new DLM_Installer();

		// switch to new blog
		switch_to_blog( $blog_id );

		// run installer on blog
		$installer->install();

		// restore current blog
		restore_current_blog();
	}
}

/**
 * Delete DLM log table on multisite when blog is deleted
 *
 * @param $tables
 *
 * @return array
 */
function _download_monitor_mu_delete_blog( $tables ) {
	global $wpdb;
	$tables[] = $wpdb->prefix . 'download_log';

	return $tables;
}

/**
 * Delete cached js and css scripts from optimisation plugins on plugin activation.
 *
 * @return void
 * @since 4.8.0
 */
function download_monitor_delete_cached_scripts() {

	// WP Rocket.
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}

	if ( function_exists( 'rocket_clean_minify' ) ) {
		rocket_clean_minify();
	}

	// WP Optimize.
	if ( class_exists( 'WP_Optimize_Minify_Commands' ) ) {
		$WP_Optimize_Minify = new WP_Optimize_Minify_Commands();
		$WP_Optimize_Minify->purge_minify_cache();
	}

	// WP Fastest Cache.
	if ( class_exists( 'WpFastestCache' ) ) {
		$WP_Fastest_Cache = new WpFastestCache();
		$WP_Fastest_Cache->deleteCache( true );
	}

	// WP Super Cache.
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
}

/**
 * Check if the Download Monitor tables are installed
 *
 * return bool
 *
 * @since 5.0.0
 *
 */
function dlm_check_tables() {
	global $wpdb;
	$transient = get_transient( 'dlm_tables_check' );
	if ( get_transient( 'dlm_tables_check' ) ) {
		return $transient;
	}
	$return = true;
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}download_log'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}dlm_reports_log'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}dlm_downloads'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}dlm_cookies'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}dlm_cookiemeta'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}dlm_api_keys'" );
	if ( empty( $tables ) ) {
		$return = false;
	}
	set_transient( 'dlm_tables_check', $return, 30 * DAY_IN_SECONDS );
	return $return;
}
