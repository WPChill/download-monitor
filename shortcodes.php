<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - SHORTCODES
	
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
// Global counts
################################################################################
// Special hack by Pierre Bendayan to see if the function already exists
if(!function_exists('wp_dlm_get_total_downloads'))
{
	function wp_dlm_get_total_downloads() {
		global $wpdb, $wp_dlm_db;
		return $wpdb->get_var('SELECT SUM(hits) FROM '.$wp_dlm_db.';');
	}

	add_shortcode('total_downloads', 'wp_dlm_get_total_downloads');
}
if(!function_exists('wp_dlm_get_total_files'))
{
	function wp_dlm_get_total_files() {
		global $wpdb, $wp_dlm_db;
		return $wpdb->get_var('SELECT COUNT(*) FROM '.$wp_dlm_db.';');
	}
	add_shortcode('total_files', 'wp_dlm_get_total_files');
}

################################################################################
// MAIN SINGLE SHORTCODE
################################################################################
// Special hack by Pierre Bendayan to see if the function already exists
if(!function_exists('wp_dlm_shortcode_download'))
{
function wp_dlm_shortcode_download( $atts ) {

	extract(shortcode_atts(array(
		'id' => '0',
		'title' => null,
		'format' => '0',
		'autop' => false
	), $atts));
	
	$output = '';
	
	$id = trim(htmlspecialchars_decode($id), "\x22");
	$format = trim(htmlspecialchars_decode($format), "\x22");
	
	if ($id>0 && is_numeric($id)) {
	
		$cached_code = wp_cache_get('download_'.$id.'_'.$format);

		if($cached_code == false) {
		
			global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

			// Handle Formats
			global $download_formats_names_array;
			$format = trim($format);
			if (!$format && $def_format>0) {
				$format = wp_dlm_get_custom_format($def_format);
			} elseif ($format>0 && is_numeric($format) ) {
				$format = wp_dlm_get_custom_format($format);
			} else {
				if (isset($download_formats_names_array) && is_array($download_formats_names_array) && in_array($format,$download_formats_names_array)) {
					$format = wp_dlm_get_custom_format_by_name($format);
				} else {
					$format = html_entity_decode($format);
				}
			}				
			
			if (empty($format) || $format=='0') {
				$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';	
				
			}
			
			$format = str_replace('\\"',"'",$format);
			
			// Get download info			
			$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE id = ".$wpdb->escape($id).";" );
			if (isset($d) && !empty($d)) {
				
				$this_download = new downloadable_file($d, $format);
				
				$fpatts = $this_download->patts;
			
				$fsubs	= $this_download->subs;
		
			} 
			
			if ($fpatts && $fsubs) {
				if ( $title ) {
					$title_fpatt = array_search( '{title}', $fpatts );
					if ( false !== $title_fpatt ) {
						$fsubs[$title_fpatt] = $title;
					}
				}
				$output = str_replace( $fpatts , $fsubs , $format );
			} else $output = '[Download not found]';

			wp_cache_set('download_'.$id.'_'.$format, $output);
		
		} else {
			$output = $cached_code;
		}
		
		if ($autop && $autop !== "false") return wpautop(do_shortcode($output));
	
	} else $output = '[Download id not defined]';
	
	return do_shortcode($output);

}

	add_shortcode('download', 'wp_dlm_shortcode_download');
}
################################################################################
// SINGLE SHORTCODE that takes a format inside
################################################################################
// Special hack by Pierre Bendayan to see if the function already exists
if(!function_exists('wp_dlm_shortcode_download_data'))
{
function wp_dlm_shortcode_download_data( $atts, $content ) {

	extract(shortcode_atts(array(
		'id' => '0',
		'autop' => false
	), $atts));
	
	$output = '';
	
	$id = trim(htmlspecialchars_decode($id), "\x22");
	
	if ($id>0 && is_numeric($id)) {
		
		global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;

		// Handle Format
		$format = html_entity_decode($content);
		
		// Untexturize content - adapted from wpuntexturize by Scott Reilly
		$codes = array('&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8242;', '&#8243;');
		$replacements = array("'", "'", '"', '"', "'", '"');
		
		$format = str_replace($codes, $replacements, $format);	
		
		// Get download info			
		$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE id = ".$wpdb->escape($id).";" );
		if (isset($d) && !empty($d)) {
			
			$this_download = new downloadable_file($d, $format);
			
			$fpatts = $this_download->patts;
		
			$fsubs	= $this_download->subs;
	
		} 
		
		if ($fpatts && $fsubs) {
			$output = str_replace( $fpatts , $fsubs , $format );
		} else $output = '[Download not found]';
		
		if ($autop && $autop !== "false") return wpautop(do_shortcode($output));
	
	} else $output = '[Download id not defined]';
	
	return do_shortcode(wptexturize($output));

}
add_shortcode('download_data', 'wp_dlm_shortcode_download_data');
}
################################################################################
// SHORTCODE FOR MULTIPLE DOWNLOADS
################################################################################		
// Special hack by Pierre Bendayan to see if the function already exists
if(!function_exists('wp_dlm_shortcode_downloads'))
{		
function wp_dlm_shortcode_downloads( $atts ) {
	
	extract(shortcode_atts(array(
		'query' => 'limit=5&orderby=rand',
		'format' => '0',
		'autop' => false,
		'wrap' => 'ul',
		'before' => '<li>',
		'after' => '</li>'
	), $atts));
	
	$query = str_replace('&#038;','&', $query);
	
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $wp_dlm_db_meta;

	$dl = get_downloads($query);
	
	$output = '';

	if (!empty($dl)) {		
		// Handle Formats
		global $download_formats_names_array;
		$format = trim($format);
		if (!$format && $def_format>0) {
			$format = wp_dlm_get_custom_format($def_format);
		} elseif ($format>0 && is_numeric($format) ) {
			$format = wp_dlm_get_custom_format($format);
		} else {
			if (isset($download_formats_names_array) && is_array($download_formats_names_array) && in_array($format,$download_formats_names_array)) {
				$format = wp_dlm_get_custom_format_by_name($format);
			} else {
				$format = html_entity_decode($format);
			}
		}	
		if (empty($format) || $format=='0') {
			$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';		
			
		}
		
		$format = str_replace('\\"',"'",$format);

		foreach ($dl as $d) {
			
			$d->prep_download_data($format);
			
			$fpatts = $d->patts;
				
			$fsubs	= $d->subs;
	
			$output .= html_entity_decode($before).str_replace( $fpatts , $fsubs , $format ).html_entity_decode($after);
			
   		} 
	
	} else $output = html_entity_decode($before).'['.__("No Downloads found","wp-download_monitor").']'.html_entity_decode($after);	
	
	if ( $wrap == 'ul' ) {
		$output = '<ul class="dlm_download_list">' . $output . '</ul>';
	} elseif ( ! empty( $wrap ) ) {
		$output = '<' . $wrap . '>' . $output . '</' . $wrap . '>';
	}
	
	if ($autop) return wpautop(do_shortcode($output));
	return do_shortcode($output);

}
add_shortcode('downloads', 'wp_dlm_shortcode_downloads');
}
################################################################################
// Main template tag to get multiple downloads
################################################################################
// Special hack by Pierre Bendayan to see if the function already exists
if(!function_exists('get_downloads'))
{	
function get_downloads($args = null) {

	$defaults = array(
		'limit' => '', 
		'offset' => '0',
		'orderby' => 'id',
		'meta_name' => '',
		'vip' => '0',
		'category' => '',
		'tags' => '',	
		'order' => 'asc',
		'digforcats' => 'true',
		'exclude' => '',
		'include' => '',
		'author' => ''
	);
	
	$args = str_replace('&amp;','&',$args);

	$r = wp_parse_args( $args, $defaults );
	
	global $wpdb, $wp_dlm_root, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $wp_dlm_db_meta, $dlm_url, $downloadurl, $downloadtype, $download_taxonomies, $download2taxonomy_array;
	
	// New Query etc
	$where = array();
	$in_ids = array();
	$cat_in_ids = array();
	$tag_in_ids = array();
	$not_in_ids = array();
	$join = '';
	$limitandoffset = '';
	
	while (true) :	
			
		// Handle $include (top priority)
		$include_array = array();
		if ( $r['include'] ) {
			$include_unclean = array_map('intval', explode(',',$r['include']));
			$in_ids = array_merge($in_ids, $include_unclean);
			break;
		}
				
		// Cats and tags
		if ( ! empty($r['category']) && $r['category']!=='none' ) {
			$categories = explode(',',$r['category']);
			$the_cats = array();
			$the_excluded_cats = array();
			// Traverse through categories to get sub-cats
			foreach ($categories as $cat) {
				if ($cat<0) {
					// Support for -cat
					$scat = $cat*-1;
					if (isset($download_taxonomies->categories[$scat])) {	
						if ($r['digforcats']) $the_excluded_cats = array_merge($the_excluded_cats, $download_taxonomies->categories[$scat]->get_decendents());
						$the_excluded_cats[] = $scat;
					}
				} else {
					if (isset($download_taxonomies->categories[$cat])) {	
						if ($r['digforcats']) $the_cats = array_merge($the_cats, $download_taxonomies->categories[$cat]->get_decendents());
						$the_cats[] = $cat;
					}
				}
			}
			if (sizeof($the_excluded_cats)>0) {
				foreach ($download2taxonomy_array as $tid=>$tax_array) {
					if (sizeof(array_intersect($tax_array, $the_excluded_cats))>0) $not_in_ids[] = $tid;
				}
			}
			if (sizeof($the_cats)>0) {
				foreach ($download2taxonomy_array as $tid=>$tax_array) {
					if (sizeof(array_intersect($tax_array, $the_cats))>0) $cat_in_ids[] = $tid;
				}
				if (sizeof($cat_in_ids)==0) :
					// No results found, show no results
					return false;
				endif;
			} 
			if (sizeof($the_cats)==0 && sizeof($the_excluded_cats)==0) {
				// Category argument was set, but the cat probably does not exist; we should still respect it and show no results
				return false;
			}			
		} elseif ($r['category']=='none') {		
			$the_cats = array_keys($download_taxonomies->categories);
			
			foreach ($download2taxonomy_array as $tid=>$tax_array) {
				if (sizeof(array_intersect($tax_array, $the_cats))==0) $cat_in_ids[] = $tid;
			}
			if (sizeof($cat_in_ids)==0) :
				// No results found, show no results
				return false;
			endif;
		} 
		
		if ( ! empty($r['tags']) ) {
			
			$tags = explode(',', $r['tags']);
			
			$tag_ids = array();
			
			if ($download_taxonomies->tags && sizeof($download_taxonomies->tags) >0) foreach ($download_taxonomies->tags as $tag) {
				$tag->name;
				if (in_array($tag->name, $tags)) {
					// Include
					$tag_ids[] = $tag->id;
				}
			} 		
			if (sizeof($tag_ids)>0) {
				foreach ($download2taxonomy_array as $tid=>$tax_array) {
					if (sizeof(array_intersect($tax_array, $tag_ids))>0) $tag_in_ids[] = $tid;
				}
				if (sizeof($tag_in_ids)==0) :
					// No results found, show no results
					return false;
				endif;
			} else {
				// Tag argument was set, but the tag probably does not exist; we should still respect it and show no results
				return false;
			}	
		} 
		
		// Tags and cats in_ids must work in harmony - if both are defined then we must only return downloads from both
		if (sizeof($tag_in_ids)>0 && sizeof($cat_in_ids)>0) :
			$tags_and_cats_ids = array_intersect($tag_in_ids, $cat_in_ids);
			if (sizeof($tags_and_cats_ids)>0) :
				$in_ids = array_merge($in_ids, $tags_and_cats_ids);
			else :
				// O, dear - none were found in both arrays so there are no results
				return false;
			endif;
		else :
			if (sizeof($cat_in_ids)>0) :
				$in_ids = array_merge($in_ids, $cat_in_ids);
			endif;
			if (sizeof($tag_in_ids)>0) :
				$in_ids = array_merge($in_ids, $tag_in_ids);
			endif;
		endif;

		// Handle $exclude
		$exclude_array = array();
		if ( $r['exclude'] ) {
			$exclude_unclean = array_map('intval', explode(',',$r['exclude']));		
			$not_in_ids = array_merge($not_in_ids, $exclude_unclean);
		}	
		
		break;
	endwhile;

	// VIP Mode
	if ( isset($r['vip']) && $r['vip']==1 ) {	
		global $user_ID;	
		if (!isset($user_ID) || $user_ID==0) {
			$where[] = ' members=0 ';
		}	
	}
	
	// Handle Author
	if ( ! empty($r['author']) ) $where[] = ' user = "'.$wpdb->escape($r['author']).'" ';
	
	// Limit
	if ( empty( $r['limit'] ) || !is_numeric($r['limit']) ) $r['limit'] = '';		
	if ( !empty( $r['limit'] ) && (empty($r['offset']) || !is_numeric($r['offset'])) ) $r['offset'] = 0; elseif ( empty( $r['limit'] )) $r['offset'] = '';	
	if ( !empty( $r['limit'] ) ) $limitandoffset = ' LIMIT '.$r['offset'].', '.$r['limit'].' ';
	
	if ( ! empty($r['orderby']) ) {
		// Can order by date/postDate, filename, title, id, hits, random
		$r['orderby'] = strtolower($r['orderby']);
		switch ($r['orderby']) {
			case 'postdate' : 
			case 'date' : 
				$orderby = 'postDate';
			break;
			case 'filename' : 
				$orderby = 'filename';
			break;
			case 'title' : 
				$orderby = 'title';
			break;
			case 'hits' : 
				$orderby = 'hits';
			break;
			case 'meta' : 
				$orderby = "$wp_dlm_db_meta.meta_value";
				$join = " LEFT JOIN $wp_dlm_db_meta ON $wp_dlm_db.id = $wp_dlm_db_meta.download_id ";
				$where[] = ' meta_name = "'.$r['meta_name'].'"';
			break;
			case 'version' : 
			   $orderby = 'dlversion';
			break;
			case 'rand' :
			case 'random' :
				$orderby = 'RAND()';
			break;
			case 'id' : 
			default :
				$orderby = $wp_dlm_db.'.id';
			break;
		}
	}
	
	if (strtolower($r['order'])!=='desc' && strtolower($r['order'])!=='asc') $r['order']='desc';
	
	// Process 'in ids' and excluded ids
	if (sizeof($in_ids) > 0) {
		$in_ids = array_unique($in_ids);
		if (sizeof($not_in_ids) > 0) $in_ids = array_diff($in_ids, $not_in_ids);
		if (sizeof($in_ids) > 0) {
			$where[] = ' '.$wp_dlm_db.'.id IN ('.implode(',',$in_ids).') ';
		} else {
			// Excluded cancelled out all the in_ids = no results
			return false;
		}
	} elseif (sizeof($not_in_ids) > 0) {
		$not_in_ids = array_unique($not_in_ids);
		$where[] = ' '.$wp_dlm_db.'.id NOT IN ('.implode(',',$not_in_ids).') ';
	}
	
	// Process where clause
	if (sizeof($where)>0) $where = ' WHERE '.implode(' AND ', $where); else $where = '';
	
	$downloads = $wpdb->get_results("
	
		SELECT DISTINCT $wp_dlm_db.id, $wp_dlm_db.title, $wp_dlm_db.filename, $wp_dlm_db.file_description, $wp_dlm_db.dlversion, $wp_dlm_db.postDate, $wp_dlm_db.hits, $wp_dlm_db.user, $wp_dlm_db.members, $wp_dlm_db.mirrors
		FROM $wp_dlm_db
		".$join."
		".$where."		
		ORDER BY $orderby ".$r['order']."	
		".$limitandoffset.";
	
	");		
	// End new query style
		
	$return_downloads = array();

	// Process download variables
	foreach ($downloads as $dl) {
		
		$d = new downloadable_file($dl);
		
		$return_downloads[] = $d;
	}
	
	return $return_downloads;
		
}
}
?>