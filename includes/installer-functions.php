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
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
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

	update_option( 'dlm_activation_check_default_languages', true );
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

function dlm_handle_wpml_translation_ajx() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'WPML\ST\Main\Ajax\SaveTranslation' ) ) {
		return;
	}

	if ( ! isset( $_POST['endpoint'] ) || str_replace( '\\', '', sanitize_text_field( wp_unslash( $_POST['endpoint'] ) ) ) !== 'WPMLSTMainAjaxSaveTranslation' ) {
		return;
	}

	$data = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : null;

	if ( ! $data || ! isset( $data['id'] ) ) {
		return;
	}

	$string = icl_get_string_by_id( absint( $data['id'] ) );

	if ( empty( $string ) ) {
		return;
	}

	$endpoint_option = get_option( 'dlm_download_endpoint', 'download' );

	if ( $string === $endpoint_option ) {
		set_transient( 'dlm_download_endpoints_rewrite', true, HOUR_IN_SECONDS );
	}
}

function dlm_check_default_translations(){
	if ( ! get_option( 'dlm_check_default_languages', false ) || get_option( 'dlm_activation_check_default_languages' ) ) {

		// sets the default language of dlm_downloads post types
		dlm_set_wpml_language_default();
		dlm_set_polylang_language_for_dlm_terms();

		// add option so we sure the check is ran at least once
		add_option( 'dlm_check_default_languages', 'checked' );

		// delte activation option.
		delete_option( 'dlm_activation_check_default_languages' );
	}
}

function dlm_set_wpml_language_default() {
	if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! function_exists( 'wpml_get_default_language' ) || ! class_exists( 'WP_Query' ) ) {
		return;
	}

	global $sitepress;

	if ( ! method_exists( $sitepress, 'set_element_language_details' ) ) {
		return;
	}

	$default_lang = apply_filters( 'wpml_default_language', null );
	if ( ! $default_lang ) {
		return;
	}

	$args = array(
		'post_type'      => 'dlm_download',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'     => '_icl_lang_duplicate',
				'compare' => 'NOT EXISTS',
			),
		),
	);

	$downloads = get_posts( $args );

	foreach ( $downloads as $post_id ) {
		$has_language = apply_filters(
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id,
				'element_type' => 'post_dlm_download',
			)
		);

		if ( ! $has_language ) {
			$sitepress->set_element_language_details(
				$post_id,
				'post_dlm_download',
				null,
				$default_lang,
				null
			);
		}
	}
}

function dlm_set_polylang_language_for_dlm_terms() {

	if ( ! function_exists( 'pll_get_term_language' ) || ! function_exists( 'pll_set_term_language' ) || ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	$default_lang = pll_default_language();
	$taxonomies   = array( 'dlm_download_category', 'dlm_download_tag' );

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {
			$current_lang = pll_get_term_language( $term->term_id );
			if ( ! $current_lang ) {
				pll_set_term_language( $term->term_id, $default_lang );
			}
		}
	}
}
