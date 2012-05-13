<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - SUPPORTING FUNCTIONS
	
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

function wp_dlm_clear_cached_stuff() { 
	delete_transient( 'dlm_categories' );
	delete_transient( 'dlm_tags' );
	delete_transient( 'dlm_used_tags' );
	wp_cache_flush();
}

add_action('download_added', 'wp_dlm_clear_cached_stuff');

################################################################################
// MAGIC QUOTES - WORDPRESS DOES THIS BUT ADDS THE SLASHES BACK - I DONT WANT THEM!
################################################################################

if (!function_exists('wp_dlm_magic')) {
function wp_dlm_magic() { 

	//if (get_magic_quotes_gpc() || get_magic_quotes_runtime() ){ 
		$_GET = array_map('stripslashes_deep', $_GET); 
		$_POST = array_map('stripslashes_deep', $_POST);
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST); 
	//}
	return;
}
}

################################################################################
// GET FORMAT FROM DB
################################################################################
function wp_dlm_get_custom_format($id) {
	global $download_formats_array;
	$format = '';
	$format = $download_formats_array[$id];
	return $format->format;	
}
function wp_dlm_get_custom_format_by_name($name) {
	global $download_formats_array;
	
	$format_id = '';
	
	if ($download_formats_array) {
		foreach($download_formats_array as $format) {
			if ($format->name==$name) {
				$format_id = $format->id;
				break;
			}
		}
	}
	if ($format_id>0) {
		$format = $download_formats_array[$format_id];
		return $format->format;	
	}
}

################################################################################
// For Changing dates. Modified from touch_time() function
################################################################################

function dlm_touch_time($timestamp) {
	global $wp_locale;

	$jj = mysql2date( 'd', $timestamp, false );
	$mm = mysql2date( 'm', $timestamp, false );
	$aa = mysql2date( 'Y', $timestamp, false );
	$hh = mysql2date( 'H', $timestamp, false );
	$mn = mysql2date( 'i', $timestamp, false );
	$ss = mysql2date( 's', $timestamp, false );

	$month = "<select name=\"mm\">\n";
	for ( $i = 1; $i < 13; $i = $i +1 ) {
		$month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
		if ( $i == $mm )
			$month .= ' selected="selected"';
		$month .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
	}
	$month .= '</select>';

	$day = '<input type="text" name="jj" value="' . $jj . '" size="2" maxlength="2" />';
	$year = '<input type="text" name="aa" value="' . $aa . '" size="4" maxlength="4" />';
	$hour = '<input type="text" name="hh" value="' . $hh . '" size="2" maxlength="2" />';
	$minute = '<input type="text" name="mn" value="' . $mn . '" size="2" maxlength="2" />';
	printf(__('%1$s%2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);
}

################################################################################
// let_to_num used for file sizes
################################################################################

function dlm_let_to_num($v){ //This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
    $l = substr($v, -1);
    $ret = substr($v, 0, -1);
    switch(strtoupper($l)){
    case 'P':
        $ret *= 1024;
    case 'T':
        $ret *= 1024;
    case 'G':
        $ret *= 1024;
    case 'M':
        $ret *= 1024;
    case 'K':
        $ret *= 1024;
        break;
    }
    return $ret;
}

################################################################################
//	Gap filler for dates/stats
################################################################################

if (!function_exists('dlm_fill_date_gaps')) {
	function dlm_fill_date_gaps($prev, $date, $gapcalc, $dateformat) {
		global $wp_dlm_root;

		$string = array();
		$loop = 0;

		while ( $date>$prev ) :									
			
			$date = strtotime($gapcalc, $date );
			
			$string[] = '<tr>			
				<td style="width:25%;">'.date_i18n($dateformat, $date ).'</td>
				<td class="value"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="0%" />0</td>
			</tr>';									
			$loop++;
		endwhile;
		
		return implode('',array_reverse($string));
	}
}

################################################################################
//	Wrap tags in quotes
################################################################################

if (!function_exists('wrap_tags')) {
function wrap_tags($tag) {
	return '"'.trim($tag).'"';
}
}

################################################################################
// ADD MEDIA BUTTONS AND FORMS
################################################################################
       
function wp_dlm_add_media_button() {
	global $wp_dlm_root;
	$url = WP_PLUGIN_URL.'/download-monitor/uploader.php?tab=add&TB_iframe=true&amp;height=500&amp;width=640';
	if (is_ssl()) $url = str_replace( 'http://', 'https://',  $url );
	echo '<a href="'.$url.'" class="thickbox" title="'.__('Add Download','wp-download_monitor').'"><img src="'.$wp_dlm_root.'img/media-button-download.gif" alt="'.__('Add Download','wp-download_monitor').'"></a>';
}

################################################################################
// Category Functions
################################################################################

function get_download_taxonomy($id, $taxonomy = 'category') {
	global $wp_dlm_db_taxonomies,$wp_dlm_db_relationships,$wp_dlm_db,$wpdb;
	$taxonomies = array();
	$taxonomy_names = array();
	$taxonomy_ids = array();
	$taxonomy_list = array();
	$download_taxonomies = $wpdb->get_results("SELECT DISTINCT * FROM $wp_dlm_db_taxonomies WHERE id IN ( SELECT taxonomy_id FROM $wp_dlm_db_relationships WHERE download_id = ".$wpdb->escape($id)." ) AND taxonomy='".$wpdb->escape($taxonomy)."' ORDER BY id;");	
	foreach ($download_taxonomies as $c) {
		$taxonomy_ids[] = $c->id;
		if ($taxonomy=='tag') $taxonomy_names[] = strtolower($c->name);
		else $taxonomy_names[] = $c->name;
		$taxonomy_list[] = $c->id.'&nbsp;&ndash;&nbsp;'.$c->name;
		$taxonomies[] = $c; // Add to array
	}
	return array('taxonomy'=>$taxonomies, 'ids'=>$taxonomy_ids, 'names'=>$taxonomy_names, 'list'=>$taxonomy_list );
}

function get_option_children_cats($parent,$chain,$current,$showid=1) {
	global $download_taxonomies;
	$string = '';
	if (isset($download_taxonomies->categories[$parent]->direct_decendents)) $scats = $download_taxonomies->categories[$parent]->direct_decendents; else $scats = '';
	if (!empty($scats)) {
		foreach ( $scats as $c ) {
			$string.= '<option ';
			if ($current==$download_taxonomies->categories[$c]->id) $string.= 'selected="selected"';
			$string.= 'value="'.$download_taxonomies->categories[$c]->id.'">';
			if ($showid==1) $string.= $download_taxonomies->categories[$c]->id.' - ';
			$string.= $chain.$download_taxonomies->categories[$c]->name.'</option>';
			$string.= get_option_children_cats($download_taxonomies->categories[$c]->id, "$chain".$download_taxonomies->categories[$c]->name." &mdash; ",$current,$showid);
		}
	}
	return $string;
}

?>