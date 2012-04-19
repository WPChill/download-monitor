<?php
/** Load WordPress Administration Bootstrap */
$wp_root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if(file_exists($wp_root . '/wp-load.php')) {
	require_once($wp_root . "/wp-load.php");
} else if(file_exists($wp_root . '/wp-config.php')) {
	require_once($wp_root . "/wp-config.php");
} else {
	exit;
}

global $wp_db_version;
if ($wp_db_version < 8201) {
	// Pre 2.6 compatibility (BY Stephen Rider)
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
		else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

require_once(ABSPATH.'wp-admin/admin.php');

load_plugin_textdomain('wp-download_monitor', false, 'download-monitor/languages/');

global $wpdb, $wp_dlm_db, $wp_dlm_db_log;

$logs = $wpdb->get_results("
	SELECT $wp_dlm_db.*, $wp_dlm_db_log.ip_address, $wp_dlm_db_log.date, $wp_dlm_db_log.user_id
	FROM $wp_dlm_db_log  
	INNER JOIN $wp_dlm_db ON $wp_dlm_db_log.download_id = $wp_dlm_db.id 
	ORDER BY $wp_dlm_db_log.date DESC;
	");
	
$log = "Download ID,Title,File,User,IP Address,Date\n";

if (!empty($logs)) {
	foreach ( $logs as $l ) {
		$date = date_i18n(__("jS M Y H:i:s","wp-download_monitor"), strtotime($l->date));
		$path 	= get_bloginfo('wpurl').'/'.get_option('upload_path').'/';		
		$file = str_replace($path, "", $l->filename);
		$links = explode("/",$file);
		$file = end($links);
		$user = '';
		if ($l->user_id) {
			$user_info = get_userdata($l->user_id);
			$user = $user_info->user_login.' (#'.$user_info->ID.')';
		}
		
		$log .= "$l->id,$l->title,$file,$user,$l->ip_address,$date\n";		
	}
} 

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=download_log.csv");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Length: " . strlen($log));

echo $log;
exit;