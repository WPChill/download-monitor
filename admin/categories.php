<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (Categories)
	
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
// Categories Configuration page
################################################################################

function wp_dlm_categories() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$dlm_url,$downloadtype,$wp_dlm_db_relationships,$download_taxonomies;

	// turn off magic quotes
	wp_dlm_magic();
	?>
	<div class="download_monitor">
	<div class="wrap alternate">
    
    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Download Categories',"wp-download_monitor"); ?></h2>
    <?php _e('<p>You can categorise downloads using these categories. You can then show groups of downloads using the category tags or a dedicated download page (see documentation). Please note, deleting a category also deletes it\'s child categories.</p>',"wp-download_monitor"); ?>
    <?php
	if (isset($_POST['rename_cat'])) {						
		$name = $_POST['cat_rename'];
		if (!empty($name)) {
			$category = $_POST['cat_to_rename'];
			$parent = $_POST['cat_parent_edit'];
			$parent_q = '';
			
			$children = array();
			if ($download_taxonomies->categories[$category]) {
				$children = $download_taxonomies->categories[$category]->get_decendents();
			}
			
			if (($parent=='0' || $parent>0) AND $parent!==$category AND !in_array($parent, $children)) $parent_q = ", parent='$parent'";
			if ($category && is_numeric($category) && $category>0) {
				$query = sprintf("UPDATE $wp_dlm_db_taxonomies SET name='%s' $parent_q WHERE id='%s' AND taxonomy = 'category';",
                    $wpdb->escape( $name ),
                    $wpdb->escape( $category ));
				$wpdb->query($query);
				wp_dlm_clear_cached_stuff();
				//echo $query;
				echo '<div id="message" class="updated fade"><p><strong>'.__('Category updated',"wp-download_monitor").'</strong></p></div>';
			}						
		}
	} elseif (isset($_POST['add_cat'])) {
		$name = $_POST['cat_name'];
		if (!empty($name)) {
			$parent = $_POST['cat_parent'];
			if (!$parent) $parent=0;
			$query_ins = sprintf("INSERT INTO %s (name, parent, taxonomy) VALUES ('%s','%s','category')",
				$wpdb->escape( $wp_dlm_db_taxonomies ),
				$wpdb->escape( $name ),
				$wpdb->escape( $parent ));
			$wpdb->query($query_ins);
			wp_dlm_clear_cached_stuff();
			if ($wpdb->insert_id>0)	echo '<div id="message" class="updated fade"><p><strong>'.__('Category added',"wp-download_monitor").'</strong></p></div>';
			else echo '<div id="message" class="updated fade"><p><strong>'.__('Category was not added. Try Recreating the download database from the configuration page.',"wp-download_monitor").'</strong></p></div>';							
		}
	} elseif (isset($_GET['action']) && $_GET['action']=='deletecat') {
		$id = $_GET['id'];	
		// Sub cats
		$delete_cats = array();
		if ($download_taxonomies->categories[$id]) {
			$delete_cats = $download_taxonomies->categories[$id]->get_decendents();
		}
		$delete_cats[]=$id;

		// Delete
		$query_delete = sprintf("DELETE FROM %s WHERE id IN (%s) AND taxonomy = 'category';",
			$wpdb->escape( $wp_dlm_db_taxonomies ),
			$wpdb->escape( implode(",",$delete_cats) ));
		$wpdb->query($query_delete);
		// Remove from relationships
		$query_delete = sprintf("DELETE FROM %s WHERE taxonomy_id IN (%s);",
			$wpdb->escape( $wp_dlm_db_relationships ),
			$wpdb->escape( implode(",",$delete_cats) ));
		$wpdb->query($query_delete);
		
		wp_dlm_clear_cached_stuff();
		
		echo '<div id="message" class="updated fade"><p><strong>'.__('Category deleted Successfully',"wp-download_monitor").'</strong></p></div>';
	}
	$download_taxonomies->download_taxonomies();
	?>
    <form action="?page=dlm_categories" method="post">
        <table class="widefat" id="sort_dlm_cats"> 
            <thead>
                <tr>
                    <th scope="col" style="text-align:center; width:3em;"><?php _e('ID',"wp-download_monitor"); ?></th>
                    <th scope="col"><?php _e('Name',"wp-download_monitor"); ?></th>
                    <th scope="col" style="text-align:center; width:5em;"><?php _e('Action',"wp-download_monitor"); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
            	<?php
            		
            		function output_category_option($child = '',$chain = '') {
            			global $download_taxonomies;
            			
            			$c = $download_taxonomies->categories[$child->id];
            			
            			if ($c) {
	
							echo '<li id="category_'.$c->id.'"><span class="handle">('.$c->id.') '.$chain.''.$c->name.'</span> <a href="?page=dlm_categories&amp;action=deletecat&amp;id='.$c->id.'" class="delete_cat" rel="'.$c->name.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a>';	
							echo '<ul class="children">';
								$download_taxonomies->do_something_to_cat_children($child->id, 'output_category_option', 'output_empty_category_option', "&mdash; ");
							echo '</ul>';
							echo '</li>';
						}
						return;
					}
					function output_empty_category_option ($child = '',$chain = '') {
						echo "<li></li>";
					}
            		
            		$cats = $download_taxonomies->get_parent_cats();
            		if ($cats) {
            			foreach ( $cats as $c ) {
							echo '<tr id="category_'.$c->id.'"><td style="text-align:center; width:5em;">('.$c->id.')</td><td><span class="handle">'.$c->name.'</span>';
								echo '<ul class="children">';
									$download_taxonomies->do_something_to_cat_children($c->id, 'output_category_option', '', "&mdash; ");
								echo '</ul>';
							echo '</td><td style="text-align:center; width:5em;"><a href="?page=dlm_categories&amp;action=deletecat&amp;id='.$c->id.'" class="delete_cat" rel="'.$c->name.'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="Delete" title="Delete" /></a></td>';							
							echo '</tr>';
						}
            		} else {
						echo '<tr><td colspan="3">'.__('No categories exist',"wp-download_monitor").'</td></tr>';
					}
				?>
            </tbody>
        </table>
        <div style="float:left; margin-right: 20px;">
        	<h4><?php _e('Add category',"wp-download_monitor"); ?></h4>
            <table class="niceblue small-table" cellpadding="0" cellspacing="0">
                <tr>
                    <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
                    <td><input type="text" name="cat_name" /></td>
                </tr>
                <tr>
                    <th scope="col"><?php _e('Parent',"wp-download_monitor"); ?>:</th>
                    <td><select name="cat_parent">
                    	<option value=""><?php _e('None',"wp-download_monitor"); ?></option>
                        <?php
							if (!empty($cats)) {
								foreach ( $cats as $c ) {
									echo '<option value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
									echo get_option_children_cats($c->id, "$c->name &mdash; ", 0);
								}
							} 
						?>
                    </select></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" value="<?php _e('Add',"wp-download_monitor"); ?>" name="add_cat" /></p>
        </div>
        <div style="float:left">
        	<h4><?php _e('Edit category',"wp-download_monitor"); ?></h4>
            <table class="niceblue small-table" cellpadding="0" cellspacing="0">	                        
                <tr>
                    <th scope="col"><?php _e('Category',"wp-download_monitor"); ?>:</th>
                    <td><select name="cat_to_rename">
                    	<option value=""><?php _e('Select a category',"wp-download_monitor"); ?></option>
                        <?php
							if (!empty($cats)) {
								foreach ( $cats as $c ) {
									echo '<option value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
									echo get_option_children_cats($c->id, "$c->name &mdash; ", 0);
								}
							} 
						?>
                    </select></td>
                </tr>
                <tr>
                    <th scope="col"><?php _e('Parent',"wp-download_monitor"); ?>:</th>
                    <td><select name="cat_parent_edit">
                    	<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                    	<option value="0"><?php _e('None',"wp-download_monitor"); ?></option>
                        <?php
							if (!empty($cats)) {
								foreach ( $cats as $c ) {
									echo '<option value="'.$c->id.'">'.$c->id.' - '.$c->name.'</option>';
									echo get_option_children_cats($c->id, "$c->name &mdash; ", 0);
								}
							} 
						?>
                    </select></td>
                </tr>
                <tr>
                    <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
                    <td><input type="text" name="cat_rename" /></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" value="<?php _e('Edit',"wp-download_monitor"); ?>" name="rename_cat" /></p>
        </div>
        <div class="clear"></div>
    </form>
    </div>
    </div>
	<?php	
}
?>