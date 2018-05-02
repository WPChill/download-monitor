<?php
/*
	Plugin Name: Download Monitor
	Plugin URI: https://www.download-monitor.com
	Description: A full solution for managing downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
	Version: 4.0.8
	Author: Never5
	Author URI: https://www.never5.com
	Requires at least: 3.8
	Tested up to: 4.9.5
	Text Domain: download-monitor

	License: GPL v3

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

	Original project created by Mike Jolley.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// Define DLM Version
define( 'DLM_VERSION', '4.0.8' );

// Define DLM FILE
define( 'DLM_PLUGIN_FILE', __FILE__ );

function download_monitor() {
	static $instance;
	if ( is_null( $instance ) ) {
		$instance = new WP_DLM();
	}
	return $instance;
}

function _load_download_monitor() {
	// fetch instance and store in global
	$GLOBALS['download_monitor'] = download_monitor();
}

// require autoloader
require_once dirname( __FILE__ ) . '/vendor/autoload_52.php';

// Init plugin
add_action( 'plugins_loaded', '_load_download_monitor', 10 );

if ( is_admin() && ( false === defined( 'DOING_AJAX' ) || false === DOING_AJAX ) ) {

	// set installer file constant
	define( 'DLM_PLUGIN_FILE_INSTALLER', __FILE__ );

	// include installer functions
	require_once( 'includes/installer-functions.php' );

	// Activation hook
	register_activation_hook( DLM_PLUGIN_FILE_INSTALLER, '_download_monitor_install' );

	// Multisite new blog hook
	add_action( 'wpmu_new_blog', '_download_monitor_mu_new_blog', 10, 6 );

	// Multisite blog delete
	add_filter( 'wpmu_drop_tables', '_download_monitor_mu_delete_blog' );
}
