<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - download_taxonomies CLASS
*/

class download_taxonomies {

	var $categories;
	var $tags;
	var $used_tags;
	var $download2taxonomy_ids;
	
	function download_taxonomies() {
		global $wpdb, $wp_dlm_db_relationships, $wp_dlm_db_taxonomies;
		
		$this->categories = array();
		$this->tags = array();
		$this->used_tags = array();
		
		$categories = get_transient( 'dlm_categories' );
		$tags = get_transient( 'dlm_tags' );
		$used_tags = get_transient( 'dlm_used_tags' );

		if ($categories && $tags && $used_tags) :
			$this->categories = $categories;
			$this->tags = $tags;
			$this->used_tags = $used_tags;
		else :
			$taxonomy_data = $wpdb->get_results( "SELECT DISTINCT $wp_dlm_db_taxonomies.id, $wp_dlm_db_taxonomies.name, $wp_dlm_db_taxonomies.order, $wp_dlm_db_taxonomies.taxonomy, $wp_dlm_db_taxonomies.parent , COUNT($wp_dlm_db_relationships.taxonomy_id) as count 
			FROM $wp_dlm_db_taxonomies 
			LEFT JOIN $wp_dlm_db_relationships ON $wp_dlm_db_taxonomies.id = $wp_dlm_db_relationships.taxonomy_id 
			WHERE $wp_dlm_db_taxonomies.`name` != '' 
			AND $wp_dlm_db_taxonomies.`id` > 0 
			GROUP BY $wp_dlm_db_taxonomies.id 
			ORDER BY $wp_dlm_db_taxonomies.`parent`, $wp_dlm_db_taxonomies.`order`, $wp_dlm_db_taxonomies.`id`;" );
			
			foreach ($taxonomy_data as $taxonomy) {
				if ($taxonomy->taxonomy == 'tag')
					$this->tags[$taxonomy->id] = new download_tag($taxonomy->id, $taxonomy->name, $taxonomy->count);
				else 
					$this->categories[$taxonomy->id] = new download_category($taxonomy->id, $taxonomy->name, $taxonomy->parent, $taxonomy->count);
			}
			
			$this->find_category_family();
			$this->filter_unused_tags();
		
			set_transient( 'dlm_categories', $this->categories, 60*60*24*7 );
			set_transient( 'dlm_tags', $this->tags, 60*60*24*7 );
			set_transient( 'dlm_used_tags', $this->used_tags, 60*60*24*7 );
		endif;
	}
	
	function find_category_family() {
		foreach ($this->categories as $cat) {
			$parent = $cat->parent;
			if ($parent > 0) :
				$this->categories[$parent]->direct_decendents[] = $cat->id; 
				$this->categories[$parent]->decendents[] = $cat->id; 
				$parent = $this->categories[$parent]->parent;
				while (	$parent > 0	) :
					$this->categories[$parent]->decendents[] = $cat->id;
					$parent = $this->categories[$parent]->parent;
				endwhile;
			endif;
		}
	}
	
	function filter_unused_tags() {
	
		global $wp_dlm_db_relationships, $wpdb;
		
		$used_ids = $wpdb->get_col( "SELECT taxonomy_id FROM $wp_dlm_db_relationships;" );
		
		if ($this->tags) {
			foreach ($this->tags as $tag) {
				if (in_array($tag->id, $used_ids)) {					
					$this->used_tags[] = $tag;
				}
			}		
		}
				
	}
	
	function get_parent_cats() {
		$cats = array();
		foreach ($this->categories as $cat) {
			if ($cat->parent==0) {	
				$cats[] = $cat;
			}
		}
		return $cats;
	}
	
	function do_something_to_cat_children($cat, $function, $function_none = '', $functionarg = '') {
		// Poor Kittens
		$retval = '';
		if($this->categories[$cat]->decendents) {
			foreach ($this->categories[$cat]->decendents as $child) {
				if ($this->categories[$child]->parent==$cat) {	
					if ($functionarg) {
						$retval = call_user_func($function, $this->categories[$child], $functionarg);
					} else {
						$retval = call_user_func($function, $this->categories[$child]);
					}
				}
			}
		} else {
			if ($function_none) {
				if ($functionarg) {
					$retval = call_user_func($function_none, $functionarg);
				} else {
					$retval = call_user_func($function_none);
				}
			}
		}
		return $retval;
	}
	
	function do_something_to_cat_parents($cat, $function, $function_none = '', $functionarg = '') {
		// Revenge
		$retval = '';
		if($parent = $this->categories[$cat]->parent) {
			if ($functionarg) {
				$retval = call_user_func($function, $this->categories[$parent], $functionarg);
			} else {
				$retval = call_user_func($function, $this->categories[$parent]);
			}
		} else {
			if ($function_none) {
				if ($functionarg) {
					$retval = call_user_func($function_none, $functionarg);
				} else {
					$retval = call_user_func($function_none);
				}
			}
		}
		return $retval;
	}

}

class download_category {
	var $id;
	var $name;
	var $parent;
	var $decendents;
	var $direct_decendents;
	var $size;
	
	function download_category($id, $name, $parent, $size) {
		$this->id = $id;
		$this->name = $name;
		$this->parent = $parent;
		$cat->decendents = array();
		$cat->direct_decendents = array();
		$this->size = $size;
	}
	
	function get_decendents() {
		if (is_array($this->decendents)) return $this->decendents;
		return array();
	}
}

class download_tag {
	var $id;
	var $name;
	var $size;
	var $parent;
	
	function download_tag($id, $name, $size) {
		$this->id = $id;
		$this->name = strtolower($name);
		$this->size = $size; 
	}
}
	
?>