<?php
/*  
	Wordpress Download Monitor Add-on: Download Page
	
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

global $wp_dlm_root;

$wp_dlmp_root = $wp_dlm_root.'page-addon/';

################################################################################
// Styles and Javascript
################################################################################

function wp_dlmp_l10n_style() {	
	/* TRANSLATORS: The value for Swedish is languages/img/downloadbutton-sv_SE.gif */
	$dl_image_url = plugins_url(__('page-addon/downloadbutton.gif','wp-download_monitor'), dirname(__FILE__));
	/* TRANSLATORS: The value for Swedish is languages/img/morebutton-sv_SE.gif */
	$more_image_url = plugins_url(__('page-addon/morebutton.gif','wp-download_monitor'), dirname(__FILE__));
?>
	<style type='text/css'>
	.download-info .download-button {
		background-image: url(<?php echo $dl_image_url ?>);
	}
	.download-info .more-button {
		background-image: url(<?php echo $more_image_url ?>);
	}
	</style>
<?php
}
add_action('wp_head', 'wp_dlmp_l10n_style' );

function wp_dlmp_styles() {
	if (function_exists('wp_register_style') && function_exists('wp_enqueue_style') ) {
		global $wp_dlmp_root;
	    $myStyleFile = $wp_dlmp_root.'styles.css';
	    
	    if ( is_ssl() ) $myStyleFile = str_replace( 'http://', 'https://', $myStyleFile );
	    
	    wp_register_style('wp_dlmp_styles', $myStyleFile);
	    wp_enqueue_style( 'wp_dlmp_styles');
    }
}
add_action('wp_print_styles', 'wp_dlmp_styles');
   																					
################################################################################
// DOWNLOAD PAGE OUTPUT FUNCTION
################################################################################

