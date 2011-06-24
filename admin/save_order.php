<?php
	/** Load WordPress Administration Bootstrap */
	if(file_exists('../../../wp-load.php')) {
		require_once("../../../wp-load.php");
	} else if(file_exists('../../wp-load.php')) {
		require_once("../../wp-load.php");
	} else if(file_exists('../wp-load.php')) {
		require_once("../wp-load.php");
	} else if(file_exists('wp-load.php')) {
		require_once("wp-load.php");
	} else if(file_exists('../../../../wp-load.php')) {
		require_once("../../../../wp-load.php");
	} else if(file_exists('../../../../wp-load.php')) {
		require_once("../../../../wp-load.php");
	} else {
	
		if(file_exists('../../../wp-config.php')) {
			require_once("../../../wp-config.php");
		} else if(file_exists('../../wp-config.php')) {
			require_once("../../wp-config.php");
		} else if(file_exists('../wp-config.php')) {
			require_once("../wp-config.php");
		} else if(file_exists('wp-config.php')) {
			require_once("wp-config.php");
		} else if(file_exists('../../../../wp-config.php')) {
			require_once("../../../../wp-config.php");
		} else if(file_exists('../../../../wp-config.php')) {
			require_once("../../../../wp-config.php");
		} else {
			echo '<p>Failed to load bootstrap.</p>';
			exit;
		}
	
	}
	
	require_once(ABSPATH.'wp-admin/admin.php');

	if ($_POST['category']) { 
		$data = $_POST['category'];
		if (is_array($data) && sizeof($data)>0) {
			global $wpdb, $wp_dlm_db_taxonomies;
			$order = 1;
			foreach ($data as $item) {
				if (is_numeric($item) && $item>0) {
					$wpdb->query("UPDATE $wp_dlm_db_taxonomies SET `order` = '$order' WHERE id = '$item' AND taxonomy='category';");
					$order++;
				}
			}
		}
	}
?>