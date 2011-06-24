<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - LEGACY (non supported) SHORTCODES
	
	Copyright 2010  Michael Jolley  (email : jolley.small.at.googlemail.com)

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
// LEGACY TAGS SUPPORT
################################################################################

function wp_dlm_parse_downloads($data) {
	
	if (substr_count($data,"[download#")) {		
	
		preg_match_all("/\[download#([0-9]+)#format=([0-9]+)\]/", $data, $matches, PREG_SET_ORDER);
		
		if ( sizeof( $matches ) > 0 ) foreach ($matches as $val) {
		
			$code = '[download id="'.$val[1].'" format="'.$val[2].'"]';
			$data = str_replace( $val[0] , $code , $data );
	   		
   		} // End foreach
   		
   		// Handle Non-formatted downloads
   		preg_match_all("/\[download#([0-9]+)/", $data, $matches, PREG_SET_ORDER);
		
		$patts = array();
		$subs = array();
			
		if ( sizeof( $matches ) > 0 ) foreach ($matches as $val) {
					
				$patts[] = "[download#" . $val[1] . "]";
				$subs[] = '[download id="'.$val[1].'"]';
				
				// No hit counter				
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title}</a>';
				
				$patts[] = "[download#" . $val[1] . "#nohits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// URL only
				$format = '{url}';
				$patts[] = "[download#" . $val[1] . "#url]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Description only
				$format = '{description}';	
				$patts[] = "[download#" . $val[1] . "#description]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Description (autop) only
				$format = '{description-autop}';
				$patts[] = "[download#" . $val[1] . "#description_autop]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';	
				
				// Hits only
				$format = '{hits}';;
				$patts[] = "[download#" . $val[1] . "#hits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Image link	
				$format = '<a class="downloadlink dlimg" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" ><img src="{image_url}" alt="'.__("Download","wp-download_monitor").' {title} {version,"'.__("Version","wp-download_monitor").' ",""}" /></a>';				
				$patts[] = "[download#" . $val[1] . "#image]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
				
				// Regular download link WITH filesize
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits}) - {size}</a>';
				$patts[] = "[download#" . $val[1] . "#size]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
								
				// No hit counter + filesize
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").' ",""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({size})</a>';
				$patts[] = "[download#" . $val[1] . "#size#nohits]";
				$subs[] = '[download id="'.$val[1].'" format="'.htmlspecialchars($format).'"]';
		
		} // End foreach
		
		$data = str_replace($patts, $subs, $data);
				
	} // End if [download# found

	
	global $wpdb, $wp_dlm_db, $wp_dlm_db_meta, $wp_dlm_db_taxonomies, $downloadurl, $downloadtype;
	
	// Handle CATEGORIES
	if (substr_count($data,"[download_cat#")) {
		
		$patts = array();
		$subs = array();
		
		preg_match_all("/\[download_cat#([0-9]+)#format=([0-9]+)\]/", $data, $result, PREG_SET_ORDER);

		if ($result) foreach ($result as $val) {

			$format = wp_dlm_get_custom_format($val[2]);
			
			if ($format) {
			
				$format = str_replace('\\"',"'",$format);
			
				$args = array(
					'orderby' => 'title',
					'category' => $val[1],
					'digforcats' => 'true'
				);
				$downloads = get_downloads($args);
				
				// GENERATE LIST
				$links = '<ul>';	
				
				if (!empty($downloads)) {
				
					foreach($downloads as $d) {
					
						$this_download = new downloadable_file($d, $format);
				
						$fpatts = $this_download->patts;
				
						$fsubs	= $this_download->subs;
						
						$code = str_replace( $fpatts , $fsubs , $format );	
						
						$links.= '<li>' . $code . '</li>';
						
					}
					
				} else {
					
					$links .= '<li>No Downloads Found</li>';
				
				}
				
				$links .= '</ul>';
				
				$patts[] = "[download_cat#" . $val[1] . "#format=" . $val[2] . "]";
				
				$subs[] = $links;		

	   		} // End if format
	   				
		} // End foreach
		
		$data = str_replace($patts, $subs, $data);
		
		$patts = array();
		$subs = array();
		
		preg_match_all("|\[download_cat#([0-9]+)\]|U",$data,$result,PREG_SET_ORDER);
		
		if ($result) foreach ($result as $val) {
		
			$args = array(
				'orderby' => 'title',
				'category' => $val[1],
				'digforcats' => 'true',
			);
			$downloads = get_downloads($args);
			
			// GENERATE LIST
			$links = '<ul>';	
			if (!empty($downloads)) {
				foreach($downloads as $d) {
					if (!empty($d->dlversion)) 				
						$links.= '<li><a href="'.$d->url.'" title="'.__("Version","wp-download_monitor").' '.$d->dlversion.' '.__("downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
					else $links.= '<li><a href="'.$d->url.'" title="'.__("Downloaded","wp-download_monitor").' '.$d->hits.' '.__("times","wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';									
					
				}
			} else {
				$links .= '<li>No Downloads Found</li>';
			}
			$links .= '</ul>';
			$patts[] = "[download_cat#" . $val[1] . "]";
			$subs[] = $links;
			
		} // endforeach
		
		$data = str_replace($patts, $subs, $data);
	
	} // End if [download_cat# found
	
	return $data;		

} 

################################################################################
// LEGACY TEMPLATE TAGS
################################################################################

function wp_dlm_show_downloads($mode = 1,$no = 5) {
	switch ($mode) {
		case 1 :
			$dl = get_downloads('limit='.$no.'&orderby=hits&order=desc');
		break;
		case 2 :
			$dl = get_downloads('limit='.$no.'&orderby=date&order=desc');
		break;
		case 3 :
			$dl = get_downloads('limit='.$no.'&orderby=random&order=desc');
		break;
	}
	if (!empty($dl)) {
		echo '<ul class="downloadList">';
		foreach($dl as $d) {
			$date = date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date));
			switch ($mode) {
				case (1) :
				case (3) :
					echo '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' ('.$d->hits.')</a></li>';
				break;
				case (2) :
					echo '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" >'.$d->title.' <span>('. date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).')</span></a></li>';
				break;
			}
		}
		echo '</ul>';
	}
}

