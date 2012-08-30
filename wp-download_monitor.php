<?php
/*
Plugin Name: Wordpress Download Monitor
Plugin URI: http://wordpress.org/extend/plugins/download-monitor/
Description: Manage downloads on your site, view and show hits, and output in posts. If you are upgrading Download Monitor it is a good idea to <strong>back-up your database</strong> first just in case. You may need to re-save your permalink settings after upgrading if your downloads stop working.
Version: 3.3.5.9
Author: Mike Jolley
Author URI: http://mikejolley.com
*/

/*  Copyright 2011	Michael Jolley  (email : jolley.small.at.googlemail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

################################################################################
// Vars and version
################################################################################

	global $wp_db_version, $wpdb, $dlm_build, $wp_dlm_root, $wp_dlm_image_url, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_formats, $wp_dlm_db_stats, $wp_dlm_db_log, $wp_dlm_db_meta, $def_format, $dlm_url, $downloadtype, $downloadurl, $wp_dlm_db_exists, $download_taxonomies, $download_formats, $download_formats_array, $download_formats_names_array, $meta_blank;
	
	$dlm_build="20120513";
	$wp_dlm_root = plugins_url('/download-monitor/');
	$wp_dlm_image_url 	= get_option('wp_dlm_image_url');
	
	$wp_dlm_db = $wpdb->prefix."download_monitor_files";
	$wp_dlm_db_taxonomies = $wpdb->prefix."download_monitor_taxonomies";
	$wp_dlm_db_relationships = $wpdb->prefix."download_monitor_relationships";
	$wp_dlm_db_formats = $wpdb->prefix."download_monitor_formats";
	$wp_dlm_db_stats = $wpdb->prefix."download_monitor_stats";
	$wp_dlm_db_log = $wpdb->prefix."download_monitor_log";
	$wp_dlm_db_meta = $wpdb->prefix."download_monitor_file_meta";
	
	$def_format = get_option('wp_dlm_default_format');
	$dlm_url = get_option('wp_dlm_url');
	$downloadtype = get_option('wp_dlm_type');
	if (empty($dlm_url)) 
		$downloadurl = $wp_dlm_root.'download.php?id=';
	else
		$downloadurl = get_bloginfo('url').'/'.$dlm_url;
		
	load_plugin_textdomain('wp-download_monitor', false, 'download-monitor/languages/');
	
	$wp_dlm_db_exists = false;
	
	// Check tables exist
	$tables = $wpdb->get_results("show tables;");
	foreach ( $tables as $table )
	{
		foreach ( $table as $value )
		{
		  if ( strtolower($value) ==  strtolower($wp_dlm_db) ) $wp_dlm_db_exists = true;
		}
	}
	
	$download_taxonomies = '';
	$download_formats = '';
	$download_formats_array = '';
	$download_formats_names_array = '';
	$meta_blank = '';
	$download2taxonomy_array = '';
	$download_meta_data_array = '';

################################################################################
// Includes
################################################################################

	include_once(WP_PLUGIN_DIR.'/download-monitor/functions.inc.php');				/* Various functions used throughout */
	include_once(WP_PLUGIN_DIR.'/download-monitor/init.php');						/* Inits the DB/Handles updates */
	include_once(WP_PLUGIN_DIR.'/download-monitor/classes/downloadable_file.class.php');		/* Download Class */
	include_once(WP_PLUGIN_DIR.'/download-monitor/classes/download_taxonomies.class.php');		/* Taxonomy Class */ 

	if (is_admin()) :
		include_once(WP_PLUGIN_DIR.'/download-monitor/admin/admin.php');					/* Admin Interface */
	else :
		include_once(WP_PLUGIN_DIR.'/download-monitor/legacy_shortcodes.php');			/* Old Style shortcodes */
		include_once(WP_PLUGIN_DIR.'/download-monitor/shortcodes.php');					/* New Style shortcodes */
		if (!function_exists('wp_dlmp_styles')) include(WP_PLUGIN_DIR.'/download-monitor/page-addon/download-monitor-page-addon.php');	/* Download Page */
	endif;
																					
