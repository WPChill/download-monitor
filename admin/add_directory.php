<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (ADD DIRECTORY)
	
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
// Add From Directory Download Page
################################################################################

function dlm_adddir() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$wp_dlm_db_meta,$download_taxonomies, $wp_dlm_db_relationships;

	// turn off magic quotes
	wp_dlm_magic();
	
	?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div class="wrap">

    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Add From Directory',"wp-download_monitor"); ?></h2>
    <p><?php _e('This feature lets you add downloads in bulk from a directory <strong>on your server</strong>. It will attempt to read sub-directories too. It will do its best to choose relevant titles for each download - if you need to change titles or add extra information you will have to edit the downloads afterwards.',"wp-download_monitor"); ?></p>
    <?php
    if ($_POST) {
		//get postdata
		$exe = htmlspecialchars(trim($_POST['exe']));
		$filename = htmlspecialchars(trim($_POST['filename']));									
		$postDate = $_POST['postDate'];
		$user = $_POST['user'];
		$members = (isset($_POST['memberonly'])) ? 1 : 0;
		$forcedownload = (isset($_POST['forcedownload'])) ? 1 : 0;
		if (isset( $_POST['download_cat'] )) $download_cat = $_POST['download_cat']; else $download_cat = '';
    }
	if ( isset($_POST['save']) ) {
											
		//validate fields
		if ( empty( $_POST['filename']) ) $errors.=__('<div class="error">No folder selected</div>',"wp-download_monitor");
		
		$tags = $_POST['tags'];
							
		//save to db
		if ( empty($errors ) ) {

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
			
			global $found_downloads, $extensions;
			
			$extensions = array(); 
			
			if ($exe) $extensions = explode(',',$exe);			
			
			global $found_downloads;
			
			$found_downloads = array();
			
			function find_downloads($dir) {
				global $found_downloads, $extensions;
				$root_dir = '';
				if( file_exists($root_dir . $dir) ) {
					if (function_exists('scandir')) {
						$files = scandir($root_dir . $dir);
					} else {
						$files = php4_scandir($root_dir . $dir);
					}
					if( count($files) > 2 ) { 
						// All dirs
						foreach( $files as $file ) {
							if( file_exists($root_dir . $dir . $file) && $file != '.' && $file != '..' && is_dir($root_dir . $dir . $file) ) {
								find_downloads(htmlentities($dir . $file .'/'));
							}
						}
						// All files
						foreach( $files as $file ) {
							if( file_exists($root_dir . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root_dir . $dir . $file) ) {
								$ext = preg_replace('/^.*\./', '', $file);
								if ($ext!='php' && (in_array($ext, $extensions) || sizeof($extensions)==0)) {
									$info = pathinfo($file);									
									$found_downloads[] = array(
										'path' => htmlentities($dir . $file),
										'title' => trim(ucwords(htmlentities(str_replace('_',' ',str_replace('.'.$info['extension'],'',$file)))))
									);
								}
							}
						}
					}
				} 
			}
			
			$dir = urldecode($filename);
			$dir = str_replace(get_bloginfo('wpurl').'/', ABSPATH, $dir);		
			find_downloads($dir);
			
			if (sizeof($found_downloads)>0) {
				$count = 0;
				$found_download_ids = array();
				foreach ($found_downloads as $adownload) {
					$query_add = sprintf("INSERT INTO %s (title, filename, dlversion, postDate, hits, user, members, mirrors, file_description) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
						$wpdb->escape( $wp_dlm_db ),
						$wpdb->escape( $adownload['title'] ),
						$wpdb->escape( $adownload['path'] ),
						"",
						$wpdb->escape( $_POST['postDate'] ),
						"0",
						$wpdb->escape( $_POST['user'] ),
						$wpdb->escape( $members ),
						"",
						""
					);
					$result = $wpdb->query($query_add);
					
					if ($result) {
					
						$download_insert_id = $wpdb->insert_id;
						$found_download_ids[] = $download_insert_id;
						
						// Loop Categories
						$cats = $download_taxonomies->categories;	
						$values = array();							
		                if (!empty($cats)) {
		                    foreach ( $cats as $c ) {
		                    	$this_cat_value = (isset($_POST[ 'category_'.$c->id ])) ? 1 : 0;
		                    	if ($this_cat_value) $values[] = '("'.$wpdb->escape( $c->id ).'", '.$download_insert_id.')';
		                    }
		                }
						if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ".implode(',', $values)."");
						
						// Tags
						$values = array();
						if ($tags) {
							// Break 'em up
							$thetags = explode(',', $tags);
							$thetags = array_map('trim', $thetags);
							if (sizeof($thetags)>0) {
								foreach ($thetags as $tag) {
									if ($tag) {
										// Exists?
										$tag_id = $wpdb->get_var("SELECT id FROM $wp_dlm_db_taxonomies WHERE taxonomy='tag' AND name='".$wpdb->escape($tag)."';");
										// Insert
										if (!$tag_id) {
											$wpdb->query("INSERT INTO $wp_dlm_db_taxonomies (name, parent, taxonomy) VALUES ('".$wpdb->escape($tag)."', 0, 'tag');");
											$tag_id = $wpdb->insert_id;
										}
										
										if ($tag_id) $values[] = '("'.$wpdb->escape( $tag_id ).'", '.$download_insert_id.')';
									}
								}
							}
						}
						if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ".implode(',', $values)."");
		
						// Thumbnail
						if (isset($thumbnail)) {
							$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('thumbnail', '".$wpdb->escape( $thumbnail )."', '".$download_insert_id."')");
						}
						
						// Force Download
						if (isset($forcedownload)) {
							$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('force', '".$wpdb->escape( $forcedownload )."', '".$download_insert_id."')");
						}
						
						$count ++;
					}
					
					
				}
				// Process and save meta/custom fields
				$index = 1;
				$values = array();
				if (isset($_POST['meta'])) foreach ($_POST['meta'] as $meta) 
				{
					if (trim($meta['key'])) {
						if ($found_download_ids) foreach($found_download_ids as $download_id) {
							$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape($meta['value']).'", '.$download_id.')';
							$index ++;
						}						
					}
				}
				if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
				
				wp_dlm_clear_cached_stuff();
				
				// Message
				echo '<div id="message" class="updated fade"><p><strong>'.$count.' '.__("Downloads added Successfully","wp-download_monitor").'</strong></p></div>';
				exit;
			} else echo '<div id="message" class="updated fade"><p><strong>'.__("No files found","wp-download_monitor").'</strong></p></div>';							
		} else echo $errors;									
		
	} 				
		
	?>
		<form action="?page=dlm_adddir&amp;action=add&amp;method=upload" method="post" id="wp_dlm_add" name="add_download" class="form-table">
            <table class="optiontable niceblue" cellpadding="0" cellspacing="0"> 
                <tr valign="top">
                    <th scope="row"><strong><?php _e('Directory (relative paths only)',"wp-download_monitor"); ?>:</strong></th> 
                    <td>
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($filename)) echo $filename; ?>" name="filename" id="filename" /><br/>
                        <?php if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
                        <a class="browsefiles" style="display:none" href="#"><?php _e('Toggle Folder Browser',"wp-download_monitor"); ?></a>
                        <div id="file_browser2"></div>
                        <?php endif; ?>
                    </td> 
                </tr>
                <tr valign="top">
                    <th scope="row"><strong><?php _e('Extensions',"wp-download_monitor"); ?>:</strong></th> 
                    <td>
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($exe)) echo $exe; ?>" name="exe" id="exe" /><br /><span class="setting-description"><?php _e('List extensions to look for separated by commas, or leave blank to import all. Example: zip,gif,jpg',"wp-download_monitor"); ?></a>
                    </td> 
                </tr>
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Categories',"wp-download_monitor"); ?></strong></th> 
                    <td><div id="categorydiv"><ul class="categorychecklist" style="background: #fff; border: 1px solid #DFDFDF; height: 200px; margin: 4px 1px; overflow: auto; padding: 3px 6px; width: 346px;">
                    		<?php	
	                            $cats = $download_taxonomies->get_parent_cats();
								
	                            if (!empty($cats)) {
	                                foreach ( $cats as $c ) {
	                                    echo '<li><label for="category_'.$c->id.'"><input type="checkbox" name="category_'.$c->id.'" id="category_'.$c->id.'" ';
										if (isset($_POST['category_'.$c->id])) echo 'checked="checked"';
										echo ' /> '.$c->id.' - '.$c->name.'</label>';
										
										// Do Children
										if (!function_exists('cat_form_output_children')) {
											function cat_form_output_children($child) {
												global $download_taxonomies;
												if ($child) {
													echo '<li><label for="category_'.$child->id.'"><input type="checkbox" name="category_'.$child->id.'" id="category_'.$child->id.'" ';
													if (isset($_POST['category_'.$child->id])) echo 'checked="checked"';
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
                    	</ul></div><a href="#" class="add-new-cat-ajax" style="display:none"><?php _e('+ Add New Category',"wp-download_monitor"); ?></a><div id="add_cat_form" style="display:none">
		                    <table class="niceblue small-table" cellpadding="0" cellspacing="0" style="width:362px;">
		                        <tr>
		                            <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
		                            <td><input type="text" name="cat_name" style="width: 220px" /></td>
		                        </tr>
		                        <tr>
		                            <th scope="col"><?php _e('Parent',"wp-download_monitor"); ?>:</th>
		                            <td><select name="cat_parent" style="width: 220px">
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
	                    	<p class="submit" style="padding:0 "><input type="submit" value="<?php _e('Add',"wp-download_monitor"); ?>" name="add_cat" id="add_cat" /></p>
                    	</div></td>
                </tr> 
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Tags',"wp-download_monitor"); ?>: </strong></th> 
                    <td>
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($tags)) echo $tags; ?>" name="tags" id="dltags" /><br /><span class="setting-description"><?php _e('Separate tags with commas.',"wp-download_monitor"); ?> <a class="browsetags" style="display:none" href="#"><?php _e('Toggle Tags',"wp-download_monitor"); ?></a></span>
                    	<div id="tag-list" style="display:none;">
                    		<?php
                    			$tags = $download_taxonomies->tags;
								echo '<ul>';
	                            if (!empty($tags)) {
	                                foreach ( $tags as $tag ) {
	                             		echo '<li><a href="#" class="ins-tag">'.$tag->name.'</a></li>';   
	                                }
	                            } else echo '<li>'.__('No Tags Found',"wp-download_monitor").'</li>';
	                            echo '</ul>';
                    		?>
                    	</div>
                    </td> 
                </tr>
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Member only file?',"wp-download_monitor"); ?></strong></th> 
                    <td><input type="checkbox" name="memberonly" style="vertical-align:top" <?php if (isset($members) && $members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. You can also add a custom field called min-level or req-role to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
                </tr>
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Force Download?',"wp-download_monitor"); ?></strong></th> 
                    <td><input type="checkbox" name="forcedownload" style="vertical-align:top" <?php if (isset($forcedownload) && $forcedownload==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, Download Monitor will attempt to force the download rather than redirect. This setting is not compatible with all servers (so test it), and in most cases will only work on files hosted on the local server.',"wp-download_monitor"); ?></span></td>
                </tr>
            </table>
            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>
            <p><?php _e('Custom fields can be used to add extra metadata to a download. Leave blank to add none. Name should be lower case with no spaces (changed automatically, e.g. <code>Some Name</code> will become <code>some-name</code>.',"wp-download_monitor"); ?></p>
			<table style="width:80%">
				<thead>
					<tr>
						<th class="left"><?php _e('Name',"wp-download_monitor"); ?></th>
						<th><?php _e('Value',"wp-download_monitor"); ?></th>
					</tr>			
				</thead>
				<tbody id="customfield_list">
					<?php
					$index = 1;
					if ($_POST) {
						if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
						{
							if (!$meta['remove']) {
								if (trim($meta['key'])) {
									echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',trim($meta['key'])))).'" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%">'.$meta['value'].'</textarea></td>
									</tr>';	
								}							
							}		
							$index ++;					
						}
						if ($_POST['addmeta']) {
							echo '<tr class="alternate">
									<td class="left" style="vertical-align:top;">
										<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="" type="text" style="width:95%" /><input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
									</td>
									<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td>
							</tr>';
						}
					} 											
					?>
					<tr id="addmetarow">
						<td colspan="2" class="submit"><input id="addmetasub" name="addmeta" value="<?php _e('Add Custom Field',"wp-download_monitor"); ?>" type="submit" style="margin-bottom: 6px !important;" /><br/><a class="seemetafields" style="display:none;" href="#"><?php _e('Toggle Existing Custom Fields',"wp-download_monitor"); ?></a>
						<div id="meta_fields" style="display:none">
							<?php
							$fields = $wpdb->get_results("SELECT DISTINCT meta_name FROM $wp_dlm_db_meta WHERE meta_name NOT IN ('tags', 'thumbnail', 'force') ORDER BY meta_name;");
							if ($fields) {
								echo '<ul>';
								foreach ($fields as $field) {
									echo '<li><strong>'.$field->meta_name.'</strong> <button type="button" class="addmeta_rel" rel="'.$field->meta_name.'">'._e('Add',"wp-download_monitor").'</button></li>';
								}
								echo '</ul>';
							} else {
								echo '<p>'.__('None found.',"wp-download_monitor").'</p>';
							}
							?>
						</div></td>
					</tr>
				</tbody>
			</table>
			<hr />
            
			<p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Scan &amp; Add',"wp-download_monitor"); ?>" /></p>
			<input type="hidden" name="postDate" value="<?php echo date_i18n(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
			
			<?php 
				global $userdata;
				get_currentuserinfo();										
				echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
			?>									
		</form>
	</div>
	<?php	

	echo '</div>';
}
?>