function wp_dlm_all() {
	
	global $wpdb,$wp_dlm_root,$allowed_extentions,$max_upload_size,$wp_dlm_db;
	
	$dl = get_downloads('limit=&orderby=title&order=asc');		
	
	if (!empty($dl)) {
		$retval = '<ul class="downloadList">';
		foreach($dl as $d) {
			$retval .= '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").' - '.__('Added',"wp-download_monitor").' '.date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).'" >'.$d->title.' ('.$d->hits.')</a></li>';
		}
		$retval .='</ul>';
	}
	
	return $retval;
}

function wp_dlm_advanced() {
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$downloadurl,$dlm_url,$downloadtype, $download_taxonomies;
	// Get post data
	if (isset($_POST['show_downloads'])) $showing = (int) $_POST['show_downloads']; else $showing = 0;
	if ($showing==0 || $showing=="") {
		$dl = get_downloads('limit=10&orderby=hits&order=desc');
	} else {
		$dl = get_downloads('limit=&orderby=title&order=asc&category='.$showing.'');
	}
	// Output selector box
	$retval = '<div class="download-box"><form method="post" action="#">
		<select name="show_downloads">
			<option value="0">'.__('Most Popular Downloads',"wp-download_monitor").'</option>
			<optgroup label="'.__('Categories',"wp-download_monitor").'">';
	// Echo categories;	
	$cats = $download_taxonomies->get_parent_cats();
	if (!empty($cats)) {
		foreach ( $cats as $c ) {
			$retval .= '<option ';
			if ($showing==$c->id) $retval .= 'selected="selected"';
			$retval .= 'value="'.$c->id.'">'.$c->name.'</option>';
			$retval .= get_option_children_cats($c->id, "$c->name &mdash; ", $showing, 0);
		}
	} 
	$retval .= '</optgroup></select> <input type="submit" value="Go" /></form>';
	
	if (!empty($dl)) {
		$retval .= '<ul class="download-list">';
		foreach($dl as $d) {
			$retval .= '<li><a href="'.$d->url.'" title="'.__('Version',"wp-download_monitor").' '.$d->version.' '.__('downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").' - '.__('Added',"wp-download_monitor").' '.date_i18n(__("jS M Y","wp-download_monitor"), strtotime($d->date)).'" >'.$d->title.' ('.$d->hits.')</a></li>';
		}
		$retval .='</ul>';
	} else $retval .='<p>'.__('No Downloads Found',"wp-download_monitor").'</p>';
	$retval .= "</div>";
	return $retval;
}

function wp_dlm_parse_downloads_all($data) {
	if (substr_count($data,"[#show_downloads]")) {
		$data = str_replace("[#show_downloads]",wp_dlm_all(), $data);
	} 
	if (substr_count($data,"[#advanced_downloads]")) {
		$data = str_replace("[#advanced_downloads]",wp_dlm_advanced(), $data);
	}
	return $data;
} 

?>