################################################################################
// Set up menus within the wordpress admin sections
################################################################################

function wp_dlm_menu() { 
	global $wp_dlm_root, $wp_roles;
	
	if (class_exists('WP_Roles')) 	
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles();
	
	if (is_object($wp_roles)) :
		$wp_roles->add_cap( 'administrator', 'user_can_config_downloads' );
		$wp_roles->add_cap( 'administrator', 'user_can_edit_downloads' );
		$wp_roles->add_cap( 'administrator', 'user_can_add_new_download' );
		$wp_roles->add_cap( 'administrator', 'user_can_view_downloads_log' );
	endif;
	
	$user_can_edit = current_user_can('user_can_edit_downloads');
	$user_can_add_new = current_user_can('user_can_add_new_download');
	$user_can_config = current_user_can('user_can_config_downloads');
	$user_can_view_log = current_user_can('user_can_view_downloads_log');
	
	if ( !$user_can_edit && $user_can_add_new ) {
		$cap = 'user_can_add_new_download';
		$function = 'dlm_addnew';
		$slug = 'dlm_addnew';
	} elseif ( !$user_can_edit && $user_can_config ) {
		$cap = 'user_can_config_downloads';
		$function = 'wp_dlm_config';
		$slug = 'dlm_config';
	} elseif ( !$user_can_edit && $user_can_view_log ) {
		$cap = 'user_can_view_downloads_log';
		$function = 'wp_dlm_log';
		$slug = 'dlm_log';
	} else {
		$cap = 'user_can_edit_downloads';
		$function = 'wp_dlm_admin';
		$slug = __FILE__;
	}
		
    add_menu_page(__('Downloads','wp-download_monitor'), __('Downloads','wp-download_monitor'), $cap , $slug , $function, $wp_dlm_root.'img/menu_icon.png');
	add_submenu_page($slug, __('Edit','wp-download_monitor'),  __('Edit','wp-download_monitor') , 'user_can_edit_downloads', __FILE__ , 'wp_dlm_admin');
	add_submenu_page($slug, __('Add New','wp-download_monitor') , __('Add New','wp-download_monitor') , 'user_can_add_new_download', 'dlm_addnew', 'dlm_addnew');
	add_submenu_page($slug, __('Add Directory','wp-download_monitor') , __('Add Directory','wp-download_monitor') , 'user_can_add_new_download', 'dlm_adddir', 'dlm_adddir');
	
	add_submenu_page($slug, __('Configuration','wp-download_monitor') , __('Configuration','wp-download_monitor') , 'user_can_config_downloads', 'dlm_config', 'wp_dlm_config');
	add_submenu_page($slug, __('Categories','wp-download_monitor') , __('Categories','wp-download_monitor') , 'user_can_config_downloads', 'dlm_categories', 'wp_dlm_categories');
    
    
    if (get_option('wp_dlm_log_downloads')=='yes') add_submenu_page(__FILE__, __('Log','wp-download_monitor') , __('Log','wp-download_monitor') , 'user_can_view_downloads_log', 'dlm_log', 'wp_dlm_log');
}
add_action('admin_menu', 'wp_dlm_menu');

################################################################################
// mod_rewrite rules
################################################################################

