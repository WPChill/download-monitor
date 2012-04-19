<?php
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
if(file_exists($wp_root . '/wp-load.php')) {
	require_once($wp_root . "/wp-load.php");
} else if(file_exists($wp_root . '/wp-config.php')) {
	require_once($wp_root . "/wp-config.php");
} else {
	exit;
}

@error_reporting(0);

if (headers_sent()) :
	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
	wp_die(__('Headers Sent',"wp-download_monitor"), __('The headers have been sent by another plugin - there may be a plugin conflict.',"wp-download_monitor"));
endif;

if ( !function_exists('htmlspecialchars_decode') )
{
    function htmlspecialchars_decode($text)
    {
        return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
    }
}

if (!function_exists('readfile_chunked')) {
	function readfile_chunked($filename, $size = '', $retbytes = TRUE) {
		$chunk_size = 1024*1024;
		$buffer = '';
		$cnt =0;

		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}

		//check if http_range is sent by browser (or download manager)
		if(isset($_SERVER['HTTP_RANGE']) && $size>0) {
			list($a, $range)=explode("=",$_SERVER['HTTP_RANGE']);
			str_replace($range, "-", $range);
			
			$size2=$size-1;
			$new_length=$size2-$range;
			
			header("Accept-Ranges: bytes");
			header("HTTP/1.1 206 Partial Content");
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range$size2/$size");
			
			fseek($fp,$range);
		}	

		while (!feof($handle) && connection_status()==0) {
			@set_time_limit(0);
			$buffer = fread($handle, $chunk_size);
			echo $buffer;
			ob_flush();
			flush();
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt;
		}
		return $status;
	}
}

if (!function_exists('remote_filesize')) {
	function remote_filesize($url)
	{
		ob_start();
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	 
		$ok = curl_exec($ch);
		curl_close($ch);
		$head = ob_get_contents();
		@ob_end_clean();
	 
		$regex = '/Content-Length:\s([0-9].+?)\s/';
		$count = preg_match($regex, $head, $matches);
	 
		return isset($matches[1]) ? $matches[1] : "";
	}
}

load_plugin_textdomain('wp-download_monitor', false, 'download-monitor/languages/');

