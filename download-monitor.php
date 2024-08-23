<?php
/*
	Plugin Name: Download Monitor
	Plugin URI: https://www.download-monitor.com
	Description: A full solution for managing and selling downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
	Version: 5.0.6
	Author: WPChill
	Author URI: https://wpchill.com
	Requires at least: 5.5
	Tested up to: 6.6
	Requires PHP: 7.4
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

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

// Define DLM Version
define('DLM_VERSION', '5.0.6');
define('DLM_UPGRADER_VERSION', '4.6.0');

// Define DLM FILE
define('DLM_PLUGIN_FILE', __FILE__);
define('DLM_URL', plugin_dir_url(__FILE__));
define('DLM_FILE', plugin_basename(__FILE__));
define('DLM_BETA', false);
define('DLM_BETA_VERSION', 'x.x.x');

// Add meta tags to head for DLM Version.
add_action(
	'wp_head',
	function () {
		// Add filter to hide plugin version.
		if (apply_filters('dlm_hide_meta_version', false)) {
			return;
		}
		echo '<meta name="dlm-version" content="' . esc_attr(DLM_VERSION) . '">';
	},
	1
);

if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
	require_once plugin_dir_path(DLM_PLUGIN_FILE) . 'includes/bootstrap.php';
} else {
	require_once plugin_dir_path(DLM_PLUGIN_FILE) . 'includes/php-too-low.php';
}

if (! class_exists('DLM_Review') && is_admin()) {
	require_once dirname(__FILE__) . '/includes/admin/class-dlm-review.php';
}
