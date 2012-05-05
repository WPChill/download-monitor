<?php
//
// jQuery File Tree PHP Connector - modified for php4/wordpress compatibility
//

// Ajax only
if ( basename(__FILE__) == 'jqueryFileTreeDir.php' && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) die();

if(file_exists('../../../../../../wp-load.php')) {
	require_once("../../../../../../wp-load.php");
} else if(file_exists('../../../../../../../wp-load.php')) {
	require_once("../../../../../../../wp-load.php");
} else {
	if(file_exists('../../../../../../wp-config.php')) {
		require_once("../../../../../../wp-config.php");
	} else if(file_exists('../../../../../../../wp-config.php')) {
		require_once("../../../../../../../wp-config.php");
	} else {
		exit;
	}
}

if (!function_exists('php4_scandir') && !function_exists('scandir')) {
	function php4_scandir($dir,$listDirectories=true) {
	    $dirArray = array();
	    if ($handle = opendir($dir)) {
	        while (false !== ($file = readdir($handle))) {
	            if($listDirectories == false) { if(is_dir($file)) { continue; } }
	            array_push($dirArray,basename($file));
	        }
	        closedir($handle);
	    }
	    return $dirArray;
	}
}

require_once(ABSPATH.'wp-admin/admin.php');

$_POST['dir'] = urldecode($_POST['dir']);

$root_dir = '';

if( file_exists($root_dir . $_POST['dir']) ) {
	if (function_exists('scandir')) {
		$files = scandir($root_dir . $_POST['dir']);
	} else {
		$files = php4_scandir($root_dir . $_POST['dir']);
	}
	natcasesort($files);
	if( count($files) > 2 ) { 
		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		echo "<li class=\"file\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir']) . "\">&lt;Select this directory&gt;</a></li>";
		// All dirs
		foreach( $files as $file ) {
			if( file_exists($root_dir . $_POST['dir'] . $file) && $file != '.' && $file != '..' && is_dir($root_dir . $_POST['dir'] . $file) ) {
				echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";
			}
		}		
		echo "</ul>";	
	} 
}

?>