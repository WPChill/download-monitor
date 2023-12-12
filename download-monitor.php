<?php
/*
	Plugin Name: Download Monitor
	Plugin URI: https://www.download-monitor.com
	Description: A full solution for managing and selling downloadable files, monitoring downloads and outputting download links and file information on your WordPress powered site.
	Version: 4.9.4
	Author: WPChill
	Author URI: https://wpchill.com
	Requires at least: 5.4
	Tested up to: 6.3
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

if ( ! function_exists( 'dm_fs' ) ) {
    // Create a helper function for easy SDK access.
    function dm_fs() {
        global $dm_fs;

        if ( ! isset( $dm_fs ) ) {
            // Include Freemius SDK.
            require_once plugin_dir_path(__FILE__) . '/includes/submodules/freemius/start.php';

            $dm_fs = fs_dynamic_init( array(
                'id'                  => '13516',
                'slug'                => 'download-monitor',
                'type'                => 'plugin',
                'public_key'          => 'pk_a967cb31e904cf6065501bfda5138',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'edit.php?post_type=dlm_download',
                    'first-path'     => 'edit.php?post_type=dlm_download&page=download-monitor-about-page',
                    'account'        => false,
                    'contact'        => false,
                ),
            ) );
        }

        return $dm_fs;
    }

	// Init Freemius.
	dm_fs();
	// Signal that SDK was initiated.
	do_action( 'dm_fs_loaded' );

	function dlm_remove_support_menu( $is_visible, $menu_id ) {
		if ( 'support' === $menu_id ) {
			return false;
		}

		return $is_visible;
	}

	dm_fs()->add_filter( 'is_submenu_visible', 'dlm_remove_support_menu', 10, 2 );
}

// Define DLM Version
define( 'DLM_VERSION', '4.9.4' );
define( 'DLM_UPGRADER_VERSION', '4.6.0' );

// Define DLM FILE
define( 'DLM_PLUGIN_FILE', __FILE__ );
define( 'DLM_URL', plugin_dir_url( __FILE__ ) );
define( 'DLM_FILE', plugin_basename( __FILE__ ) );
define( 'DLM_BETA', false );
define( 'DLM_BETA_VERSION', 'x.x.x' );

// Add meta tags to head for DLM Version.
add_action(
	'wp_head',
	function () {
		// Add filter to hide plugin version.
		if ( apply_filters( 'dlm_hide_meta_version', false ) ) {
			return;
		}
		echo '<meta name="dlm-version" content="' . esc_attr( DLM_VERSION ) . '">';
	},
	1 );

if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	require_once plugin_dir_path( DLM_PLUGIN_FILE ) . 'includes/bootstrap.php';
} else {
	require_once plugin_dir_path( DLM_PLUGIN_FILE ) . 'includes/php-too-low.php';
}

if( ! class_exists( 'DLM_Review') && is_admin() ) {
	require_once dirname( __FILE__ ) . '/includes/admin/class-dlm-review.php';
}
