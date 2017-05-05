<?php
/*
	Plugin Name: Download Monitor
	Plugin URI: https://www.download-monitor.com
	Description: A full solution for managing downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
	Version: 1.9.7
	Author: Never5
	Author URI: https://www.never5.com
	Requires at least: 3.8
	Tested up to: 4.7.4
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
define( 'DLM_VERSION', '1.9.7' );

function __download_monitor_main() {

	// Define DLM FILE
	define( 'DLM_PLUGIN_FILE', __FILE__ );

	// Require class file
	require_once plugin_dir_path( DLM_PLUGIN_FILE ) . 'includes/class-wp-dlm.php';

	// Create DLM object
	$dlm = new WP_DLM();

	// Backwards compatibility
	$GLOBALS['download_monitor'] = $dlm;
}

// Init plugin
add_action( 'plugins_loaded', '__download_monitor_main', 10 );

if ( is_admin() && ( false === defined( 'DOING_AJAX' ) || false === DOING_AJAX ) ) {

	// set installer file constant
	define( 'DLM_PLUGIN_FILE_INSTALLER', __FILE__ );

	// include installer functions
	require_once( 'installer-functions.php' );

	// Activation hook
	register_activation_hook( DLM_PLUGIN_FILE_INSTALLER, '__download_monitor_install' );

	// Multisite new blog hook
	add_action( 'wpmu_new_blog', '__download_monitor_mu_new_blog', 10, 6 );

	// Multisite blog delete
	add_filter( 'wpmu_drop_tables', '__download_monitor_mu_delete_blog' );
}
