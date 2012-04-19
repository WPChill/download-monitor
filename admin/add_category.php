<?php
/** Load WordPress Administration Bootstrap */
$wp_root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if(file_exists($wp_root . '/wp-load.php')) {
	require_once($wp_root . "/wp-load.php");
} else if(file_exists($wp_root . '/wp-config.php')) {
	require_once($wp_root . "/wp-config.php");
} else {
	exit;
}

	require_once(ABSPATH.'wp-admin/admin.php');

	if ($_POST['add_cat']=='true') {
		
		$name = $_POST['cat_name'];
		$parent = $_POST['cat_parent'];
		
		if (!empty($name)) {
		
			global $wpdb, $wp_dlm_db_taxonomies, $download_taxonomies;
		
			if (!$parent) $parent=0;
			$query_ins = sprintf("INSERT INTO %s (name, parent, taxonomy) VALUES ('%s','%s','category')",
				$wpdb->escape( $wp_dlm_db_taxonomies ),
				$wpdb->escape( $name ),
				$wpdb->escape( $parent ));
			$wpdb->query($query_ins);
			
			wp_dlm_clear_cached_stuff();
			
			// Echo Form with checked inputs
			if ($wpdb->insert_id>0)	{			
				$download_taxonomies->download_taxonomies();
				?>
				<ul class="categorychecklist" style="background: #fff; border: 1px solid #DFDFDF; height: 200px; margin: 4px 1px; overflow: auto; padding: 3px 6px; width: 346px;">
					<?php	
			            $cats = $download_taxonomies->get_parent_cats();
						
			            if (!empty($cats)) {
			                foreach ( $cats as $c ) {
			                    echo '<li><label for="category_'.$c->id.'"><input type="checkbox" name="category_'.$c->id.'" id="category_'.$c->id.'" ';
								
								if (isset($_REQUEST['category_'.$c->id]) && $_REQUEST['category_'.$c->id]=='on') echo 'checked="checked"';
								
								echo ' /> '.$c->id.' - '.$c->name.'</label>';
								// Do Children
								if (!function_exists('cat_form_output_children')) {
									function cat_form_output_children($child) {
										global $download_taxonomies;
										if ($child) {
											echo '<li><label for="category_'.$child->id.'"><input type="checkbox" name="category_'.$child->id.'" id="category_'.$child->id.'" ';
											if (isset($_REQUEST['category_'.$child->id]) && $_REQUEST['category_'.$child->id]=='on') echo 'checked="checked"';
											echo ' /> '.$child->id.' - '.$child->name.'</label>';
											
											echo '<ul class="children">';
												$download_taxonomies->do_something_to_cat_children($child->id, 'cat_form_output_children', 'cat_form_output_no_children');
											echo '</ul>';
											
											echo '</li>';
										}
									}
									function cat_form_output_no_children() {
										echo '<li></li>';
									}
								}
								
								echo '<ul class="children">';
									$download_taxonomies->do_something_to_cat_children($c->id, 'cat_form_output_children', 'cat_form_output_no_children');
								echo '</ul>';
								
								echo '</li>';
			                }
			            } 
			        ?>
				</ul>		
				<?php
			}
		}
	}