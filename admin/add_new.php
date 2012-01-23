<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (ADD NEW DOWNLOAD)
	
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
// Changing the upload path
################################################################################

if (!function_exists('dlm_upload_dir')) {
function dlm_upload_dir( $pathdata ) {
	$subdir = '/downloads'.$pathdata['subdir'];
 	$pathdata['path'] = str_replace($pathdata['subdir'], $subdir, $pathdata['path']);
 	$pathdata['url'] = str_replace($pathdata['subdir'], $subdir, $pathdata['url']);
	$pathdata['subdir'] = str_replace($pathdata['subdir'], $subdir, $pathdata['subdir']);
	do_action('download_monitor_dlm_upload_dir', $pathdata);
	return $pathdata;
}
function dlm_upload_thumbnail_dir( $pathdata ) {
	$subdir = '/downloads/thumbnails'.$pathdata['subdir'];
 	$pathdata['path'] = str_replace($pathdata['subdir'], $subdir, $pathdata['path']);
 	$pathdata['url'] = str_replace($pathdata['subdir'], $subdir, $pathdata['url']);
	$pathdata['subdir'] = str_replace($pathdata['subdir'], $subdir, $pathdata['subdir']);
	do_action('download_monitor_dlm_upload_dir', $pathdata);
	return $pathdata;
}
}

################################################################################
// Add Download Page
################################################################################