function wp_dlmp_output( $base_heading_level = '3', $pop_count = 4, $pop_cat_count = 4, $show_uncategorized = true, $per_page = 20, $format = '', $exclude = '', $exclude_cat = '', $show_tags = 0, $default_order = 'title' , $front_order = 'hits')
{
if (function_exists('get_downloads')) {

	// DEFINE STRINGS (translate these if needed)
	$popular_text 			= __('Popular Downloads','wp-download_monitor');
	$tags_widget_text 		= __('Tags','wp-download_monitor');
	$uncategorized 			= __('Other','wp-download_monitor');
	$search_text 			= __('Search Downloads:','wp-download_monitor');
	$search_submit_text 	= __('Go','wp-download_monitor');
	$search_results_text 	= __('Results found for ','wp-download_monitor');
	$nonefound 				= __('No downloads were found.','wp-download_monitor');
	$notfound 				= __('No download found matching given ID.','wp-download_monitor');
	$main_page_back_text	= __('Downloads','wp-download_monitor');
	$desc_heading 			= __('Description','wp-download_monitor');
	$version_text 			= __('Version','wp-download_monitor');
	$category_text 			= __('Categories','wp-download_monitor');
	$single_tags_text 				= __('Tags','wp-download_monitor');
	$hits_text 				= __('Downloaded','wp-download_monitor');
	$hits_text2 			= __(' time','wp-download_monitor');
	$hits_text2_p 			= __(' times','wp-download_monitor');
	$posted_text 			= __('Date posted','wp-download_monitor');
	$posted_text2 			= __('F j, Y','wp-download_monitor');
	$dbutton_text 			= __('Download','wp-download_monitor');
	$readmore_text 			= __('Read More','wp-download_monitor');
	$subcat_text 			= __('Sub-Categories:','wp-download_monitor');
	$sort_text 				= __('Sort by:','wp-download_monitor');
	$tags_text 				= __('Downloads tagged:','wp-download_monitor');
	// END DEFINE STRINGS

	global $wpdb, $wp_dlm_db, $wp_dlm_db_taxonomies, $wp_dlm_db_relationships, $post, $wp_dlmp_root, $wp_dlm_db_meta, $download_taxonomies;

	// Handle $exclude
	$exclude_array = array();
	if ($exclude) {
		$exclude_unclean = explode(',',$exclude);		
		foreach ($exclude_unclean as $e) {
			$e = trim($e);
			if (is_numeric($e)) $exclude_array[] = $e;
		}
	}	
	if (sizeof($exclude_array) > 0) $exclude_query = ' AND '.$wp_dlm_db.'.id NOT IN ('.implode(',',$exclude_array).')';
	else {
		$exclude_query="";
		$exclude_array[] = 0;
	}
	
	// Handle $exclude_cat
	$exclude_cat_array = array();
	if ($exclude_cat) {
		$exclude_cat_unclean = explode(',',$exclude_cat);	
		foreach ($exclude_cat_unclean as $e) {
			$e = trim($e);
			if (is_numeric($e)) $exclude_cat_array[] = $e;
		}
	}
	$category_array = array();
	if (is_object($download_taxonomies) && sizeof($download_taxonomies->categories)>0) {
		foreach ($download_taxonomies->categories as $category) {
			if (!in_array($category->id, $exclude_cat_array)) {
				$category_array[$category->id] = $category;
			}
		}
	}

	// Find more IDS to exlude
	if (sizeof($exclude_cat_array) > 0) {
	
		$results = $wpdb->get_results("
		SELECT $wp_dlm_db.id 
		FROM $wp_dlm_db 		
		LEFT JOIN $wp_dlm_db_relationships ON( $wp_dlm_db.id = $wp_dlm_db_relationships.download_id )
		LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
		WHERE $wp_dlm_db_taxonomies.id IN (".implode(',',$exclude_cat_array).")
		AND $wp_dlm_db_taxonomies.taxonomy = 'category';");
		
		$new_exclude_array = array();
		foreach ($results as $r) {
			$new_exclude_array[] = $r->id;
		}
		$exclude_array = array_merge($exclude_array,$new_exclude_array);
	}
	if (sizeof($exclude_array) > 0) $exclude_query = ' AND '.$wp_dlm_db.'.id NOT IN ('.implode(',',$exclude_array).')';
	else $exclude_query="";
	
	// Handle Formats
	global $download_formats_names_array, $def_format;
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
	// Default is none set/no defaults
	if (empty($format) || $format=='0') {
		$format = '<a class="downloadlink" href="{url}" title="{version,"'.__("Version","wp-download_monitor").'", ""} '.__("downloaded","wp-download_monitor").' {hits} '.__("times","wp-download_monitor").'" >{title} ({hits})</a>';	
	}	
	
	wp_dlm_magic();
	
	// DOWNLOAD PAGE DATA FUNCTIONS
	if (!function_exists('wp_dlmp_append_url')) {
	function wp_dlmp_append_url( $append ) {
		global $post;
		$querystring = explode('?', get_permalink( $post->ID ));
		$add = '?';
		if (isset($querystring[1])) {
			$add .= $querystring[1].'&amp;';
		}
		$add .= $append;
		return $querystring[0].$add;
	}
	}
    if (!function_exists('get_chain')) {
    function get_chain($cat, $chain = array()) {
    	global $download_taxonomies;
   		
   		$chain[] = '<a href="'.wp_dlmp_append_url(	'category='.urlencode(strtolower($cat->id))	).'">&laquo; '.$cat->name.'</a>';
   		if ($cat->parent>0) $chain = get_chain($download_taxonomies->categories[$cat->parent], $chain);
   		
   		return $chain;
   		
    }
    function no_chain($chain = array()) {

   		return $chain;
   		
    }
    }
	
	// START PAGE OUTPUT
	$page = '';
	$fields = '';
	
	// Ensure it works with default permalinks
	global $post;
	if ($post && is_page()) $fields = '<input type="hidden" name="page_id" value="'.$post->ID.'" />';
	if ($post && is_single()) $fields = '<input type="hidden" name="p" value="'.$post->ID.'" />';
	if (isset($_GET['lang'])) $fields .= '<input type="hidden" name="lang" value="'.$_GET['lang'].'" />';
	
	$dlsearch = '';
	if (isset($_REQUEST['dlsearch'])) 
		$dlsearch = esc_attr( $_REQUEST['dlsearch'] );
	
	$page .= '<div id="download-page">
		<form id="download-page-search" action="" method="post">
			<p><label for="dlsearch">'.$search_text.'</label> <input type="text" name="dlsearch" id="dlsearch" value="'.$dlsearch.'" /><input class="search_submit" type="submit" value="'.$search_submit_text.'" />'.$fields.'</p></form>';
		
	if (isset($dlsearch) && !empty($dlsearch)) {
	
		##########################################################################################################################################################################################
		## Search View
		##########################################################################################################################################################################################
		
		$page .= '<h'.$base_heading_level.'>'.$search_results_text.'<em>"'.$dlsearch.'"</em> <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';

		$orderby = '';
		if (!isset($_GET['sortby'])) $_GET['sortby'] = $default_order;
		// Sorting Options
			switch (trim(strtolower($_GET['sortby']))) {
				case 'hits' :
					$sort_hits = 'class="active"';
					$sort_date = '';
					$sort_title = '';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.hits DESC';
				break;
				case 'date' :
					$sort_date = 'class="active"';
					$sort_hits = '';
					$sort_title = '';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.postDate DESC';
				break;
				default :
					$sort_title = 'class="active"';
					$sort_date = '';
					$sort_hits = '';
					$orderby = 'ORDER BY '.$wp_dlm_db.'.title ASC';
				break;
			}					
			$sort_options = array('<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby=title').'" '.$sort_title.'>'.__('Title','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby=hits').'" '.$sort_hits.'>'.__('Hits','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby=date').'" '.$sort_date.'>'.__('Date','wp-download_monitor').'</a>');
			$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
			$page .= implode(' | ', $sort_options).'</p>';
		// End Sorting Options
		
		// Pagination Calc
			$paged_query = "";
			if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
			$from = (($dlpage * $per_page) - $per_page); 
			$paged_query = 'LIMIT '.$from.','.$per_page.'';
			$total = $wpdb->get_var("SELECT COUNT(DISTINCT $wp_dlm_db.id) 
				FROM $wp_dlm_db  
				LEFT JOIN $wp_dlm_db_relationships ON( $wp_dlm_db.id = $wp_dlm_db_relationships.download_id )
				LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
				WHERE 
					(
					title LIKE '%".$wpdb->escape($dlsearch)."%' 
					OR filename LIKE '%".$wpdb->escape($dlsearch)."%'
					OR $wp_dlm_db_taxonomies.name LIKE '%".$wpdb->escape($dlsearch)."%'
					OR file_description LIKE '%".$wpdb->escape($dlsearch)."%'
					)
				AND $wp_dlm_db.id NOT IN (".implode(',',$exclude_array).")
			;");
			$total_pages = ceil($total / $per_page);
		// End Pagination Calc

		$downloads = $wpdb->get_results( "SELECT DISTINCT $wp_dlm_db.* 
				FROM $wp_dlm_db  
				LEFT JOIN $wp_dlm_db_relationships ON( $wp_dlm_db.id = $wp_dlm_db_relationships.download_id )
				LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
				WHERE 
					(
					title LIKE '%".$wpdb->escape($dlsearch)."%' 
					OR filename LIKE '%".$wpdb->escape($dlsearch)."%'
					OR $wp_dlm_db_taxonomies.name LIKE '%".$wpdb->escape($dlsearch)."%'
					OR file_description LIKE '%".$wpdb->escape($dlsearch)."%'
					)
				AND $wp_dlm_db.id NOT IN (".implode(',',$exclude_array).")
				$orderby $paged_query;" );			
			
		if (!empty($downloads)) {
		    $page .= '<ul>';
		    foreach($downloads as $d) {
		        $page .= '<li>'.do_shortcode('[download id="'.$d->id.'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]').'</li>';
		    }
		   $page .= '</ul>';
		   
			// Show Pagination				       
				if ($total_pages>1)  {
					$page .= '<ul class="pagination">';
				
					if($dlpage > 1){ 
						$prev = ($dlpage - 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";
	
					for($i = 1; $i <= $total_pages; $i++){ 
						if(($dlpage) == $i){ 
							$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
						} else { 
							$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
						} 
					} 
	
					if($dlpage < $total_pages){ 
						$next = ($dlpage + 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dlsearch='.urlencode($dlsearch).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
					
					$page .= '</ul>';
				}
			// End show pagination		   
		   
		} else $page .= '<p>'.$nonefound.'</p>';
		
	}
	elseif (isset($_GET['category'])) {
		
		##########################################################################################################################################################################################
		## Single Category view
		##########################################################################################################################################################################################
		
		$category = $wpdb->escape(trim(urldecode(strtolower($_GET['category']))));
		$downloads = "";	        
		$total_pages = "";
		$dlpage = "";
		
		if (($category==strtolower($uncategorized) || $category==0) && $show_uncategorized) {
		
			$count = $wpdb->get_var("
				SELECT COUNT(DISTINCT $wp_dlm_db.id) 
				FROM $wp_dlm_db 
				WHERE $wp_dlm_db.id NOT IN (
					SELECT download_id FROM $wp_dlm_db_relationships
					LEFT JOIN $wp_dlm_db_taxonomies ON $wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id
					WHERE $wp_dlm_db_taxonomies.taxonomy = 'category'
				)
				$exclude_query;
			");
							
			$page .= '<h'.$base_heading_level.'>'.ucwords($uncategorized).' ('.$count.') <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';

			$orderby = '';
			if (!isset($_GET['sortby'])) $_GET['sortby'] = $default_order;
			// Sorting Options
				switch (trim(strtolower($_GET['sortby']))) {
					case 'hits' :
						$sort_hits = 'class="active"';
						$orderby = 'hits';
						$order = 'desc';
					break;
					case 'date' :
						$sort_date = 'class="active"';
						$orderby = 'postdate';
						$order = 'desc';
					break;
					default :
						$sort_title = 'class="active"';
						$orderby = 'title';
						$order = 'asc';
					break;
				}					
				$sort_options = array('<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=title').'" '.$sort_title.'>'.__('Title','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=hits').'" '.$sort_hits.'>'.__('Hits','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'&sortby=date').'" '.$sort_date.'>'.__('Date','wp-download_monitor').'</a>');
				$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
				$page .= implode(' | ', $sort_options).'</p>';
			// End Sorting Options
			
			// Pagination Calc
				if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
				$from = (($dlpage * $per_page) - $per_page); 
				$total_pages = ceil($count / $per_page);
			// End Pagination Calc
			
			$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&category=none" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');
			
		} else {
			
			if ($category_array[$category]) $cat = $category_array[$category];
			
			if ($cat->id >0) {
				
		        $chain = $download_taxonomies->do_something_to_cat_parents($cat->id, 'get_chain', 'no_chain');
		        if (is_array($chain) && sizeof($chain)>0) $cat_breadcrumb = implode(' ', $chain);
								
				// Count = children too
				$in_cats = $cat->decendents;
				$in_cats[] = $cat->id;
				$count = $wpdb->get_var("
					SELECT COUNT(DISTINCT $wp_dlm_db_relationships.download_id) 
					FROM $wp_dlm_db_relationships 
					LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
					WHERE $wp_dlm_db_taxonomies.id IN (".implode(',',$in_cats).")
					AND $wp_dlm_db_relationships.download_id NOT IN (".implode(',',$exclude_array).")
					AND $wp_dlm_db_taxonomies.taxonomy = 'category';
				");
				if (!isset($cat_breadcrumb)) $cat_breadcrumb = '';
				$page .= '<h'.$base_heading_level.'>'.wptexturize($cat->name).' ('.$count.') <small>'.$cat_breadcrumb.' <a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';
				
				if (sizeof($cat->decendents) > 0) {					
					$subcats = array();
					foreach ($cat->direct_decendents as $child_cat) {
						if ($category_array[$child_cat]->name) {
							$sub_in_cats = $category_array[$child_cat]->decendents;
							$sub_in_cats[] = $category_array[$child_cat]->id;
							$scount = $wpdb->get_var("
								SELECT COUNT(DISTINCT $wp_dlm_db_relationships.download_id) 
								FROM $wp_dlm_db_relationships 
								LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
								WHERE $wp_dlm_db_taxonomies.id IN (".implode(',',$sub_in_cats).")
								AND $wp_dlm_db_relationships.download_id NOT IN (".implode(',',$exclude_array).")
								AND $wp_dlm_db_taxonomies.taxonomy = 'category';
							");	
							if ($scount>0) $subcats[] = wptexturize($category_array[$child_cat]->name).' '.'<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($category_array[$child_cat]->id))).'">('.$scount.')</a>';
						}					
					}
					if (sizeof($subcats)>0) :
						$page .= '<p class="subcats"><strong>'.$subcat_text.'</strong> ';
						sort($subcats);
						$page .= implode(' | ', $subcats).'</p>';
					endif;
				}
				
				$orderby = '';
				if (!isset($_GET['sortby'])) $_GET['sortby'] = $default_order;
				// Sorting Options
					switch (trim(strtolower($_GET['sortby']))) {
						case 'hits' :
							$sort_hits = 'class="active"';
							$sort_date = '';
							$sort_title = '';
							$orderby = 'hits';
							$order = 'desc';
						break;
						case 'date' :
							$sort_date = 'class="active"';
							$sort_hits = '';
							$sort_title = '';
							$orderby = 'postdate';
							$order = 'desc';
						break;
						default :
							$sort_title = 'class="active"';
							$sort_hits = '';
							$sort_date = '';
							$orderby = 'title';
							$order = 'asc';
						break;
					}					
					$sort_options = array('<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby=title').'" '.$sort_title.'>'.__('Title','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby=hits').'" '.$sort_hits.'>'.__('Hits','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby=date').'" '.$sort_date.'>'.__('Date','wp-download_monitor').'</a>');
					$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
					$page .= implode(' | ', $sort_options).'</p>';
				// End Sorting Options
				
				// Pagination Calc
					if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
					$from = (($dlpage * $per_page) - $per_page); 
					$paged_query = 'LIMIT '.$from.','.$per_page.'';
					$total_pages = ceil($count / $per_page);
				// End Pagination Calc

				$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&category='.$cat->id.'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');			
			}
		}
		
		// Show Pagination				       
			if ($total_pages>1)  {
				$page .= '<ul class="pagination">';
			
				if($dlpage > 1){ 
					$prev = ($dlpage - 1); 
					$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
				} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";

				for($i = 1; $i <= $total_pages; $i++){ 
					if(($dlpage) == $i){ 
						$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
					} else { 
						$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
					} 
				} 

				if($dlpage < $total_pages){ 
					$next = ($dlpage + 1); 
					$page .= "<li><a href=\"".wp_dlmp_append_url('category='.urlencode(strtolower($cat->id)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
				} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
				
				$page .= '</ul>';
			}
		// End show pagination
	
	}
	elseif (isset($_GET['dltag'])) {
		
		##########################################################################################################################################################################################
		## Tag View
		##########################################################################################################################################################################################
		
		$tag = esc_attr( strtolower( trim( urldecode( $_GET['dltag'] ) ) ) );
			
		if ($tag) {
				
			$page .= '<h'.$base_heading_level.'>'.$tags_text.' '.$tag.' <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></h'.$base_heading_level.'>';
								
			$orderby = '';
			if (!isset($_GET['sortby'])) $_GET['sortby'] = $default_order;
			// Sorting Options
				switch (trim(strtolower($_GET['sortby']))) {
					case 'hits' :
						$sort_hits = 'class="active"';
						$sort_date = '';
						$sort_title = '';
						$orderby = 'hits';
						$order = 'desc';
					break;
					case 'date' :
						$sort_date = 'class="active"';
						$sort_hits = '';
						$sort_title = '';
						$orderby = 'postdate';
						$order = 'desc';
					break;
					default :
						$sort_title = 'class="active"';
						$sort_date = '';
						$sort_hits = '';
						$orderby = 'title';
						$order = 'asc';
					break;
				}					
				$sort_options = array('<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=title').'" '.$sort_title.'>'.__('Title','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=hits').'" '.$sort_hits.'>'.__('Hits','wp-download_monitor').'</a>', '<a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby=date').'" '.$sort_date.'>'.__('Date','wp-download_monitor').'</a>');
				$page .= '<p class="sorting"><strong>'.$sort_text.'</strong> ';
				$page .= implode(' | ', $sort_options).'</p>';
			// End Sorting Options
				
			// Pagination Calc
				if(!isset($_GET['dlpage'])) $dlpage = 1; else $dlpage = $_GET['dlpage']; 
				$from = (($dlpage * $per_page) - $per_page); 
				 
				$count = $wpdb->get_var("
					SELECT COUNT(DISTINCT $wp_dlm_db_relationships.download_id) 
					FROM $wp_dlm_db_relationships 
					LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
					WHERE $wp_dlm_db_taxonomies.name = '$tag'  
					AND $wp_dlm_db_relationships.download_id NOT IN (".implode(',',$exclude_array).") 
					AND $wp_dlm_db_taxonomies.taxonomy = 'tag';
				");
				
				$total_pages = ceil($count / $per_page);
			// End Pagination Calc
			
			$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$per_page.'&orderby='.$orderby.'&order='.$order.'&offset='.$from.'&tags='.$tag.'" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');			

			// Show Pagination				       
				if ($total_pages>1)  {
					$page .= '<ul class="pagination">';
				
					if($dlpage > 1){ 
						$prev = ($dlpage - 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$prev.'')."\">&laquo; ".__('Previous',"wp-download_monitor")."</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span></li>";
	
					for($i = 1; $i <= $total_pages; $i++){ 
						if(($dlpage) == $i){ 
							$page .= "<li><span class='page-numbers current'>$i</span></li>"; 
						} else { 
							$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$i.'')."\">$i</a></li>"; 
						} 
					} 
	
					if($dlpage < $total_pages){ 
						$next = ($dlpage + 1); 
						$page .= "<li><a href=\"".wp_dlmp_append_url('dltag='.urlencode(strtolower($tag)).'&sortby='.strtolower($_GET['sortby']).'&dlpage='.$next.'')."\">".__('Next',"wp-download_monitor")." &raquo;</a></li>"; 
					} else $page .= "<li><span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span></li>";
					
					$page .= '</ul>';
				}
			// End show pagination
			
		}		
	}
	elseif (isset($_GET['did']) && is_numeric($_GET['did']) && $_GET['did']>0) {
		
		##########################################################################################################################################################################################
		## Single Download View
		##########################################################################################################################################################################################
		
		$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE $wp_dlm_db.id = ".$wpdb->escape($_GET['did'])." $exclude_query LIMIT 1;" );
			
		if (!empty($d)) {
		
			$download = new downloadable_file($d);
					
			if ($download->category) $catname = trim($download->category); 
				else $catname = ucwords($uncategorized);		
	        $date = date("jS M Y", strtotime($download->date));
	        if ($download->dlversion) $version = __('Version',"wp-download_monitor").' '.$download->dlversion; 
	        	else $version = '';
	        if ($download->file_description) $desc = apply_filters('download_description', $download->file_description); 
	        	else $desc = "";
	        $thumbnail_url = $download->thumbnail;
	        
	        // Gen category breadcrumb
	        if ($download->category_id) {
	        
		        $chain[] = '<a href="'.wp_dlmp_append_url(	'category='.urlencode(strtolower($download_taxonomies->categories[$download->category_id]->id))	).'">&laquo; '.$download_taxonomies->categories[$download->category_id]->name.'</a>';
		        $chain = $download_taxonomies->do_something_to_cat_parents($download->category_id, 'get_chain', 'no_chain', $chain);
		        $cat_breadcrumb = implode(' ', $chain);

	        } else $cat_breadcrumb = '<a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'').'">&laquo;&nbsp;'.ucwords($uncategorized).'</a>';
	        
	        $page .= '<div class="download-info single">	        		
	        		
		           	<div class="side-section">
		        		<p><img src="'.$thumbnail_url.'" class="download-image" alt="'.strip_tags($download->title).'" title="'.strip_tags($download->title).'" width="112" /></p>';
		        		
		    if (!isset($download->meta['hide_download_button'])) $page .= '<p><a href="'.do_shortcode('[download id="'.$download->id.'" format="{url}"]').'" class="download-button">'.$dbutton_text.'</a></p>';
		       
			// Handle post_id field
			if (isset($download->meta['post_id']) && is_numeric($download->meta['post_id'])) {            
				$page .= '<p><a href="'.get_permalink($download->meta['post_id']).'" class="more-button">'.$readmore_text.'</a></p>';
			} else {
				$page .= '<div class="related-projects">'."\n";
				// multiple ids
    		    $page .= '<h3>'.__('Related', 'wp-download_monitor').':</h3><ul>';
    		    $post_ids = explode(',', $download->meta['post_id']); // split into array
    		    foreach($post_ids as $dl) { // print link for each array entry
					$page .= '<li><a href="'.get_permalink(trim($dl)).'">'.get_the_title(trim($dl)).'</a></li>';
    		    }
    		    $page .= '</ul></div> <!-- /.related-projects -->'."\n";
			}

    		// Special additional content meta field
    		if (isset($download->meta['side_content'])) {
    			$extra = $download->meta['side_content'];
    			if ($extra) $page .= '<div class="extra">'.$extra.'</div>';
    		}
		  
		    $page .= '
		        	</div>
		        	<div class="main-section">
		        		<h'.$base_heading_level.' class="download-info-heading">'.$download->title.' <small>'.$cat_breadcrumb.' <small><a href="'.get_permalink( $post->ID ).'">&laquo;&nbsp;'.$main_page_back_text.'</a></small></small></h'.$base_heading_level.'>
	        	';
	        
	        // Show Meta Fields + download data
	        $custom_field_data = array();	        
	        
	        if ($download->dlversion) {
	        	$custom_field_data[] = array($version_text, $download->dlversion);
	        }
	        
	        $custom_field_data[] = array($posted_text, date($posted_text2, strtotime($download->postDate)));
	        
	        if (!isset($download->meta['hide_hits'])) {
		        if ($download->hits==1) 
	 				$custom_field_data[] = array($hits_text, $download->hits.$hits_text2);
	 			else
	 				$custom_field_data[] = array($hits_text, $download->hits.$hits_text2_p);
			}
			
	        if ($download->categories) {
	        	$names = array();
	        	foreach ($download->categories as $cat) {
	        		$names[] = '<a href="'.wp_dlmp_append_url(	'category='.urlencode(strtolower($cat['id']))	).'">'.$cat['name'].'</a>';
	        	}
	        	$custom_field_data[] = array($category_text, implode(', ', $names));
	        } 	
	        
	        if ($download->tags) {
	        	$names = array();
	        	foreach ($download->tags as $tag) {
	        		$names[] = '<a href="'.wp_dlmp_append_url(	'dltag='.urlencode(strtolower($tag['name']))	).'">'.$tag['name'].'</a>';
	        	}
	        	$custom_field_data[] = array($single_tags_text, implode(', ', $names));
	        }         
	        
	        if (isset($download->meta['include_fields'])) {
		        $show_custom_fields = $download->meta['include_fields'];
		        if ($show_custom_fields) $show_custom_fields = explode(',',$show_custom_fields);	        
		        if (sizeof($show_custom_fields)>0) {
		        	// Get each custom field's value ready to output
		        	foreach ($show_custom_fields as $field) {
		        		$value = $download->meta[$field];
		        		if (!empty($value)) {
		        			$custom_field_data[] = array(ucfirst(str_replace('-',' ',$field)), $value);
		        		}
		        	}
		        }
	        }
	            
	        if (sizeof($custom_field_data)>0) {
	        	// Output
	        	 $page .= '<table class="download-meta" cellspacing="0" style="width:100%"><thead><tr><th scope="col">Attribute</th><th style="text-align:right" scope="col">Value</th></tr></thead><tbody>';
	        	 	foreach($custom_field_data as $field) {
	        	 		 $page .= '<tr><th scope="row">'.$field[0].'</th><td style="text-align:right">'.do_shortcode($field[1]).'</td></tr>';
	        	 	}
	        	 $page .= '</tbody></table>';
	        }
	        
	        // Show Description
	        if ($desc) {	
		        $page .= '<div class="download-desc">
		        			<h'.($base_heading_level+1).' class="download-desc-heading">'.$desc_heading.'</h'.($base_heading_level+1).'>
		        			'.$desc.'
		        		</div>';
	        }
	        	
	        $page .= '</div>'; /* Close main-section */
	        
	        $page .= '</div>'; /* Close download-info */
			
		} else $page .= '<p>'.$notfound.'</p>';
		
	}
	else {
		
		##########################################################################################################################################################################################
		## Front View
		##########################################################################################################################################################################################
		
		if ($pop_count>0) {
			// Front view
			$page .= '<div id="download-page-featured">
					<h'.$base_heading_level.'>'.$popular_text.'</h'.$base_heading_level.'><ul>';
					
					// Get top downloads
					$downloads = get_downloads('limit='.$pop_count.'&orderby=hits&order=desc&exclude='.implode(',',$exclude_array).'');
					if (!empty($downloads)) {
						$alt = -1;
					    foreach($downloads as $d) {
					    	if ($alt==1) $alttext = 'alternate'; else $alttext = '';
					        $date = date("jS M Y", strtotime($d->date));
					        if ($d->version) $version = __('Version',"wp-download_monitor").' '.$d->version; else $version = '';
					        if ($d->desc) $desc = do_shortcode(wptexturize(wpautop(current(explode('<!--more-->', $d->desc))))); else $desc = "";
					        $thumbnail_url = $d->thumbnail;
					        if (!$thumbnail_url) $thumbnail_url = $wp_dlmp_root.'thumbnail.gif';
					        
					        $page .= '<li class="'.$alttext.'"><a href="'.wp_dlmp_append_url('did='.$d->id).'" title="'.$version.' '.__('Downloaded',"wp-download_monitor").' '.$d->hits.' '.__('times',"wp-download_monitor").'" ><span><img src="'.$thumbnail_url.'" class="download-thumbnail" alt="'.strip_tags($d->title).'" title="'.strip_tags($d->title).'"  /></span> <span>'.$d->title.'</span></a></li>';
					        
					        $alt = $alt*-1;
					    }
					}
			$page .= '</ul></div>';
			// End top
		}
		
		// Tags View
		if ($show_tags>0) {
			$page .= '<div id="download-page-tags">
					<h'.$base_heading_level.'>'.$tags_widget_text.'</h'.$base_heading_level.'><ul>';
					
					// Get tags
					$tags = $download_taxonomies->used_tags;			
					
					if (!empty($tags)) {
						
						$sized_tags = array();
						foreach ($tags as $tag) {
							$sized_tags[$tag->name] = $tag->size;
						}
						
						$max = max($sized_tags);
						$min = min($sized_tags);	
						$div = $max-$min;
						if (!$div) $div = 1;			
						
						$multiplier = (200-80)/($div); 
						
						asort($sized_tags);
						
						$sized_tags = array_reverse($sized_tags);
						
						$sized_tags = array_slice($sized_tags, 0, $show_tags);	
						
						ksort($sized_tags);		  
   
						foreach ($sized_tags as $tag=>$count) {
							$size = 80 + (($max-($max-($count-$min)))*$multiplier);
							$page .= '<li style="font-size:'.$size.'%"><a href="'.wp_dlmp_append_url('dltag='.urlencode(strtolower($tag))).'">'.$tag.'</a></li>';
						}
					}
			$page .= '</ul></div>';
		}
		// End Tags
		
		// Begin cats
		$page .= '<div id="download-page-categories">';
		// Show categories
		if (sizeof($category_array)>0) {
			$alt = -1;
			foreach ($category_array as $cat) {
				if ($cat->parent>0) continue;
			
				// Count = children too
				$in_cats = $cat->decendents;
				$in_cats[] = $cat->id;

				$count = $wpdb->get_var("
					SELECT COUNT(DISTINCT $wp_dlm_db_relationships.download_id) 
					FROM $wp_dlm_db_relationships 
					LEFT JOIN $wp_dlm_db_taxonomies ON($wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id)
					WHERE $wp_dlm_db_taxonomies.id IN (".implode(',',$in_cats).")
					AND $wp_dlm_db_relationships.download_id NOT IN (".implode(',',$exclude_array).")
					AND $wp_dlm_db_taxonomies.taxonomy = 'category';
				");

				if ($count==0) continue;
				
				if ($alt==1) $alttext = 'alternate'; else $alttext = '';
				$page .= '<div class="category '.$alttext.'"><div class="inner">';
				$page .= '<h'.$base_heading_level.'><a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($cat->id))).'">'.wptexturize($cat->name).' ('.$count.') &raquo;</a></h'.$base_heading_level.'>';
				if ($pop_cat_count>0) {
					$page .= '<ol>';
					
					$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$pop_cat_count.'&orderby='.$front_order.'&order=desc&category='.$cat->id.'" wrap="" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}',$format)).'"]');
					$page .= '</ol>';
				}
				$page .= '</div></div>';
				$alt = $alt*-1;
			}
			// $show_uncategorized
			if ($show_uncategorized) {
			
				if ($alt==1) $alttext = 'alternate'; else $alttext = '';
				
				$count = $wpdb->get_var("
					SELECT COUNT(DISTINCT $wp_dlm_db.id) 
					FROM $wp_dlm_db 
					WHERE $wp_dlm_db.id NOT IN (
						SELECT download_id FROM $wp_dlm_db_relationships
						LEFT JOIN $wp_dlm_db_taxonomies ON $wp_dlm_db_relationships.taxonomy_id = $wp_dlm_db_taxonomies.id
						WHERE $wp_dlm_db_taxonomies.taxonomy = 'category'
					)
					$exclude_query;
				");
				
				if ($count>0) {
					
					$page .= '<div class="category '.$alttext.'"><div class="inner">';
					$page .= '<h'.$base_heading_level.'><a href="'.wp_dlmp_append_url('category='.urlencode(strtolower($uncategorized)).'').'">'.ucwords($uncategorized).' ('.$count.') &raquo;</a></h'.$base_heading_level.'>';
					
					if ($pop_cat_count>0) {
						$page .= '<ol>';
						$page .= do_shortcode('[downloads query="exclude='.implode(',',$exclude_array).'&limit='.$pop_cat_count.'&orderby='.$front_order.'&order=desc&category=none" wrap="" format="'.htmlspecialchars(str_replace('{url}',wp_dlmp_append_url('did=').'{id}', $format)).'"]');
						$page .= '</ol>';
					}
					$page .= '</div></div>';
					$alt = $alt*-1;
				
				}
			}
		}
			
		$page .= '</div>';
		// End cats
	}
	
	$page .= '</div>';
	
	$page .= "\n<!-- Download Page powered by WordPress Download Monitor (http://mikejolley.com). Fugue icons by Yusuke Kamiyamane (http://pinvoke.com). -->";
	
	return $page;
}
}

################################################################################
// SHORTCODE
################################################################################

function wp_dlmp_shortcode_download_page( $atts ) {

	extract(shortcode_atts(array(
		'base_heading_level' => '3',
		'pop_count' => '4',
		'pop_cat_count' => '4',
		'show_uncategorized' => '1',
		'per_page' => '20',
		'format' => '',
		'exclude' => '',
		'exclude_cat' => '',
		'show_tags' => '0',
		'default_order' => 'title',
		'front_order' => 'hits'
	), $atts));
	
	$output = wp_dlmp_output($base_heading_level, $pop_count, $pop_cat_count, $show_uncategorized, $per_page, $format, $exclude, $exclude_cat, $show_tags, $default_order, $front_order);
	return $output;

}
add_shortcode('download_page', 'wp_dlmp_shortcode_download_page');
?>