function wp_dlm_rewrite($rewrite) {
	global $dlm_url;
	$blog = get_bloginfo('wpurl');
	$base_url = get_bloginfo('url');

	$offset = '';

	// Options +FollowSymLinks
	
	// Borrowed from wp-super-cache
	$home_root = parse_url(get_bloginfo('url'));
	$home_root = isset( $home_root['path'] ) ? trailingslashit( $home_root['path'] ) : '/';
	
$rule = ('
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase '.$home_root.'
RewriteRule ^'.$offset.$dlm_url.'([^/]+)$ '.WP_PLUGIN_URL.'/download-monitor/download.php?id=$1 [L]
</IfModule>
');
	return $rule.$rewrite;	
}

################################################################################
// Hooks
################################################################################

if (!empty($dlm_url)) add_filter('mod_rewrite_rules', 'wp_dlm_rewrite');
	
function wp_dlm_init_hooks() {

	global $wp_db_version, $wpdb, $dlm_build, $wp_dlm_root, $wp_dlm_image_url, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_formats, $wp_dlm_db_stats, $wp_dlm_db_log, $wp_dlm_db_meta, $def_format, $dlm_url, $downloadtype, $downloadurl, $wp_dlm_db_exists, $download_taxonomies, $download_formats, $download_formats_array, $download_formats_names_array, $meta_blank, $download2taxonomy_array, $download_meta_data_array;
	
	$wp_dlm_build = get_option('wp_dlm_build');
	
	if (is_admin()) :
		if (((isset($_GET['activate']) && $_GET['activate']==true)) || ($dlm_build != $wp_dlm_build)) {
			wp_dlm_init_or_upgrade();
		}		
	endif;
	
	if ($wp_dlm_db_exists==true) {

		################################################################################
		// Pre-fetch data before its needed to lessen queries later
		################################################################################
		
		### Get taxonomies
			$download_taxonomies	= new download_taxonomies();
		
		### Get formats		
			$download_formats 		= $wpdb->get_results( "SELECT * FROM $wp_dlm_db_formats;" );
			$download_formats_array = array();
			$download_formats_names_array = array();
			if ($download_formats) foreach ($download_formats as $format) {
				$download_formats_array[$format->id] = $format;
				$download_formats_names_array[] = $format->name;
			}
		
		### Get names of meta fields
			$meta_blank = $wpdb->get_col( "SELECT DISTINCT meta_name FROM $wp_dlm_db_meta;" );
		
		### Download 2 taxonomies
			$download2taxonomy_data = $wpdb->get_results( "SELECT download_id, GROUP_CONCAT(DISTINCT taxonomy_id) AS taxonomies FROM $wp_dlm_db_relationships GROUP BY download_id;" );
			$download2taxonomy_array = array();
			if ($download2taxonomy_data) foreach ($download2taxonomy_data as $data) {
				$download2taxonomy_array[$data->download_id] = explode(',',$data->taxonomies);
			}
			
		### Meta Data
			$download_meta_data = $wpdb->get_results( "SELECT download_id, meta_name, meta_value FROM $wp_dlm_db_meta;" );
			$download_meta_data_array = array();
			if ($download_meta_data) foreach ($download_meta_data as $data) {
				$download_meta_data_array[$data->download_id][$data->meta_name] = stripslashes($data->meta_value);
			}
		
		if (!is_admin()) :
			add_filter('the_content', 'wp_dlm_parse_downloads',1); 
			add_filter('the_excerpt', 'wp_dlm_parse_downloads',1);
			add_filter('the_meta_key', 'wp_dlm_parse_downloads',1);
			add_filter('widget_text', 'wp_dlm_parse_downloads',1);
			add_filter('widget_title', 'wp_dlm_parse_downloads',1);
			add_filter('the_content', 'wp_dlm_parse_downloads_all',1);			
			
			add_filter('the_excerpt', 'do_shortcode',11);
			add_filter('the_meta_key', 'do_shortcode',11);
			add_filter('widget_text', 'do_shortcode',11);
			add_filter('widget_title', 'do_shortcode',11);
			
			add_filter( 'download_description', 'wptexturize'        );
			add_filter( 'download_description', 'convert_smilies'    );
			add_filter( 'download_description', 'convert_chars'      );
			add_filter( 'download_description', 'wpautop'            );
			add_filter( 'download_description', 'shortcode_unautop'  );
		else :
			wp_enqueue_script('jquery-ui-sortable');
			add_action('media_buttons', 'wp_dlm_add_media_button', 20);
		endif;
	}
}
add_action('init','wp_dlm_init_hooks');

function wp_dlm_activate() {
	wp_dlm_init_or_upgrade();		
}
register_activation_hook( __FILE__, 'wp_dlm_activate' );
