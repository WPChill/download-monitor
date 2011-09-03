<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - downloadable_file CLASS
*/
class downloadable_file {
	var $id;
	var $filename;
	var $title;
	var $user;
	var $version;
	var $dlversion;
	var $hits;
	var $file_description;
	var $desc;
	var $mirrors;
	var $postDate;
	var $date;
	var $members;
	var $memberonly;
	var $url;
	var $size;		
	var $tags;
	var $thumbnail;
	var $meta;
	var $image;	
	var $categories;
	var $category;
	var $category_id;	
	var $patts;
	var $subs;
	
	function downloadable_file($d = '', $format = '') {
		$this->init_file($d);
		if ($d && $format) {			
			$this->prep_download_data($format);
		}
	}
	
	function init_file($d) {
		if ($d) {
			global $downloadurl, $downloadtype, $wp_dlm_image_url;
				
			switch ($downloadtype) {
				case ("Title") :
					$downloadlink = urlencode($d->title);
				break;
				case ("Filename") :
					$downloadlink = $d->filename;
					$link = explode("/",$downloadlink);
					$downloadlink = urlencode(end($link));
					$downloadlink = str_replace('%26', '%2526', $downloadlink);
				break;
				default :
					$downloadlink = $d->id;
				break;
			}
			$this->url =  $downloadurl.$downloadlink;
			$this->rawurl = $d->filename; 
			$this->id = $d->id;
			$this->filename = $d->filename;
			$this->title = $d->title;
			$this->user = $d->user;
			$this->version = $d->dlversion;
			$this->dlversion = $d->dlversion;
			$this->hits = $d->hits;
			$this->file_description = $d->file_description;
			$this->desc = $d->file_description;
			$this->mirrors = $d->mirrors;
			$this->postDate = $d->postDate;
			$this->date = $d->postDate;
			$this->members = $d->members;
			$this->memberonly = $d->members;			
			$this->get_taxonomy();
			$this->get_meta();
			if (!isset($this->size)) $this->get_size();
			$this->image = $wp_dlm_image_url;
		}	
	}
	
	function get_taxonomy() {
		global $download2taxonomy_array, $download_taxonomies;
		
		$download_cats = array();
		$download_tags = array();
		$this_download2taxonomy = '';
		
		if (isset($download2taxonomy_array[$this->id])) $this_download2taxonomy = $download2taxonomy_array[$this->id];
		
		if ($this_download2taxonomy && sizeof($this_download2taxonomy)>0) :
			foreach ($this_download2taxonomy as $taxonomy_id) :
				
				if (isset($download_taxonomies->categories[$taxonomy_id])) {
					$download_cats[] = array(
						'name' => $download_taxonomies->categories[$taxonomy_id]->name,
						'id' => $download_taxonomies->categories[$taxonomy_id]->id,
						'parent' => $download_taxonomies->categories[$taxonomy_id]->parent,
					);
				} elseif (isset($download_taxonomies->tags[$taxonomy_id])) {
					$download_tags[] = array(
						'name' => $download_taxonomies->tags[$taxonomy_id]->name,
						'id' => $download_taxonomies->tags[$taxonomy_id]->id,
						'parent' => $download_taxonomies->tags[$taxonomy_id]->parent,
					);
				}
				
				
			endforeach;
		endif;

		$this->tags = $download_tags;
		$this->categories = $download_cats;
		$firstcat = current($download_cats);
		$this->category = $firstcat['name'];
		$this->category_id = $firstcat['id'];
	}
	
	function get_meta() {
		global $wp_dlm_root, $download_meta_data_array;
		
		$this_meta = array();
		
		if (isset($download_meta_data_array[$this->id])) :

			$this_meta = $download_meta_data_array[$this->id];

		endif;

		if (isset($this_meta['thumbnail'])) $this->thumbnail = $this_meta['thumbnail']; else $this->thumbnail = $wp_dlm_root.'page-addon/thumbnail.gif';
		if (isset($this_meta['filesize'])) $this->size = $this_meta['filesize'];
		$this->meta = $this_meta;
	}
	