do_action('wp_dlm_download');

	global $wpdb,$user_ID;	
	
	// set table name	
	$wp_dlm_db = $wpdb->prefix."download_monitor_files";
	$wp_dlm_db_stats = $wpdb->prefix."download_monitor_stats";
	$wp_dlm_db_log = $wpdb->prefix."download_monitor_log";
	$level = '';
	
	$id = stripslashes($_GET['id']);
	if ($id) {
		//type of link
		$downloadtype = get_option('wp_dlm_type');	
		// Check passed data is safe
		$go=false;
		switch ($downloadtype) {
			case ("Title") :
				$id=urldecode($id);
				$go=true;
			break;
			case ("Filename") :
				$id=urldecode($id);
				$go=true;
			break;
			default :
				if (is_numeric($id) && $id>0) $go=true;
			break;
		}
	}
	if (isset($id) && $go==true) {		
		switch ($downloadtype) {
					case ("Title") :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE title='%s';", $id );
					break;
					case ("Filename") :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE filename LIKE '%s' ORDER BY LENGTH(filename) ASC LIMIT 1;", "%".$id );
					break;
					default :
							// select a download
							$query_select_1 = $wpdb->prepare( "SELECT * FROM $wp_dlm_db WHERE id=%s;" , $id );
					break;
		}	

		$d = $wpdb->get_row($query_select_1);
		if (!empty($d) && is_numeric($d->id) ) {
					
				if (isset($user_ID) && $user_ID > 0) {
				
					$theroles = array();
				
					$user = new WP_User( $user_ID );					
					if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
						foreach ( $user->roles as $role )
							$theroles[] = $role;
					}
					
					$level = $user->user_level;
				}

				// Check permissions
				if (($d->members || get_option('wp_dlm_global_member_only')=='yes') && (!isset($user_ID) || $user_ID == 0) ) {
					$url = get_option('wp_dlm_member_only');
					$url = str_replace('{referrer}',urlencode($_SERVER['REQUEST_URI']),$url);
					if (!empty($url)) {
						$url = 'Location: '.$url;
						header( $url );
						exit();
   					} else {
   						@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
   						wp_die( sprintf(__('You must be logged in to download this file. <br/><br/><a href="%1$s"><strong>← Back to %2$s</strong></a>', "wp-download_monitor"), get_bloginfo('url'), get_bloginfo('name')), __('You must be logged in to download this file.',"wp-download_monitor"));
   					}
					exit();
				}
								
				// Min-level/req-role addon
				if ($d->members && isset($user_ID) && $user_ID > 0) {
					$minLevel = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='min-level' LIMIT 1" , $d->id ) );
					if ($minLevel) {
						if ($level < $minLevel) {
							@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
		   					wp_die(__('You do not have permission to download this file.',"wp-download_monitor"),__('You do not have permission to download this file.',"wp-download_monitor"));
							exit();
						}
					}
					$reqRole = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='req-role' LIMIT 1" , $d->id ) );
					if ($reqRole) {
						$roles = explode(',', $reqRole);
						$roles = array_map('trim', $roles);
						if (	sizeof(array_intersect($roles, $theroles))	== 0	) {
							@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
								wp_die(__('You do not have permission to download this file.',"wp-download_monitor"),__('You do not have permission to download this file.',"wp-download_monitor"));
							exit();
						}
					}
				}
				
				$log_timeout = (int) get_option('wp_dlm_log_timeout');
				$blacklist = array_map("trim", explode("\n", get_option('wp_dlm_ip_blacklist')));
				$dupe = false;
				$blocked = false;
				$ipAddress = '';
				
				if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strtolower($_SERVER['HTTP_X_FORWARDED_FOR'])!='unknown') {
					$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
				} elseif(isset($_SERVER['HTTP_X_REAL_IP']) && strtolower($_SERVER['HTTP_X_REAL_IP'])!='unknown') {
					$ipAddress = $_SERVER['HTTP_X_REAL_IP'];
				} else {
					$ipAddress = $_SERVER['REMOTE_ADDR'];
				}
				if (in_array($ipAddress, $blacklist)) $blocked = true;
				
				if (get_option('wp_dlm_log_downloads')=='yes' && $log_timeout>0) {					
					$old_date = $wpdb->get_var( $wpdb->prepare( "SELECT date FROM $wp_dlm_db_log WHERE ip_address = '$ipAddress' AND download_id = ".$d->id." ORDER BY date DESC limit 1;") );
					if ($old_date) {
						$old_date = strtotime($old_date);
						$old_date = strtotime('+'.$log_timeout.' MIN', $old_date);
						$timestamp = current_time('timestamp', 1);						
						if ($timestamp < $old_date) {
							$dupe = true;
						}
					}
				}
				
				if ($dupe==false && $blocked==false && $level!=10) {
					$hits = $d->hits;
					$hits++;
					// update download hits					
					$wpdb->query( $wpdb->prepare( "UPDATE $wp_dlm_db SET hits=%s WHERE id=%s;", $hits, $d->id ) );
					
					// Record date/hits for stats purposes
					$today = date("Y-m-d");
					
					// Check database for date
					$hits = $wpdb->get_var( $wpdb->prepare("SELECT hits FROM $wp_dlm_db_stats WHERE date='%s' AND download_id=%s;", $today, $d->id ) );
					
					if($hits<1) {
						// Insert hits
						$wpdb->query( $wpdb->prepare( "INSERT INTO $wp_dlm_db_stats (download_id,date,hits) VALUES (%s,'%s',%s);", $d->id, $today, 1 ) );
					} else {
						// Update hits
						$wpdb->query( $wpdb->prepare( "UPDATE $wp_dlm_db_stats SET hits=%s WHERE date='%s' AND download_id=%s;", $hits+1, $today, $d->id ) );
					}	
				
			   }
			   
		   		// Log download details
		   		if ($dupe==false && $blocked==false && get_option('wp_dlm_log_downloads')=='yes') {
					$timestamp = current_time('timestamp', 1);					
					$user = $user_ID;
					if (empty($user)) $user = '0';			
					$wpdb->query( $wpdb->prepare( "INSERT INTO $wp_dlm_db_log (download_id, user_id, date, ip_address) VALUES (%s, %s, %s, %s);", $d->id, $user, date("Y-m-d H:i:s" ,$timestamp), $ipAddress ) );
				}
			   
			   // Select a mirror
			   $mirrors = trim($d->mirrors);
			   if (!empty($mirrors) && get_option('wp_dlm_auto_mirror')!=='no') {			   
			   
			   		$mirrors = explode("\n",$mirrors);
			   		array_push($mirrors,$d->filename);
			   		$mirrorcount = sizeof($mirrors)-1;
			   		$thefile = trim($mirrors[rand(0,$mirrorcount)]);

			   		// Check random mirror is OK or choose another
			   		$checking=true;
			   		$loop = 0;
			   		include_once('classes/linkValidator.class.php');
			   		$linkValidator = new linkValidator();
			   		while ($checking) { 						
						$linkValidator->linkValidator($thefile, true, false);

						if (!$linkValidator->status()) {
						
							// Failed - use another mirror
							if ($loop<=$mirrorcount) {
								$thefile = trim($mirrors[$loop]);
								$loop++;
							} else {
								// All broken
								$thefile = $d->filename;
								$checking = false;
							}
						
						} else {
							$checking = false;
						}

					}
					// Do we have a link?		
					if (strlen($thefile)<4) $thefile = $d->filename;			   		
			   			   		
			   } else {
			   		$thefile = $d->filename;
			   };
			   $thefile = htmlspecialchars_decode($thefile); // Fix for dodgy chars etc.
			   
			   /* Do action - should allow custom functions to allow/disallow the download */
			   do_action('download_ready_to_start', $d);
			   

				// NEW - Member only downloads should be forced to download so real URL is not revealed - NOW OPTIONAL DUE TO SOME SERVER CONFIGS
				
				/*  Ok, new logic. So we want to only force downloads if its member only and force is not set to 0. Ok so far.
					But as we know, remotly hosted files can be a bitch to force download without corruption SO heres what I want to do:
					
						Forced = 0 - DONT FORCE
						Member Only and Forced Not Set or Set to 1 - FORCE
						Normal Download and Forced Not Set or set to 0 =  DONT Force
				*/
				
				$force = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wp_dlm_db_meta WHERE download_id = %s AND meta_name='force' LIMIT 1" , $d->id ) );
				
				if (isset($force)) {} else {
					if ($d->members) $force=1; else $force=0;
				}		
				
				// Ensure relative urls are forced if outside of the wordpress installation!!!!
				if ($force==0) {
					$urlparsed = parse_url($thefile);
					$isURI = array_key_exists('scheme', $urlparsed);
					$localToWordPress = (bool) strstr($thefile, ABSPATH);
					if (!$isURI && !$localToWordPress) {
						// This download is outside of wordpress and is not a URL, it’s a file path => force it
						$force = 1;
					}
				}
				
				if ($force==1) {				

					$filename = basename($thefile);
					$file_extension = strtolower(substr(strrchr($filename,"."),1));					

					// This will set the Content-Type to the appropriate setting for the file
					// Extended list provided by Jim Isaacs (jidd.jimisaacs.com)
					switch( $file_extension ) {
						case "m4r": 	$ctype="audio/Ringtone"; 				break;
						case "gz": 		$ctype="application/x-gzip"; 			break;
						case "rar": 	$ctype="application/zip"; 				break;	
						case "xls": 	$ctype="application/vnd.ms-excel";		break;
						case "djvu":	$ctype="image/x.djvu";					break;		
						case "ez":		$ctype="application/andrew-inset";		break; 
						case "hqx":		$ctype="application/mac-binhex40";		break; 
						case "cpt":		$ctype="application/mac-compactpro";	break; 
						case "doc":		$ctype="application/msword";			break; 
						case "oda":		$ctype="application/oda";				break; 
						case "pdf":		$ctype="application/pdf";				break; 
						case "ai":		$ctype="application/postscript";		break; 
						case "eps":		$ctype="application/postscript";		break; 
						case "ps":		$ctype="application/postscript";		break; 
						case "smi":		$ctype="application/smil";				break; 
						case "smil":	$ctype="application/smil";				break; 
						case "wbxml":	$ctype="application/vnd.wap.wbxml";		break; 
						case "wmlc":	$ctype="application/vnd.wap.wmlc";		break; 
						case "wmlsc":	$ctype="application/vnd.wap.wmlscriptc";break; 
						case "bcpio":	$ctype="application/x-bcpio";			break; 
						case "vcd":		$ctype="application/x-cdlink";			break; 
						case "pgn":		$ctype="application/x-chess-pgn";		break; 
						case "cpio":	$ctype="application/x-cpio";			break; 
						case "csh":		$ctype="application/x-csh";				break; 
						case "dcr":		$ctype="application/x-director";		break; 
						case "dir":		$ctype="application/x-director";		break; 
						case "dxr":		$ctype="application/x-director";		break; 
						case "dvi":		$ctype="application/x-dvi";				break; 
						case "spl":		$ctype="application/x-futuresplash";	break; 
						case "gtar":	$ctype="application/x-gtar";			break; 
						case "hdf":		$ctype="application/x-hdf";				break; 
						case "js":		$ctype="application/x-javascript";		break; 
						case "skp":		$ctype="application/x-koan";			break; 
						case "skd":		$ctype="application/x-koan";			break; 
						case "skt":		$ctype="application/x-koan";			break; 
						case "skm":		$ctype="application/x-koan";			break; 
						case "latex":	$ctype="application/x-latex";			break; 
						case "nc":		$ctype="application/x-netcdf";			break; 
						case "cdf":		$ctype="application/x-netcdf";			break; 
						case "sh":		$ctype="application/x-sh";				break; 
						case "shar":	$ctype="application/x-shar";			break; 
						case "swf":		$ctype="application/x-shockwave-flash";	break; 
						case "sit":		$ctype="application/x-stuffit";			break; 
						case "sv4cpio":	$ctype="application/x-sv4cpio";			break; 
						case "sv4crc":	$ctype="application/x-sv4crc";			break; 
						case "tar":		$ctype="application/x-tar";				break; 
						case "tcl":		$ctype="application/x-tcl";				break; 
						case "tex":		$ctype="application/x-tex";				break; 
						case "texinfo":	$ctype="application/x-texinfo";			break; 
						case "texi":	$ctype="application/x-texinfo";			break; 
						case "t":		$ctype="application/x-troff";			break; 
						case "tr":		$ctype="application/x-troff";			break; 
						case "roff":	$ctype="application/x-troff";			break; 
						case "man":		$ctype="application/x-troff-man";		break; 
						case "me":		$ctype="application/x-troff-me";		break; 
						case "ms":		$ctype="application/x-troff-ms";		break; 
						case "ustar":	$ctype="application/x-ustar";			break; 
						case "src":		$ctype="application/x-wais-source";		break;  
						case "au":		$ctype="audio/basic";					break; 
						case "snd":		$ctype="audio/basic";					break; 
						case "mid":		$ctype="audio/midi";					break; 
						case "midi":	$ctype="audio/midi";					break; 
						case "kar":		$ctype="audio/midi";					break; 
						case "mpga":	$ctype="audio/mpeg";					break; 
						case "mp2":		$ctype="audio/mpeg";					break; 
						case "mp3":		$ctype="audio/mpeg";					break; 
						case "aif":		$ctype="audio/x-aiff";					break; 
						case "aiff":	$ctype="audio/x-aiff";					break; 
						case "aifc":	$ctype="audio/x-aiff";					break; 
						case "m3u":		$ctype="audio/x-mpegurl";				break; 
						case "ram":		$ctype="audio/x-pn-realaudio";			break; 
						case "rm":		$ctype="audio/x-pn-realaudio";			break; 
						case "rpm":		$ctype="audio/x-pn-realaudio-plugin";	break; 
						case "ra":		$ctype="audio/x-realaudio";				break; 
						case "wav":		$ctype="audio/x-wav";					break; 
						case "pdb":		$ctype="chemical/x-pdb";				break; 
						case "xyz":		$ctype="chemical/x-xyz";				break; 
						case "bmp":		$ctype="image/bmp";						break; 
						case "gif":		$ctype="image/gif";						break; 
						case "ief":		$ctype="image/ief";						break; 
						case "jpeg":	$ctype="image/jpeg";					break; 
						case "jpg":		$ctype="image/jpeg";					break; 
						case "jpe":		$ctype="image/jpeg";					break; 
						case "png":		$ctype="image/png";						break; 
						case "tiff":	$ctype="image/tiff";					break; 
						case "tif":		$ctype="image/tif";						break;  
						case "djv":		$ctype="image/vnd.djvu";				break; 
						case "wbmp":	$ctype="image/vnd.wap.wbmp";			break; 
						case "ras":		$ctype="image/x-cmu-raster";			break; 
						case "pnm":		$ctype="image/x-portable-anymap";		break; 
						case "pbm":		$ctype="image/x-portable-bitmap";		break; 
						case "pgm":		$ctype="image/x-portable-graymap";		break; 
						case "ppm":		$ctype="image/x-portable-pixmap";		break; 
						case "rgb":		$ctype="image/x-rgb";					break; 
						case "xbm":		$ctype="image/x-xbitmap";				break; 
						case "xpm":		$ctype="image/x-xpixmap";				break; 
						case "xwd":		$ctype="image/x-windowdump";			break; 
						case "igs":		$ctype="model/iges";					break; 
						case "iges":	$ctype="model/iges";					break; 
						case "msh":		$ctype="model/mesh";					break; 
						case "mesh":	$ctype="model/mesh";					break; 
						case "silo":	$ctype="model/mesh";					break; 
						case "wrl":		$ctype="model/vrml";					break; 
						case "vrml":	$ctype="model/vrml";					break;
						case "as":		$ctype="text/x-actionscript";			break; 
						case "css":		$ctype="text/css";						break; 
						case "asc":		$ctype="text/plain";					break; 
						case "txt":		$ctype="text/plain";					break; 
						case "rtx":		$ctype="text/richtext";					break; 
						case "rtf":		$ctype="text/rtf";						break; 
						case "sgml":	$ctype="text/sgml";						break; 
						case "sgm":		$ctype="text/sgml";						break; 
						case "tsv":		$ctype="text/tab-seperated-values";		break; 
						case "wml":		$ctype="text/vnd.wap.wml";				break; 
						case "wmls":	$ctype="text/vnd.wap.wmlscript";		break; 
						case "etx":		$ctype="text/x-setext";					break; 
						case "xml":		$ctype="text/xml";						break; 
						case "xsl":		$ctype="text/xml";						break; 
						case "mpeg":	$ctype="video/mpeg";					break; 
						case "mpg":		$ctype="video/mpeg";					break; 
						case "mpe":		$ctype="video/mpeg";					break; 
						case "qt":		$ctype="video/quicktime";				break; 
						case "mov":		$ctype="video/quicktime";				break; 
						case "mxu":		$ctype="video/vnd.mpegurl";				break; 
						case "avi":		$ctype="video/x-msvideo";				break; 
						case "movie":	$ctype="video/x-sgi-movie";				break; 
						case "ice":		$ctype="x-conference-xcooltalk" ;		break;						
						case "jad":		$ctype="text/vnd.sun.j2me.app-descriptor" ;		break;
						case "cod":		$ctype="application/vnd.rim.cod" ;		break;
						case "mp4":		$ctype="video/mp4" ;					break;
						//The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files) - if you want to serve these types of files just zip then or give them another extension! This is mainly to protect users who don't know what they are doing :)
						case "php":
						case "htm":
						case "htaccess":
						case "sql":
						case "html":
							$thefile = str_replace(ABSPATH, get_bloginfo('wpurl'));
							$thefile = str_replace($_SERVER['DOCUMENT_ROOT'], get_bloginfo('url'));
						
							$location= 'Location: '.$thefile;
							header($location);
							exit;
						break;						
						default: 		$ctype="application/octet-stream";
					}						

					@ini_set('zlib.output_compression', 'Off');
					@set_time_limit(0);
					@session_start();					
					@session_cache_limiter('none');		
					@set_magic_quotes_runtime(0);			
					
					// START jidd.jimisaacs.com
					$urlparsed = parse_url($thefile);
					$isURI = array_key_exists('scheme', $urlparsed);
					$localURI = (bool) strstr($thefile, get_bloginfo('wpurl')); /* Local TO WORDPRESS!! */
							
					/* Debug
					echo "
						URL Parsed: $urlparsed\n
						isUrl:		$isURI\n
						localURL:	$localURI\n
						BloginfoURL: ".get_bloginfo('url')."\n
						BloginfoWPURL:	".get_bloginfo('wpurl')."\n
						ABSPATH: ".ABSPATH." 
					";
					exit; */
					
					
					// Deal with remote file or local file					
					if( $isURI && $localURI || !$isURI && !$localURI ) {
						
						/* Had some problems with strange wordpress setups/files on server but on within wordpress installation SO lets try this:
							Does the download contain the wpurl or url? */
						if( $localURI ) {
							// the URI is local, replace the WordPress url OR blog url with WordPress's absolute path.
							//$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|', '|^'. get_bloginfo('url') . '/' . '|');
							$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|');
							$path = preg_replace( $patterns, '', $thefile );
							
							// this is joining the ABSPATH constant, changing any slashes to local filesystem slashes, and then finally getting the real path.
							$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join( ABSPATH, $path ) );
													
						// Local File System path
						} else if( !path_is_absolute( $thefile ) ) { 
							//$thefile = path_join( ABSPATH, $thefile );
							// Get the absolute path
							if ( ! isset($_SERVER['DOCUMENT_ROOT'] ) ) $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF']) ) );
							
							// Now substitute the domain for the absolute path in the file url
							$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join( $_SERVER['DOCUMENT_ROOT'], $thefile ));
							
						}
						// If the path wasn't a URI and not absolute, then it made it all the way to here without manipulation, so now we do this...
						// By the way, realpath() returns NOTHING if is does not exist.
						$testfile = realpath( $thefile );
						
						// now do a long condition check, it should not be emtpy, a directory, and should be readable.
						$willDownload = empty($testfile) ? false : !is_file($testfile) ? false : is_readable($testfile);

						if ( !$willDownload ) {	
							// Prefix with abspath and try again, just in case this is from an old version of download monitor
							$thefile = realpath( ABSPATH . $thefile );
							$willDownload = empty($thefile) ? false : !is_file($thefile) ? false : is_readable($thefile);
						} else {
							$thefile = realpath( $thefile );
						}
						
						if ( $willDownload ) {							
						// END jidd.jimisaacs.com	
							@ob_end_clean();
							@session_write_close();
											
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Cache-Control: public");
							header("Robots: none");
							header("Content-Type: ".$ctype."");
							header("Content-Description: File Transfer");						
							
							if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
							    // workaround for IE filename bug with multiple periods / multiple dots in filename
							    $iefilename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
							    header("Content-Disposition: attachment; filename=\"".$iefilename."\";");
							} else {
							    header("Content-Disposition: attachment; filename=\"".$filename."\";");
							}
							
							header("Content-Transfer-Encoding: binary");
							$size = @filesize($thefile);
							if (isset($size) && $size>0) {						
								header("Content-Length: ".$size);
								@readfile_chunked($thefile, $size);
							} else {
								readfile($thefile);
							}
							exit;
						}			
					// START jidd.jimisaacs.com
					// this is only for remote URI's
					} elseif ( $isURI && ini_get('allow_url_fopen')  ) {
					// END jidd.jimisaacs.com
						// Remote File
						@ob_end_clean();
						@session_write_close();
						
						// Are we going to be able to load this beatch?
						$handle = @fopen($thefile, 'rb');
						if ($handle === false) {
							// Cannot open the file
							@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
							wp_die(__('Cannot open remote file ',"wp-download_monitor").'"'.trim(basename($thefile)).'"', __('Cannot open remote file ',"wp-download_monitor").'"'.trim(basename($thefile)).'"');
						}
						@fclose($handle);
									
						// Get filesize
						$filesize = 0;
						$header_filename = '';
						if (function_exists('get_headers')) {
							// php5 method
							$ary_header = @get_headers($thefile, 1);   
							if (is_array($ary_header) && (array_key_exists("Content-Length", $ary_header))) 
								$filesize = $ary_header["Content-Length"];
							if (is_array($ary_header) && (array_key_exists("Content-Disposition", $ary_header))) 
								$header_filename = $ary_header["Content-Disposition"];
						} else if (function_exists('curl_init')) {
							// Curl Method
							$filesize = remote_filesize($thefile);
						} else {
							$filesize = @filesize($thefile);
						}
						if (isset($filesize) && $filesize > 0) {						
							header("Content-Length: ".$filesize);
						}
						
						header("Pragma: public");
						header("Expires: 0");
						header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
						header("Cache-Control: public");
						header("Robots: none");
						header("Content-Type: ".$ctype."");
						header("Content-Description: File Transfer");						
						header("Content-Transfer-Encoding: binary");
						
						if (isset($header_filename) && !empty($header_filename)) {							
							header("Content-Disposition: ".$header_filename.";");
						} else {
							if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
							    $iefilename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
							    header("Content-Disposition: attachment; filename=\"".$iefilename."\";");
							} else {
							    header("Content-Disposition: attachment; filename=\"".$filename."\";");
							}							
						}	
						if (isset($filesize) && $filesize > 0) {					
							@readfile_chunked($thefile, $filesize);
						} else {
							readfile($thefile);
						}
						exit;
					} elseif ( $isURI && !ini_get('allow_url_fopen')) {
						
						// O dear, we cannot force the remote file without allow_url_fopen
						@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
						wp_die(__('Forcing the download of externally hosted files is not supported by this server.',"wp-download_monitor"), __('Forcing the download of externally hosted files is not supported by this server.',"wp-download_monitor"));
						
					}
					
					// If we have not exited by now, the only thing left to do is die.
					// We cannot download something that is a local file system path on another system, and that's the only thing left it could be!
					@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
					wp_die(__('Download path is invalid!',"wp-download_monitor"), __('Download path is invalid!',"wp-download_monitor"));
							
				}

				if( !strstr($thefile, 'http://') && !strstr($thefile,'https://') && !strstr($thefile, 'ftp://') ) { 
				
					// Non forced, absolute path within wordpress install directory
					$pageURL = get_bloginfo('wpurl');

					$thefile = str_replace( ABSPATH, trailingslashit($pageURL), $thefile );
					
					// If that didn't work then the file is obviously outside wordpress so we have no choice but to use DOCUMENT ROOT
					if( !strstr($thefile, 'http://') && !strstr($thefile,'https://') && !strstr($thefile, 'ftp://') ) { 
						if ( ! isset($_SERVER['DOCUMENT_ROOT'] ) ) 
							$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF']) ) );
						$dir_path = $_SERVER['DOCUMENT_ROOT'];
						$thefile = str_replace( $dir_path, $pageURL, $thefile );
					}
				}
				$location= 'Location: '.$thefile;
				header($location);
        	    exit;					
		}
   }
   $url = get_option('wp_dlm_does_not_exist');
   if (!empty($url)) {
   		$url = 'Location: '.$url;
		header( $url );
		exit();
   } else {
   	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
   	wp_die(__('Download does not exist!',"wp-download_monitor"), __('Download does not exist!',"wp-download_monitor"));
   }

   exit();