function dlm_addnew() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_relationships,$wp_dlm_db_formats,$wp_dlm_db_meta,$download_taxonomies;

	// turn off magic quotes
	wp_dlm_magic();
	
		?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div class="wrap">

    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Add New Download',"wp-download_monitor"); ?></h2>
    <?php
    $errors = '';
    
    if ($_POST) {
		//get postdata
		$title = htmlspecialchars(trim($_POST['title']));
		$filename = htmlspecialchars(trim($_POST['filename']));									
		$dlversion = htmlspecialchars(trim($_POST['dlversion']));
		$dlhits = htmlspecialchars(trim($_POST['dlhits']));
		$postDate = $_POST['postDate'];
		$user = $_POST['user'];
		$members = (isset($_POST['memberonly'])) ? 1 : 0;
		$forcedownload = (isset($_POST['forcedownload'])) ? 1 : 0;
		if (isset($_POST['download_cat'])) $download_cat = $_POST['download_cat']; else $download_cat = '';
		$mirrors = htmlspecialchars(trim($_POST['mirrors']));
		$file_description = trim($_POST['file_description']);
    }
	if ( isset($_POST['save']) ) {
										
		//validate fields
		if (empty( $_POST['title'] )) $errors.=__('<div class="error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
		if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
		if (!is_numeric($_POST['dlhits'] )) $errors.=__('<div class="error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
		
		$tags = $_POST['tags'];
		$thumbnail = $_POST['thumbnail'];
		
		if ($thumbnail) {
			if( !strstr($thumbnail, '://' ) ) { 
			
				$pageURL = "";
				$pageURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
			
				if ($_SERVER["SERVER_PORT"] != "80") {
					$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
				} else {
					$pageURL .= $_SERVER["SERVER_NAME"];
				}		
				
				if (!strstr(get_bloginfo('url'),'www.')) $pageURL = str_replace('www.','', $pageURL );
				
				if ( ! isset($_SERVER['DOCUMENT_ROOT'] ) ) $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF']) ) );
				$dir_path = $_SERVER['DOCUMENT_ROOT'];
				$thumbnail = str_replace( $dir_path, $pageURL, $thumbnail );
			}
		}
		
		//attempt to upload file
		if ( empty($errors ) ) {										
			$time = current_time('mysql');
			$overrides = array('test_form'=>false);
			
			add_filter('upload_dir', 'dlm_upload_dir');
			
			$file = wp_handle_upload($_FILES['upload'], $overrides, $time);
			
			remove_filter('upload_dir', 'dlm_upload_dir');
	
			if ( !isset($file['error']) ) {
				$full_path = $file['url'];
				$info = $file['url'];
				$filename = $file['url'];
			} 
			else $errors = '<div class="error">'.$file['error'].'</div>';

			if ( !empty($errors ) ) {
				// No File Was uploaded
				if ( empty( $_POST['filename'] ) && !isset($_FILES['upload']) ) $errors = '<div class="error">'.__('No file selected',"wp-download_monitor").'</div>';
				elseif (!empty($_POST['filename'])) $errors = '';
			}								
		}	
		//attempt to upload thumbnail
		if ( empty($errors ) ) {										
			$time = current_time('mysql');
			$overrides = array('test_form'=>false);
			
			add_filter('upload_dir', 'dlm_upload_thumbnail_dir');
			
			$file = wp_handle_upload($_FILES['thumbnail_upload'], $overrides, $time);
			
			remove_filter('upload_dir', 'dlm_upload_thumbnail_dir');
	
			if ( !isset($file['error']) ) {
				$thumbnail = $file['url'];
			} 							
		}	
		
		//save to db
		if ( empty($errors ) ) {
	
			// Add download							
			$query_add = sprintf("INSERT INTO %s (`title`, `filename`, `dlversion`, `postDate`, `hits`, `user`, `members`, `mirrors`, `file_description`) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $_POST['title'] ),
			$wpdb->escape( $filename ),
			mysql_real_escape_string( $_POST['dlversion'] ),
			$wpdb->escape( $_POST['postDate'] ),
			mysql_real_escape_string( $_POST['dlhits'] ),
			$wpdb->escape( $_POST['user'] ),
			$wpdb->escape( $members ),
			$wpdb->escape($mirrors),
			$wpdb->escape($file_description)
			);
							
			$result = $wpdb->query($query_add);
			if ($result) {
				
				$download_insert_id = $wpdb->insert_id;
				
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
					$thetags = array_map('strtolower', $thetags);
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
				if ($thumbnail) {
					$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('thumbnail', '".$wpdb->escape( $thumbnail )."', '".$download_insert_id."')");
				}
				
				// Force Download
				$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('force', '".$wpdb->escape( $forcedownload )."', '".$download_insert_id."')");
			
				// Process and save meta/custom fields
				$index = 1;
				$values = array();
				if (isset($_POST['meta']) && is_array($_POST['meta'])) foreach ($_POST['meta'] as $meta) 
				{
					if (trim($meta['key'])) {
						$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape($meta['value']).'", '.$download_insert_id.')';
						$index ++;
					}
				}
				if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
				
				if (empty($info)) echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").'</strong></p></div>';
				else echo '<div id="message" class="updated fade"><p><strong>'.__("Download added Successfully","wp-download_monitor").' - '.$info.'</strong></p></div>';				
				do_action('download_added', $download_insert_id);
							
				// Redirect
				echo '<meta http-equiv="refresh" content="3;url=admin.php?page=dlm_addnew"/>';
				exit;
			}
			else _e('<div class="error">Error saving to database</div>',"wp-download_monitor");										
		} else echo $errors;									
		
	} 
								
	$max_upload_size_text = '';
	
	if (function_exists('ini_get')) {
		$max_upload_size = min(dlm_let_to_num(ini_get('post_max_size')), dlm_let_to_num(ini_get('upload_max_filesize')));
		$max_upload_size_text = __(' (defined in php.ini)',"wp-download_monitor");
	}
	
	if (!$max_upload_size || $max_upload_size==0) {
		$max_upload_size = 8388608;
		$max_upload_size_text = '';
	}				
		
	?>
		<form enctype="multipart/form-data" action="?page=dlm_addnew&amp;action=add&amp;method=upload" method="post" id="wp_dlm_add" name="add_download" class="form-table"> 
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_upload_size; ?>" />
            <table class="optiontable niceblue" cellpadding="0" cellspacing="0"> 
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Title',"wp-download_monitor"); ?> <?php _e('(required)',"wp-download_monitor"); ?>: </strong></th> 
                    <td>
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($title)) echo $title; ?>" name="title" id="dltitle" maxlength="200" />												
                    </td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
                    <td>
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($dlversion)) echo $dlversion; ?>" name="dlversion" id="dlversion" />
                    </td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Description',"wp-download_monitor"); ?>: </strong></th> 
                    <td><textarea style="width:360px;" name="file_description" cols="50" rows="6"><?php if (isset($file_description)) echo $file_description; ?></textarea></td> 
                </tr>
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Starting hits',"wp-download_monitor");?>: </strong></th> 
                    <td>
                        <input type="text" style="width:100px;" class="cleardefault" value="<?php if (isset($dlhits) && $dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" />
                    </td> 
                </tr>
				<tr valign="top">
						<th scope="row"><strong><?php _e('Select a file...',"wp-download_monitor"); ?> <?php _e('(required)',"wp-download_monitor"); ?></strong></th> 
						<td>
							<div style="width:820px;">
								<div style="float:left; width:362px;">
									<h3 style="margin:0 0 0.5em"><?php _e('Upload File',"wp-download_monitor"); ?></h3>
									<input type="file" name="upload" style="width:354px; margin:1px;" /><br /><span class="setting-description"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>. <?php _e('If a file with the same name already exists in the upload directly, this file will be renamed.',"wp-download_monitor"); ?></span>
								</div>
								<div style="float:left; text-align:center; width: 75px; margin: 0 8px; display:inline;">
									<p style="font-weight:bold; font-size:16px; color: #999; line-height: 48px; ">&larr;<?php _e('OR',"wp-download_monitor"); ?>&rarr;</p>
								</div>
								<div style="float:left; width:362px;">
									<h3 style="margin:0 0 0.5em"><?php _e('Enter file URL',"wp-download_monitor"); ?></h3>
									<input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($filename)) echo $filename; ?>" name="filename" id="filename" /><br />
									<?php 
									if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
									<a class="browsefiles" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                        			<div id="file_browser"></div>
                        			<?php endif; ?>
								</div>
								<div style="clear:both"></div>
							</div>
						</td>
                </tr> 
                <tr valign="top">												
                    <th scope="row"><strong><?php _e('Download Mirrors',"wp-download_monitor"); ?></strong></th> 
                    <td><textarea name="mirrors" style="width:360px;" cols="50" rows="2"><?php if (isset($mirrors)) echo $mirrors; ?></textarea><br /><span class="setting-description"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></span></td>
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
                        <input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($tags)) echo $tags; ?>" name="tags" id="dltags" /><br /><span class="setting-description"><?php _e('Separate tags with commas.',"wp-download_monitor"); ?> <a class="browsetags" style="display:none" href="#">Toggle Tags</a></span>
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
                <tr valign="middle">
                    <th scope="row"><strong><?php _e('Thumbnail',"wp-download_monitor"); ?>: </strong></th> 
					<td>
						<div style="width:820px;">
							<div style="float:left; width:362px;">
								<h3 style="margin:0 0 0.5em"><?php _e('Upload thumbnail',"wp-download_monitor"); ?></h3>
								<input type="file" name="thumbnail_upload" style="width:354px; margin:1px;" /><br /><span class="setting-description"><?php _e('This will be displayed on the download page or with {thumbnail} in a custom format (a placeholder will be shown if not set).',"wp-download_monitor"); ?></span>
							</div>
							<div style="float:left; text-align:center; width: 75px; margin: 0 8px; display:inline;">
								<p style="font-weight:bold; font-size:16px; color: #999; line-height: 48px; ">&larr;<?php _e('OR',"wp-download_monitor"); ?>&rarr;</p>
							</div>
							<div style="float:left; width:362px;">
								<h3 style="margin:0 0 0.5em"><?php _e('Enter thumbnail URL',"wp-download_monitor"); ?></h3>
								<input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($thumbnail)) echo $thumbnail; ?>" name="thumbnail" id="thumbnail" /><br />
								<?php if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
								<a class="browsefilesthumbnail" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                    			<div id="file_browser_thumbnail"></div>
                    			<?php endif; ?>
							</div>
							<div style="clear:both;"></div>
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
						if (isset($_POST['meta'])) foreach ($_POST['meta'] as $meta) 
						{
							if (!isset($meta['remove'])) {
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
						if (isset($_POST['addmeta'])) {
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
									echo '<li><strong>'.$field->meta_name.'</strong> <button type="button" class="addmeta_rel" rel="'.$field->meta_name.'">Add</button></li>';
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

            <p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Upload &amp; save',"wp-download_monitor"); ?>" /></p>
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