	function get_size() {
		global $wpdb, $wp_dlm_db_meta;
	
		$thefile = $this->filename;
		$urlparsed = parse_url($thefile);
		$isURI = array_key_exists('scheme', $urlparsed);
		$localURI = (bool) strstr($thefile, get_bloginfo('wpurl')); /* Local TO WORDPRESS!! */
		
		$filesize = '';
		
		if( $isURI && $localURI || !$isURI && !$localURI ) {
					
			if( $localURI ) {
				// the URI is local, replace the WordPress url OR blog url with WordPress's absolute path.
				//$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|', '|^'. get_bloginfo('url') . '/' . '|');
				$patterns = array( '|^'. get_bloginfo('wpurl') . '/' . '|');
				$path = preg_replace( $patterns, '', $thefile );
				// this is joining the ABSPATH constant, changing any slashes to local filesystem slashes, and then finally getting the real path.
				$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join( ABSPATH, $path ) );
				
				if (@file_exists($thefile)) {
					$filesize = filesize($thefile);
				}					
			// Local File System path
			} elseif( !path_is_absolute( $thefile ) ) { 
				//$thefile = path_join( ABSPATH, $thefile );
				// Get the absolute path
				if ( ! isset($_SERVER['DOCUMENT_ROOT'] ) ) $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF']) ) );
				$dir_path = $_SERVER['DOCUMENT_ROOT'];
				// Now substitute the domain for the absolute path in the file url
				$thefile = str_replace( '/', DIRECTORY_SEPARATOR, path_join($dir_path, $thefile ));
				
				if (@file_exists($thefile)) {
					$filesize = filesize($thefile);
				}
			} else {
				if (@file_exists($thefile)) {
					$filesize = filesize($thefile);
				}
			}		
		} elseif ( $isURI && ini_get('allow_url_fopen')  ) {
			// Absolute path outside of wordpress
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
					ob_end_clean();
				 
					$regex = '/Content-Length:\s([0-9].+?)\s/';
					$count = preg_match($regex, $head, $matches);
				 
					return isset($matches[1]) ? $matches[1] : "";
				}
			}
			$isHTTP = (bool) ($urlparsed['scheme'] == 'http' || $urlparsed['scheme'] == 'https');
			if (function_exists('get_headers') && $isHTTP) {
				$ary_header = @get_headers($thefile, 1);   
				if (is_array($ary_header) && (array_key_exists("Content-Length", $ary_header))) {
					$filesize = $ary_header["Content-Length"];
				}
			} else if (function_exists('curl_init')) {
				$filesize = remote_filesize($thefile); // I wonder, is this returning something non-numeric?
			} else {
				$filesize = @filesize($thefile);
			}
		}
						
		
		if ($filesize && is_numeric($filesize)) {
			$bytes = array('bytes','kB','MB','GB','TB');
			foreach($bytes as $val) {
				if($filesize > 1024){
					$filesize = $filesize / 1024;
			    } else {
					break;
			   	}
			}
			$this->size = round($filesize, 2)." ".$val;
			// Add to DB for quick loading in future
			$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('filesize', '".$wpdb->escape( $this->size )."', '".$this->id."')");
		} else {
			// Could not get size, but insert anyway to prevent slow page loads
			$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('filesize', '', '".$this->id."')");
		}
	}
	
	function prep_download_data($format) {
		
		global $wp_dlm_image_url, $wp_dlm_db_meta, $download_taxonomies, $meta_blank;
			
		$fpatts = array(
			'{url}', 
			'{raw_url}', 
			'{id}', 
			'{user}', 
			'{version}', 
			'{title}', 
			'{size}', 
			'{hits}', 
			'{image_url}', 
			'{description}', 
			'{description-autop}', 
			'{category}', 
			'{category_other}'
		);
		
		$fsubs = array( 
			$this->url, 
			$this->rawurl,
			$this->id,
			$this->user, 
			$this->version, 
			$this->title, 
			$this->size, 
			$this->hits , 
			$wp_dlm_image_url, 
			$this->file_description , 
			wpautop($this->file_description) 
		);
		
		// Category (single cat uses first - this is for compatibility with the old system)
		if ($this->category_id>0) {			
			$fsubs[]  = $this->category; /* category */
			$fsubs[]  = $this->category; /* category_other */
			$fpatts[] = '{category_ID}';
			$fsubs[] = $this->category_id;
			if (strpos($format, '{category,')!==false) :
				preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				if ($match) {
					$fpatts[] = $match[0];
					$fsubs[]  = $match[1].$this->category.$match[2];
				}
			endif;
			if (strpos($format, '{category_other,')!==false) :
				preg_match("/{category_other,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				if ($match) {
					$fpatts[] = $match[0];
					$fsubs[]  = $match[1].$this->category.$match[2];
				}
			endif;			
		} else {
			$fsubs[]  = "";
			$fsubs[]  = __('Other','wp-download_monitor');
			$fpatts[] = '{category_ID}';
			$fsubs[] = "";
			if (strpos($format, '{category,')!==false) :
				preg_match("/{category,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				if ($match) {
					$fpatts[] = $match[0];
					$fsubs[]  = "";
				}
			endif;
			if (strpos($format, '{category_other,')!==false) :
				preg_match("/{category_other,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
				if ($match) {
					$fpatts[] = $match[0];
					$fsubs[]  = $match[1].__('Other','wp-download_monitor').$match[2];
				}
			endif;			
		}
		
		// Categories (multiple)		
		$fpatts[] = "{categories}";
		$cats = array();
		if (!$this->categories) $cats[] = __('Uncategorized',"wp-download_monitor");
		else {
			foreach ($this->categories as $cat) {
				$cats[] = $cat['name'];
			}
		}
		$fsubs[] = implode(', ', $cats);
		
		// Categories (linked)
		if (strpos($format, '{categories,')!==false) :
			preg_match("/{categories,\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$cats = array();
				if (!$this->categories) $cats[] = '<a href="'.str_replace('%',0,str_replace('%2',urlencode(strtolower(__('Other',"wp-download_monitor"))),$match[1])).'" class="cat-link">'.__('Other',"wp-download_monitor").'</a>';
				else {
					foreach ($this->categories as $cat) {
						$cats[] = '<a href="'.str_replace('%',$cat['id'],str_replace('%2',urlencode(strtolower($cat['name'])),$match[1])).'" class="cat-link">'.$cat['name'].'</a>';
					}
				}
				$fsubs[] = implode(', ', $cats);
			}
		endif;
		
		// Mirrors
		if (strpos($format, '{mirror-')!==false) :
			preg_match("/{mirror-([0-9]+)-url}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$mirrors = trim($this->mirrors);
				if (!empty($mirrors)) {			   
					$mirrors = explode("\n",$mirrors);
					if (isset($mirrors[($match[1]-1)])) { 
						$fsubs[] = $mirrors[($match[1]-1)];
					} else {
						$fsubs[] = __('#Mirror-not-found',"wp-download_monitor");
					}
				} else {
					$fsubs[] = __('#Mirror-not-found',"wp-download_monitor");
				}
			}
		endif;
		
		// Filetype
		$fpatts[] = "{filetype}";
		$filetype = basename($this->filename);
		$filetype = trim(strtolower(substr(strrchr($filetype,"."),1)));	
		if ($filetype) 
			$fsubs[] = $filetype;	
		else {
			$fsubs[] = __('N/A',"wp-download_monitor");
			$filetype = __('File',"wp-download_monitor");
		}
		
		global $wp_dlm_root;
		
		// Filetype Icons
		$fpatts[] = "{filetype_icon}";
		$icon = '<img alt="'.$filetype.'" title="'.$filetype.'" class="download-icon" src="'.$wp_dlm_root.'img/filetype_icons/';
		switch ($filetype) :
			case "pdf" :
				$icon .= 'document-pdf';
			break;
			case "m4r":
			case "au":
			case "snd":
			case "mid":
			case "midi":
			case "kar":
			case "mpga":
			case "mp2":
			case "mp3":
			case "aif":
			case "aiff":
			case "aifc":
			case "m3u":
			case "ram":
			case "rm":
			case "rpm":
			case "ra":
			case "wav":
				$icon .= 'document-music';
			break;
			case "mpeg": 
			case "mpg":
			case "mpe":
			case "qt":
			case "mov":
			case "mxu":
			case "avi":
			case "movie":	
			case "mp4":		
				$icon .= 'document-film';
			break;
			case "zip":
			case "gz":
			case "rar":
			case "sit":
			case "tar":
				$icon .= 'document-zipper';
			break;
			case "xls":
			case "tsv":	
			case "csv":	
				$icon .= 'document-excel';
			break;
			case "doc":
				$icon .= 'document-word-text';
			break;
			case "ai":
				$icon .= 'document-illustrator';
			break;
			case "swf":
				$icon .= 'document-flash-movie';
			break;			
			case "eps":
			case "ps":
			case "bmp":
			case "gif":	
			case "ief":
			case "jpeg":
			case "jpg":
			case "jpe":
			case "png":
			case "tiff":
			case "tif":
			case "djv":	
			case "wbmp":
			case "ras":
			case "pnm":
			case "pbm":
			case "pgm":
			case "ppm":
			case "rgb":
			case "xbm":
			case "xpm":
			case "xwd":
				$icon .= 'document-image';
			break;
			case "psd" :
				$icon .= 'document-photoshop';
			break;
			case "ppt" :
				$icon .= 'document-powerpoint';
			break;
			case "js":
			case "css":
			case "as":
			case "htm":
			case "htaccess":
			case "sql":
			case "html":			
			case "php":
			case "xml":
			case "xsl":
				$icon .= 'document-code';
			break;
			case "rtx": 
			case "rtf":
				$icon .= 'document-text-image';
			break;
			case "txt":	
				$icon .= 'document-text';
			break;
			default :
				$icon .= 'document';
			break;
		endswitch;
		$icon .= '.png" />';
		$fsubs[] = $icon;
			
		// Hits (special) {hits, none, one, many)
		if (strpos($format, '{hits,')!==false) :
			preg_match("/{hits,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				if ( $this->hits == 1 ) 
				{
					$text = str_replace('%',$this->hits,$match[2]);
					$fsubs[]  = $text; 
				}
				elseif ( $this->hits > 1 ) 
				{
					$text = str_replace('%',$this->hits,$match[3]);
					$fsubs[]  = $text; 
				}
				else 
				{
					$text = str_replace('%',$this->hits,$match[1]);
					$fsubs[]  = $text; 
				}
			}
		endif;			
		
		// Version
		if (strpos($format, '{version,')!==false) :
			preg_match("/{version,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				if ($this->version) $fsubs[]  = $match[1].$this->version.$match[2]; else $fsubs[]  = "";
			}
		endif;
		
		// Date
		if (strpos($format, '{date,')!==false) :
			preg_match("/{date,\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				if ($this->postDate) $fsubs[] = date_i18n($match[1],strtotime($this->postDate)); else $fsubs[]  = "";
			}
		endif;				
		
		// Other
		if (strpos($format, '{description,')!==false) :
			preg_match("/{description,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				if ($this->file_description) $fsubs[]  = $match[1].$this->file_description.$match[2]; else $fsubs[]  = "";
			}
		endif;	
		
		if (strpos($format, '{description-autop,')!==false) :
			preg_match("/{description-autop,\s*\"([^\"]*?)\",\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				if ($this->file_description) $fsubs[]  = $match[1].wpautop($this->file_description).$match[2]; else $fsubs[]  = "";
			}
		endif;	
						
		// tags
		$fpatts[] = "{tags}";
		$tags = array();
		if (!$this->tags) $tags[] = 'Untagged';
		else {
			foreach ($this->tags as $tag) {
				$tags[] = $tag['name'];
			}
		}
		$fsubs[] = implode(', ', $tags);
		
		// Tags (linked)
		if (strpos($format, '{tags,')!==false) :
			preg_match("/{tags,\s*\"([^\"]*?)\"}/", $format, $match);
			if ($match) {
				$fpatts[] = $match[0];
				$tags = array();
				if (!$this->tags) $tags[] = 'Untagged';
				else {
					foreach ($this->tags as $tag) {
						$tags[] = '<a href="'.str_replace('%',$tag['id'],str_replace('%2',urlencode(strtolower($tag['name'])),$match[1])).'" class="tag-link">'.$tag['name'].'</a>';
					}
				}
				$fsubs[] = implode(', ', $tags);
			}
		endif;
		
		// Thumbnail
		$fpatts[] = "{thumbnail}";
		$fsubs[] = $this->thumbnail;
		
		// meta
		if (strpos($format, '{meta-')!==false) :
			if (preg_match("/{meta-([^,]*?)}/", $format, $match)) {					
				$meta_names = array();
				$meta_names[] = "''";
				foreach($this->meta as $meta_name=>$meta_value) {
					$fpatts[] = "{meta-".$meta_name."}";
					$fsubs[] = stripslashes($meta_value);
					$fpatts[] = "{meta-autop-".$meta_name."}";
					$fsubs[] = wpautop(stripslashes($meta_value));
					$meta_names[] = $meta_name;
				}
				// Blank Meta
				foreach($meta_blank as $meta_name) {
					if (!in_array($meta_name, $meta_names)) {
						$fpatts[] = "{meta-".$meta_name."}";
						$fsubs[] = '';
						$fpatts[] = "{meta-autop-".$meta_name."}";
						$fsubs[] = '';
					}
				}
			}
		endif;
	
		$this->patts = $fpatts;				
		$this->subs = $fsubs;
	}	
	
}
	
?>