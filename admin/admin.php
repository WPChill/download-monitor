<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN
	
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
// INCLUDES
################################################################################

	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/config.php');
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/add_new.php');
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/add_directory.php');
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/categories.php');
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/logs.php');
	include_once(WP_PLUGIN_DIR.'/download-monitor/admin/dashboard.php');

################################################################################
// ADMIN HEADER
################################################################################

function wp_dlm_head() {
	global $wp_db_version, $wp_dlm_root;
	
	// Provide css based on wordpress version.
	if ($wp_db_version < 9872) {
		// 2.5 + 2.6 with new interface
		echo '<link rel="stylesheet" type="text/css" href="'.$wp_dlm_root.'css/wp-download_monitor25.css" />';
	} else {
		// 2.7
		echo '<link rel="stylesheet" type="text/css" href="'.$wp_dlm_root.'css/wp-download_monitor27.css" />';
	}	
	
	if (
		isset($_REQUEST['page']) && $_REQUEST['page']=='dlm_categories'
	) {
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
		jQuery.noConflict();
		(function($) {
		
			$(function() {
		
				function forceHelper(e,ui) {
					$(".ui-state-highlight").html("<td colspan='100%'>&nbsp;</td>");
				};
				function fixHelper(e, ui) {
					ui.children().each(function() {
						$(this).width($(this).width());
					});
					return ui;
				};

				$("#sort_dlm_cats tbody").sortable({
					axis: 'y',
					handle: '.handle:eq(0)',
					forcePlaceholderSize: true,
					placeholder: 'ui-state-highlight',
					helper: fixHelper,
					opacity: 0.6,
					change: function(e, ui) {
			        	forceHelper(e,ui);
			      	},
			      	update: function(event, ui) {
			      		// Save Position to DB
			      		var data = $(this).sortable( 'serialize' );
			      		$.post("<?php echo $wp_dlm_root.'admin/'; ?>save_order.php", data, function(data){
						}, 'text');
			      	}
				}).disableSelection();
				
				$("#sort_dlm_cats td ul").sortable({
					axis: 'y',
					handle: '.handle:eq(0)',
					forcePlaceholderSize: true,
					opacity: 0.6,
			      	update: function(event, ui) {
			      		// Save Position to DB
			      		var data = $(this).sortable( 'serialize' );
			      		$.post("<?php echo $wp_dlm_root.'admin/'; ?>save_order.php", data, function(data){
						}, 'text');
			      	}
				}).disableSelection();
				
				$('a.delete_cat').click(function(){
					var answer = confirm("<?php _e('Are you sure you want to delete',"wp-download_monitor"); ?> " + $(this).attr('rel') + '?')
					if (answer){
						return true;
					}
					else{
						return false;
					}					
				});
				
			});
		
		})(jQuery);

	/* ]]> */
	</script>
	<?php
	}	
	
	if ( isset($_REQUEST['page']) && (
		$_REQUEST['page']=='dlm_addnew' ||
		$_REQUEST['page']=='dlm_adddir' ||
		$_REQUEST['page']=='download-monitor/wp-download_monitor.php' ||
		$_REQUEST['page']=='download-monitor/wp-download_monitor.php'
		)
	) {
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.css" />
	<script type="text/javascript" src="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.js"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
		
		jQuery.noConflict();
		(function($) { 
		  $(function() {
		  	
		  	<?php if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
		  	
		    $('#file_browser').hide().fileTree({
		      root: '<?php echo apply_filters( 'file_browser_root', get_option('wp_dlm_file_browser_root') ); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTree.php',
		    }, function(file) {
		        //var path = file.replace('<?php echo ABSPATH; ?>', '<?php bloginfo('wpurl'); ?>/');
		        var path = file;
		        $('#filename, #dlfilename').val(path);
		        $('#file_browser').slideToggle();
		    });
		    
		     $('#file_browser_thumbnail').hide().fileTree({
		      root: '<?php echo apply_filters( 'file_browser_root', get_option('wp_dlm_file_browser_root') ); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTree.php',
		    }, function(file) {
		        var path = file.replace('<?php echo ABSPATH; ?>', '<?php bloginfo('wpurl'); ?>/');
		        $('#thumbnail').val(path);
		        $('#file_browser_thumbnail').slideToggle();
		    });
		    
		    $('#file_browser2').hide().fileTree({
		      root: '<?php echo apply_filters( 'file_browser_root', get_option('wp_dlm_file_browser_root') ); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTreeDir.php',
		    }, function(file) {
		        //var path = file.replace('<?php echo ABSPATH; ?>', '<?php bloginfo('wpurl'); ?>/');
		        var path = file;
		        $('#filename, #dlfilename').val(path);
		        $('#file_browser2').slideToggle();
		    });
		    
		    $('a.browsefiles').show().click(function(){
		    	$('#file_browser, #file_browser2').slideToggle();
		    	return false;
		    });
		    
		    $('a.browsefilesthumbnail').show().click(function(){
		    	$('#file_browser_thumbnail').slideToggle();
		    	return false;
		    });
		    
		    <?php endif; ?>
		    
		    $('a.browsetags').show().click(function(){
		    	$('#tag-list').slideToggle();
		    	return false;
		    });
		    
		    $('a.seemetafields').show().click(function(){
		    	$('#meta_fields').slideToggle();
		    	return false;
		    }); 
		    
		    $('button.addmeta_rel').click(function(){
		    	$('#addmetasub').click();
		    	$('#customfield_list tr.alternate:last input:eq(0)').val( $(this).attr('rel') );
		    	return false;
		    });
		    
		    $('a.add-new-cat-ajax').show().click(function(){
		    	$('#add_cat_form input:eq(0)').val('');
		    	$('#add_cat_form select option').attr('selected', false);
		    	$('#add_cat_form select option:eq(0)').attr('selected', 'selected');
		    	$('#add_cat_form').slideToggle();
		    	return false;
		    });
		    
		    $('#add_cat_form #add_cat').click(function(){
		    	
		    	$.post("<?php echo $wp_dlm_root.'admin/'; ?>add_category.php?" + $("form .categorychecklist input").serialize(), { add_cat: "true", cat_name: $('#add_cat_form input:eq(0)').val(), cat_parent: $('#add_cat_form select').val() }, function(data) {
		    		if (data) $("#categorydiv").html(data);
		    		
		    		$("select[name='cat_parent']").parent().load("admin.php?page=dlm_addnew select[name='cat_parent']");
		    		
		    		$('a.add-new-cat-ajax').click();
		    	}, 'text' );
		    	return false;
		    	
		    });
		    
		    $('a.ins-tag').click(function(){
		    	var value = $('#dltags').val();
		    	var tag = $(this).text();
		    	
		    	// Check dupes
				var exploded = value.split(',');
				
				var found = false;
				
				for (i=0;i<exploded.length;i++) {
					
					var thistag = $.trim(exploded[i]);

					if (thistag==tag) {
						found = true;
					}
				}

		    	if (found == false) {
			    	if (value == '') {
			    		$('#dltags').val(tag);
			    	} else {
			    		$('#dltags').val(value + ', ' + tag);
			    	}
		    	}	    	
		    	return false;
		    });
		  										  	
		  	$('#customfield_list tr.alternate').each(function(i){
		  	
		  		var index = i + 1;
		  		$("input[name='meta[" + index + "][remove]']").click(function(){
		    		$('input, textarea', $(this).parent().parent()).val('');
		    		$(this).parent().parent().hide();
		    		return false;
		    	});
		  	
		  	});										  	
		    
		    $('#addmetasub').click(function(){
		    
		    	var newfield = $('#customfield_list tr.alternate').size() + 1;
		    	
		    	$('#addmetarow').before('<tr class="alternate"><td class="left" style="vertical-align:top;"><label class="hidden" for="meta[' + newfield + '][key]">Key</label><input name="meta[' + newfield +'][key]" id="meta[' + newfield +'][key]" tabindex="6" size="20" value="" type="text" style="width:95%" /><input type="submit" name="meta[' + newfield +'][remove]" class="button" value="<?php _e('remove',"wp-download_monitor"); ?>" /></td><td style="vertical-align:top;"><label class="hidden" for="meta[' + newfield + '][value]">Value</label><textarea name="meta[' + newfield + '][value]" id="meta[' + newfield + '][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td></tr>');									    	
		    	
		    	$("input[name='meta[" + newfield + "][remove]']").click(function(){
		    		$('input, textarea', $(this).parent().parent()).val('');
		    		$(this).parent().parent().hide();
		    		return false;
		    	});
		    	
		    	return false;
		    	
		    });
		    
		  });
		})(jQuery);

	/* ]]> */
	</script>
	<?php
	}
}
add_action('admin_head', 'wp_dlm_head');

################################################################################
// MAIN ADMIN PAGE
################################################################################

function wp_dlm_admin()
{
	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$wp_dlm_db_stats, $wp_dlm_db_meta, $wp_dlm_db_log, $wp_dlm_db_relationships,$download_taxonomies;

	// turn off magic quotes
	wp_dlm_magic();

	echo '<div class="download_monitor">';
	
	// DEFINE QUERIES
	
	// select all downloads
	if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;
		
	// select a downloads
	if (isset($_GET['id'])) {
		$query_select_1 = sprintf("SELECT * FROM %s WHERE id=%s;",
			$wpdb->escape( $wp_dlm_db ),
			$wpdb->escape( $_GET['id'] ));	
	}
	
	if (isset($_GET['action'])) $action = $_GET['action']; else $action = '';
	if (!empty($action)) {
		switch ($action) {
				case "delete" :
					wp_dlm_clear_cached_stuff();
					$d = $wpdb->get_row($query_select_1);
					global $wp_db_version;
					$adminpage = 'admin.php';
					?>
						<div class="wrap">
							<div id="downloadadminicon" class="icon32"><br/></div>
							<h2><?php _e('Sure?',"wp-download_monitor"); ?></h2>
							<p><?php _e('Are you sure you want to delete',"wp-download_monitor"); ?> "<?php echo $d->title; ?>"<?php _e('? (If originally uploaded by this plugin, this will also remove the file from the server)',"wp-download_monitor"); ?> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/<?php echo $adminpage; ?>?page=download-monitor/wp-download_monitor.php&amp;action=confirmed&amp;id=<?php echo $_GET['id']; ?>&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[yes]',"wp-download_monitor"); ?></a> <a href="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/<?php echo $adminpage; ?>?page=download-monitor/wp-download_monitor.php&amp;action=cancelled&amp;sort=<?php echo $_GET['sort']; ?>&amp;p=<?php echo $_GET['p']; ?>"><?php _e('[no]',"wp-download_monitor"); ?></a>
						</div>
					<?php					
				break;
				case "edit" :
					wp_dlm_clear_cached_stuff();
					if ( isset($_POST['sub']) ) {
						$title = $_POST['title'];
						$dlversion = $_POST['dlversion'];
						$dlhits = $_POST['dlhits'];
						$dlfilename = $_POST['dlfilename'];
						$members = (isset($_POST['memberonly'])) ? 1 : 0;
						$removefile = (isset($_POST['removefile'])) ? 1 : 0;
						$mirrors = $_POST['mirrors'];
						$file_description = $_POST['file_description'];
						if (isset($_POST['meta'])) $custom_fields = $_POST['meta']; else $custom_fields = '';
						if (isset($_POST['tags'])) $download_tags = $_POST['tags']; else $download_tags = '';
						$thumbnail = $_POST['thumbnail'];
						
						/* Get Post Date Fields */
						$postDate = $_POST['postDate'];
						$mm = $_POST['mm'];
						$jj = $_POST['jj'];
						$aa = $_POST['aa'];
						$hh = $_POST['hh'];
						$mn = $_POST['mn'];	
						
						if (
							($mm > 0 && $mm < 13) &&
							($jj > 0 && $jj < 32) &&
							($aa > 1990 && $jj < 2100) &&
							(isset($hh) && $hh < 25) &&
							(isset($mn) && $mn < 61)
						) {
							// Good to go
							$newPostDate = "$aa-$mm-$jj $hh-$mn-00";
						} else {
							// Bad Date
							$newPostDate = $postDate;	
						}									
						/* End Date Fields */
						
						if ( $_POST['save'] )
						{
							//save and validate
							if (empty( $_POST['title'] )) $errors.='<div class="error">'.__('Required field: <strong>Title</strong> omitted',"wp-download_monitor").'</div>';
							if (empty( $_POST['dlfilename'] ) && empty($_FILES['upload']['tmp_name'])) $errors.='<div class="error">'.__('Required field: <strong>THE FILE</strong> omitted',"wp-download_monitor").'</div>';						
							if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
							if (!is_numeric($_POST['dlhits'] )) $errors.='<div class="error">'.__('Invalid <strong>hits</strong> entered',"wp-download_monitor").'</div>';
							
							$members = (isset($_POST['memberonly'])) ? 1 : 0;
							
							$removefile = (isset($_POST['removefile'])) ? 1 : 0;
							
							$forcedownload = (isset($_POST['forcedownload'])) ? 1 : 0;
								
							if (empty($errors)) {

								 // Remove Old Taxonomies
				                $wpdb->query("DELETE FROM $wp_dlm_db_relationships WHERE download_id = ".$wpdb->escape($_GET['id'])."");
				                
								// Categories
								$cats = $download_taxonomies->categories;	
								$values = array();							
				                if (!empty($cats)) {
				                    foreach ( $cats as $c ) {
				                    	$this_cat_value = (isset($_POST[ 'category_'.$c->id ])) ? 1 : 0;
				                    	if ($this_cat_value) $values[] = '("'.$wpdb->escape( $c->id ).'", '.$wpdb->escape($_GET['id']).')';
				                    }
				                }				               
								if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ".implode(',', $values)."");
								
								// Tags
								$values = array();
								if ($download_tags) {
									// Break 'em up
									$thetags = explode(',', $download_tags);
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
												
												if ($tag_id) $values[] = '("'.$wpdb->escape( $tag_id ).'", '.$wpdb->escape($_GET['id']).')';
											}
										}
									}
								}
								if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ".implode(',', $values)."");
				
								// Handle File Uploads										
								$time = current_time('mysql');
								$overrides = array('test_form'=>false);
								
								// Remove old file
								if ($removefile){		
									$d = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wp_dlm_db WHERE id=%s;", $_GET['id'] ) );
									$file = $d->filename;
									$dirs = wp_upload_dir();
									$uploadpath 	= trailingslashit( $dirs['baseurl'] );  
									$absuploadpath 	= trailingslashit( $dirs['basedir'] );
									if ( $uploadpath && ( strstr ( $d->filename, $uploadpath ) || strstr ( $d->filename, $absuploadpath )) ) {
					
										$file = str_replace( $uploadpath , "" , $d->filename);
										if(is_file($absuploadpath.$file)){
												chmod($absuploadpath.$file, 0777);  
												unlink($absuploadpath.$file);								
										}					    
									}									
								}
								
								add_filter('upload_dir', 'dlm_upload_dir');
								
								$file = wp_handle_upload($_FILES['upload'], $overrides, $time);
								
								remove_filter('upload_dir', 'dlm_upload_dir');
						
								if ( !isset($file['error']) ) {
									$dlfilename = $file['url'];
								} 
								else $errors = '<div class="error">'.$file['error'].'</div>';
					
								if ( !empty($errors ) ) {
									// No File Was uploaded
									if ( empty( $_POST['dlfilename']) ) $errors = __('<div class="error">No file selected</div>',"wp-download_monitor");
									else $errors = '';
								}				
								//attempt to upload thumbnail
								if ( empty($errors ) ) {										
									
									add_filter('upload_dir', 'dlm_upload_thumbnail_dir');
									
									$file = wp_handle_upload($_FILES['thumbnail_upload'], $overrides, $time);
									
									remove_filter('upload_dir', 'dlm_upload_thumbnail_dir');
							
									if ( !isset($file['error']) ) {
										$thumbnail = $file['url'];
									} 							
								}	
								//save to db
								if ( empty($errors ) ) {

									$query_update_file = sprintf("UPDATE %s SET title='%s', dlversion='%s', hits='%s', filename='%s', postDate='%s', user='%s',members='%s',mirrors='%s', file_description='%s' WHERE id=%s;",
										$wpdb->escape( $wp_dlm_db ),
										$wpdb->escape( $title ),
										mysql_real_escape_string( $dlversion ),
										mysql_real_escape_string( $dlhits ),
										$wpdb->escape( $dlfilename ),
										$wpdb->escape( $newPostDate ),
										$wpdb->escape( $_POST['user'] ),
										$wpdb->escape( $members ),
										$wpdb->escape( trim($mirrors) ) ,
										$wpdb->escape( trim($file_description) ) ,
										$wpdb->escape( $_GET['id'] ));
	
									$d = $wpdb->get_row($query_update_file);
									$show=true;
											
									// Process and save meta/custom fields	
									$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id = ".$_GET['id']."");
									
									// Thumbnail
									if ($thumbnail) {
										$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('thumbnail', '".$wpdb->escape( $thumbnail )."', '".$wpdb->escape($_GET['id'])."')");
									}
									
									// Force Download
									$wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('force', '".$wpdb->escape( $forcedownload )."', '".$wpdb->escape($_GET['id'])."')");										
									
									// Custom Fields								
									$index = 1;
									$values = array();
									if (isset($_POST['meta'])) foreach ($_POST['meta'] as $meta) 
									{
										if (trim($meta['key'])) {
											$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape($meta['value']).'", '.$_GET['id'].')';
											$index ++;
										}
									}
									if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
									
									echo '<div id="message" class="updated fade"><p><strong>'.__('Download edited Successfully',"wp-download_monitor").'</strong></p></div>';
								}							
							} 
							if (!empty($errors)) {
								echo $errors;								
							}
						}
					}
					else 
					{					
						//load values
						$d = $wpdb->get_row($query_select_1);
						$title = $d->title;
						$dlversion = $d->dlversion;
						$dlhits = $d->hits;
						$dlfilename = $d->filename;
						if (empty( $dlhits )) $dlhits = 0;
						$members = $d->members;
						
						global $categories;
						$categories = array();
						$categories_taxonomy = get_download_taxonomy($d->id);
						if (sizeof($categories_taxonomy['ids'])>0) $categories = $categories_taxonomy['ids'];
						
						global $download_tags;
						$download_tags = '';
						$tags_taxonomy = get_download_taxonomy($d->id, 'tag');
						if (sizeof($tags_taxonomy['names'])>0) $download_tags = implode(', ', $tags_taxonomy['names']);
						
						$mirrors =  $d->mirrors;
						$file_description = $d->file_description;
						$fields = $wpdb->get_results("SELECT * FROM $wp_dlm_db_meta WHERE download_id= ".$d->id."");
						$index=1;
						$custom_fields = array();
						$thumbnail = '';
						if ($fields) foreach ($fields as $meta) 
						{
							if ($meta->meta_name=='thumbnail') {
								$thumbnail = stripslashes($meta->meta_value);
							} elseif ($meta->meta_name=='force') {
								$forcedownload = stripslashes($meta->meta_value);
							} elseif ($meta->meta_name=='filesize') {
								// Nothing
							} else {
								$custom_fields[$index]['key'] = $meta->meta_name;
								$custom_fields[$index]['value'] = stripslashes($meta->meta_value);
								$custom_fields[$index]['remove'] = 0;
								$index++;
							}
						}
						$postDate = date_i18n('Y-m-d H:i:s', strtotime($d->postDate));
						$newPostDate = $d->postDate;
					}	
					
					if (!isset($show)) $show = false;
					
					if ($show==false) {
											
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
								<div class="wrap">
								<div id="downloadadminicon" class="icon32"><br/></div>
								<h2><?php _e('Edit Download Information',"wp-download_monitor"); ?></h2>
								<form enctype="multipart/form-data" action="?page=download-monitor/wp-download_monitor.php&amp;action=edit&amp;id=<?php echo $_GET['id']; ?>" method="post" id="wp_dlm_add" name="edit_download" class="form-table" cellpadding="0" cellspacing="0"> 
									<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_upload_size; ?>" />

									<table class="optiontable niceblue">                     
										<tr valign="top">
											<th scope="row"><strong><?php _e('Title (required)',"wp-download_monitor"); ?>: </strong></th> 
											<td>
												<input type="text" style="width:360px;" class="cleardefault" value="<?php echo $title; ?>" name="title" id="dlmtitle" maxlength="200" />												
											</td> 
										</tr>
										<tr valign="top">
											<th scope="row"><strong><?php _e('Version',"wp-download_monitor"); ?>: </strong></th> 
											<td>
												<input type="text" style="width:360px;" class="cleardefault" value="<?php echo $dlversion; ?>" name="dlversion" id="dlversion" maxlength="200" />
											</td> 
										</tr>
										<tr valign="middle">
                                            <th scope="row"><strong><?php _e('Description',"wp-download_monitor"); ?>: </strong></th> 
                                            <td><textarea name="file_description" style="width:360px;" cols="50" rows="6"><?php echo $file_description; ?></textarea></td> 
                                        </tr>
										<tr valign="top">
											<th scope="row"><strong><?php _e('Change hit count',"wp-download_monitor"); ?>: </strong></th> 
											<td>
												<input type="text" style="width:100px;" class="cleardefault" value="<?php echo $dlhits; ?>" name="dlhits" id="dlhits" maxlength="50" />
											</td> 
										</tr>
										<tr valign="top">
											<th scope="row"><strong><?php _e('Change post date',"wp-download_monitor"); ?>: </strong></th> 
											<td>
												<?php dlm_touch_time($newPostDate); ?>
												<br/><span class="setting-description"><?php _e('Change the post date of the download. Was set to:',"wp-download_monitor"); ?> <?php echo date_i18n(__('M jS Y @ H:i',"wp-download_monitor"), strtotime($postDate)) ;?></span>
											</td> 
										</tr>
										<tr valign="top">
												<th scope="row"><strong><?php _e('The File',"wp-download_monitor"); ?> <?php _e('(required)',"wp-download_monitor"); ?></strong></th> 
												<td>
													<div style="width:820px;">
														<div style="float:left; width:362px;">
															<h3 style="margin:0 0 0.5em"><?php _e('Upload New File',"wp-download_monitor"); ?></h3>
															<input type="file" name="upload" style="width:354px; margin:1px;" /><br />
															<input type="checkbox" name="removefile" id="removefile" style="vertical-align:middle" <?php if (isset($removefile) && $removefile==1) echo "checked='checked'"; ?> /> <label for="removefile"><?php _e('Remove old file?',"wp-download_monitor"); ?></label>
															<span class="setting-description"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>. <?php _e('If a file with the same name already exists in the upload directly, this file will be renamed.',"wp-download_monitor"); ?></span>															
														</div>
														<div style="float:left; text-align:center; width: 75px; margin: 0 8px; display:inline;">
															<p style="font-weight:bold; font-size:16px; color: #999; line-height: 48px; ">&larr;<?php _e('OR',"wp-download_monitor"); ?>&rarr;</p>
														</div>
														<div style="float:left; width:362px;">
															<h3 style="margin:0 0 0.5em"><?php _e('Edit File URL',"wp-download_monitor"); ?></h3>
															<input type="text" style="width:360px;" class="cleardefault" value="<?php echo $dlfilename; ?>" name="dlfilename" id="dlfilename" /><br />
															<?php if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
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
											<td><textarea name="mirrors" cols="50" rows="2"><?php echo $mirrors; ?></textarea><br /><span class="setting-description"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></span></td>
										</tr>											
						                <tr valign="top">												
						                    <th scope="row"><strong><?php _e('Categories',"wp-download_monitor"); ?></strong></th> 
						                    <td><div id="categorydiv"><ul class="categorychecklist" style="background: #fff; border: 1px solid #DFDFDF; height: 200px; margin: 4px 1px; overflow: auto; padding: 3px 6px; width: 346px;">
						                    		<?php	
							                            $cats = $download_taxonomies->get_parent_cats();
														
							                            if (!empty($cats)) {
							                                foreach ( $cats as $c ) {
							                                    echo '<li><label for="category_'.$c->id.'"><input type="checkbox" name="category_'.$c->id.'" id="category_'.$c->id.'" ';
																if (isset($_POST['category_'.$c->id]) || (is_array($categories) && in_array($c->id, $categories))) echo 'checked="checked"';
																echo ' /> '.$c->id.' - '.$c->name.'</label>';
																
																// Do Children
																if (!function_exists('cat_form_output_children')) {
																	function cat_form_output_children($child) {
																		global $download_taxonomies, $categories;
																		if ($child) {
																			echo '<li><label for="category_'.$child->id.'"><input type="checkbox" name="category_'.$child->id.'" id="category_'.$child->id.'" ';
																			if (isset($_POST['category_'.$child->id]) || (is_array($categories) && in_array($child->id,$categories))) echo 'checked="checked"';
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
						                        <input type="text" style="width:360px;" class="cleardefault" value="<?php echo $download_tags; ?>" name="tags" id="dltags" /><br /><span class="setting-description"><?php _e('Separate tags with commas.',"wp-download_monitor"); ?> <a class="browsetags" style="display:none" href="#"><?php _e('Toggle Tags',"wp-download_monitor"); ?></a></span>
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
														<h3 style="margin:0 0 0.5em"><?php _e('Upload new thumbnail',"wp-download_monitor"); ?></h3>
														<input type="file" name="thumbnail_upload" style="width:354px; margin:1px;" /><br /><span class="setting-description"><?php _e('This will be displayed on the download page or with {thumbnail} in a custom format (a placeholder will be shown if not set).',"wp-download_monitor"); ?></span>
													</div>
													<div style="float:left; text-align:center; width: 75px; margin: 0 8px; display:inline;">
														<p style="font-weight:bold; font-size:16px; color: #999; line-height: 48px; ">&larr;<?php _e('OR',"wp-download_monitor"); ?>&rarr;</p>
													</div>
													<div style="float:left; width:362px;">
														<h3 style="margin:0 0 0.5em"><?php _e('Edit thumbnail URL',"wp-download_monitor"); ?></h3>
														<input type="text" style="width:360px;" class="cleardefault" value="<?php echo $thumbnail; ?>" name="thumbnail" id="thumbnail" /><br />
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
						                    <td><input type="checkbox" name="memberonly" style="vertical-align:top" <?php if ($members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. You can also add a custom field called min-level or req-role to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
						                </tr>
						                <tr valign="top">												
						                    <th scope="row"><strong><?php _e('Force Download?',"wp-download_monitor"); ?></strong></th> 
						                    <td><input type="checkbox" name="forcedownload" style="vertical-align:top" <?php if ($forcedownload==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, Download Monitor will attempt to force the download rather than redirect. This setting is not compatible with all servers (so test it), and in most cases will only work on files hosted on the local server.',"wp-download_monitor"); ?></span></td>
						                </tr>
									</table>
									<input type="hidden" name="sort" value="<?php echo $_REQUEST['sort']; ?>" />
									<input type="hidden" name="p" value="<?php echo $_REQUEST['p']; ?>" />
									<input type="hidden" name="sub" value="1" />
									<input type="hidden" name="postDate" value="<?php echo $postDate; ?>" />
									<?php 
										global $userdata;
										get_currentuserinfo();										
										echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
									?>
									<hr/>
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
											if ($custom_fields) foreach ($custom_fields as $meta) 
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
											if (isset($_POST['addmeta'])) {
												echo '<tr class="alternate">
														<td class="left" style="vertical-align:top;">
															<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="" type="text" style="width:95%">
															<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
														</td>
														<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td>
												</tr>';
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
									<p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
								</form>
								</div>													
							<?php	
					}
				
				break;
				case "confirmed" :
					wp_dlm_clear_cached_stuff();
					//load values
					$d = $wpdb->get_row($query_select_1);
					$file = $d->filename;
					$dirs = wp_upload_dir();
					$uploadpath 	= trailingslashit( $dirs['baseurl'] );  
					$absuploadpath 	= trailingslashit( $dirs['basedir'] );
					if ( $uploadpath && ( strstr ( $d->filename, $uploadpath ) || strstr ( $d->filename, $absuploadpath )) ) {
	
						$file = str_replace( $uploadpath , "" , $d->filename);
						if(is_file($absuploadpath.$file)){
								chmod($absuploadpath.$file, 0777);  
								unlink($absuploadpath.$file);								
						}					    
					}					
					$query_delete = "DELETE FROM $wp_dlm_db WHERE id=".$wpdb->escape( $_GET['id'] ).";";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_stats WHERE download_id=".$wpdb->escape( $_GET['id'] ).";";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_log WHERE download_id=".$wpdb->escape( $_GET['id'] ).";";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_meta WHERE download_id=".$wpdb->escape( $_GET['id'] ).";";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_relationships WHERE download_id=".$wpdb->escape( $_GET['id'] ).";";
					$wpdb->query($query_delete);				

					echo '<div id="message" class="updated fade"><p><strong>'.__('Download deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					
					// Truncate table if empty
					$q=$wpdb->get_results("select * from $wp_dlm_db;");
					if ( empty( $q ) ) {
						$wpdb->query("TRUNCATE table $wp_dlm_db");
					}
					$show=true;
				break;
				case "cancelled" :
					$show=true;
				break;
		}
	}
	
	/* Bulk Editing */
	if (isset($_POST['dobulkaction']) || isset($_POST['dobulkaction2'])) {
		if (isset($_POST['dobulkaction'])) $action = $_POST['bulkactions'];
		elseif (isset($_POST['dobulkaction2'])) $action = $_POST['bulkactions2'];
		if (isset($_POST['check'])) $checked = $_POST['check']; else $checked = '';
		$bulk_ids = array();
		if ($checked && is_array($checked)) foreach ($checked as $key=>$value){
			if (key($value) && key($value)>0) $bulk_ids[] = key($value);
		} elseif ($checked) {
			$bulk_ids = explode(',',$checked);
		}
		if (!$action || sizeof($bulk_ids)==0) {
			// No action selected/or no downloads selected
			$show=true;
		} elseif ($action=='reset') {
			// Reset Stats of selected downloads
			wp_dlm_clear_cached_stuff();
			foreach ($bulk_ids as $bid) {
				if (is_numeric($bid) && $bid>0) {
					$wpdb->query( $wpdb->prepare( "UPDATE $wp_dlm_db SET hits=0 WHERE id=%s;", $bid ) );
				}
			}
			
			echo '<div id="message" class="updated fade"><p><strong>'.__('Stats successfully reset for selected downloads',"wp-download_monitor").'</strong></p></div>';
				
			$show=true;
			
		} elseif ($action=='delete') {
			// Delete selected downloads
			wp_dlm_clear_cached_stuff();
			foreach ($bulk_ids as $bid) {
				
				if (is_numeric($bid) && $bid>0) {
				
					$d = $wpdb->get_row( "SELECT * FROM $wp_dlm_db WHERE id=$bid;" );
					$file = $d->filename;
					$dirs = wp_upload_dir();
					$uploadpath 	= trailingslashit( $dirs['baseurl'] );  
					$absuploadpath 	= trailingslashit( $dirs['basedir'] );
					if ( $uploadpath && ( strstr ( $d->filename, $uploadpath ) || strstr ( $d->filename, $absuploadpath )) ) {
	
						$file = str_replace( $uploadpath , "" , $d->filename);
						if(is_file($absuploadpath.$file)){
								chmod($absuploadpath.$file, 0777);  
								unlink($absuploadpath.$file);								
						}					    
					}
					$query_delete = "DELETE FROM $wp_dlm_db WHERE id=$bid;";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_stats WHERE download_id=$bid;";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_log WHERE download_id=$bid;";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_meta WHERE download_id=$bid;";
					$wpdb->query($query_delete);
					
					$query_delete = "DELETE FROM $wp_dlm_db_relationships WHERE download_id=$bid;";
					$wpdb->query($query_delete);
				
				}
			}					

			echo '<div id="message" class="updated fade"><p><strong>'.__('Selected Downloads deleted Successfully',"wp-download_monitor").'</strong></p></div>';
			
			// Truncate table if empty
			$q=$wpdb->get_results("select * from $wp_dlm_db;");
			if ( empty( $q ) ) {
				$wpdb->query("TRUNCATE table $wp_dlm_db");
			}
			$show=true;
		} elseif ($action=='edit'){
			// Show edit form instead
			$show=false;
			$show_edit = true;
			if (isset($_POST['meta'])) $custom_fields = $_POST['meta']; else $custom_fields = '';
			if (isset($_POST['save'])) {
				// get values
				if (isset($_POST['download_cat'])) $download_cat = $_POST['download_cat']; else $download_cat = '';
				$change_memberonly = $_POST['change_memberonly'];
				$change_forcedownload = $_POST['change_forcedownload'];
				$members = (isset($_POST['memberonly'])) ? 1 : 0;
				$forcedownload = (isset($_POST['forcedownload'])) ? 1 : 0;
				$change_customfields = $_POST['change_customfields'];
				
				$change_tags = $_POST['change_tags'];
				$change_cats = $_POST['change_cats'];
				$change_thumbnail = $_POST['change_thumbnail'];
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
				
				// save options
				$queries = array();
				if ($change_memberonly==1) {
					$queries[] = " members='".$wpdb->escape($members)."' ";
				}
				if (sizeof($queries)>0) {
					$wpdb->query( "UPDATE $wp_dlm_db SET ".implode(', ', $queries)." WHERE id IN (".$wpdb->escape( implode(', ',$bulk_ids) ).");" );
				}
				
				$taxonomy_ids = array();	
					
				if ($change_cats==1) {
					
					$cats = $download_taxonomies->categories;						
	                if (!empty($cats)) {
	                    foreach ( $cats as $c ) {
	                    	$this_cat_value = (isset($_POST[ 'category_'.$c->id ])) ? 1 : 0;
	                    	foreach($bulk_ids as $bid) {
								$wpdb->query("DELETE FROM $wp_dlm_db_relationships WHERE taxonomy_id = '".$c->id."' AND download_id = ".$bid."");
							}
	                    	if ($this_cat_value) $taxonomy_ids[] = $c->id;
	                    }
	                }
	                if (sizeof($cat_ids)>0) {
						foreach($cat_ids as $cat) {
							foreach($bulk_ids as $bid) {
								$wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ('".$cat."' , ".$bid.")");
							}
						}
					}
				}
				if ($change_tags==1) {
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
									foreach($bulk_ids as $bid) {
										$wpdb->query("DELETE FROM $wp_dlm_db_relationships WHERE taxonomy_id = '".$tag_id."' AND download_id = ".$bid."");
									}
									if ($tag_id) $taxonomy_ids[] = $tag_id;
								}
							}
						}
					}									
				}
			 	if (sizeof($taxonomy_ids)>0) {
					foreach($taxonomy_ids as $tax) {
						foreach($bulk_ids as $bid) {
							$wpdb->query("INSERT INTO $wp_dlm_db_relationships (taxonomy_id, download_id) VALUES ('".$tax."' , ".$bid.")");
						}
					}
				}
				
				if ($change_thumbnail==1) {
					$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id IN (".$wpdb->escape( implode(', ',$bulk_ids) ).") AND meta_name IN ('thumbnail')");
					foreach($bulk_ids as $bid) {
						if ($thumbnail) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('thumbnail', '".$thumbnail."' , ".$bid.")");
					}
				}
				if ($change_forcedownload==1) {
					$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id IN (".$wpdb->escape( implode(', ',$bulk_ids) ).") AND meta_name IN ('force')");
					foreach($bulk_ids as $bid) {
						if ($forcedownload) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ('force', '".$forcedownload."' , ".$bid.")");
					}	
				}
				// Process and save meta/custom fields
				if ($change_customfields>0) {
					if ($change_customfields==1) {
						
						$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id IN (".$wpdb->escape( implode(', ',$bulk_ids) ).") AND meta_name NOT IN ('tags','thumbnail','force')");

					} elseif ($change_customfields==2) {
					
						// Get posted meta names
						$meta_names = array();
						if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
						{
							if (trim($meta['key'])) {
								$meta_names[] = "'".$wpdb->escape(strtolower((str_replace(' ','-',trim($meta['key'])))))."'";
							}
						}
					
						$wpdb->query("DELETE FROM $wp_dlm_db_meta WHERE download_id IN (".$wpdb->escape( implode(', ',$bulk_ids) ).") AND meta_name IN (".implode(',', $meta_names).")");
					
					}
					$values = array();
					if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
					{
						if (trim($meta['key'])) {
							foreach($bulk_ids as $bid) {
								$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape($meta['value']).'", '.$bid.')';
							}
						}
					}
					if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
				}		
											
				echo '<div id="message" class="updated fade"><p><strong>'.__('Downloads edited successfully',"wp-download_monitor").'</strong></p></div>';
								
				// hide edit form - show downloads
				$show_edit = false;
				$show=true;
			}
			if ($show_edit==true) :
			?>
			<div class="wrap">
			<div id="downloadadminicon" class="icon32"><br/></div>
				<h2><?php _e('Bulk Edit Downloads',"wp-download_monitor"); ?></h2>
				<p><?php _e('Editing downloads with id\'s:',"wp-download_monitor"); ?> <code><?php echo implode(', ',$bulk_ids); ?></code>. <?php _e('Adding options here will overwrite the options in ALL of the selected downloads.',"wp-download_monitor"); ?></p>
				<form action="admin.php?page=download-monitor/wp-download_monitor.php" method="post" id="wp_dlm_add" name="edit_download" class="form-table" cellpadding="0" cellspacing="0"> 

					<table class="optiontable niceblue">    
					
					
		                <tr valign="top">												
		                    <th scope="row"><strong><?php _e('Categories',"wp-download_monitor"); ?></strong></th> 
		                    <td>
		                    	<select name="change_cats" style="vertical-align:middle">
                            		<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            		<option value="1"><?php _e('Change to &darr;',"wp-download_monitor"); ?></option>
                            	</select>
                            	<div id="categorydiv"><ul class="categorychecklist" style="background: #fff; border: 1px solid #DFDFDF; height: 200px; margin: 4px 1px; overflow: auto; padding: 3px 6px; width: 346px;">
		                    		<?php	
		                    			global $download_taxonomies, $categories;
		                    		
			                            $cats = $download_taxonomies->get_parent_cats();
										
			                            if (!empty($cats)) {
			                                foreach ( $cats as $c ) {
			                                    echo '<li><label for="category_'.$c->id.'"><input type="checkbox" name="category_'.$c->id.'" id="category_'.$c->id.'" ';
												if (isset($_POST['category_'.$c->id]) || (is_array($categories) && in_array($c->id, $categories))) echo 'checked="checked"';
												echo ' /> '.$c->id.' - '.$c->name.'</label>';
												
												// Do Children
												if (!function_exists('cat_form_output_children')) {
													function cat_form_output_children($child) {
														global $download_taxonomies, $categories;
														if ($child) {
															echo '<li><label for="category_'.$child->id.'"><input type="checkbox" name="category_'.$child->id.'" id="category_'.$child->id.'" ';
															if (isset($_POST['category_'.$child->id]) || (is_array($categories) && in_array($child->id,$categories))) echo 'checked="checked"';
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
		                        <select name="change_tags" style="vertical-align:middle">
                            		<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            		<option value="1"><?php _e('Change to &rarr;',"wp-download_monitor"); ?></option>
                            	</select><input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($download_tags)) echo $download_tags; ?>" name="tags" id="dltags" /><br /><span class="setting-description"><?php _e('Separate tags with commas.',"wp-download_monitor"); ?> <a class="browsetags" style="display:none" href="#"><?php _e('Toggle Tags',"wp-download_monitor"); ?></a></span>
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
		                        <select name="change_thumbnail" style="vertical-align:middle">
                            		<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            		<option value="1"><?php _e('Change to &rarr;',"wp-download_monitor"); ?></option>
                            	</select><input type="text" style="width:360px;" class="cleardefault" value="<?php if (isset($thumbnail)) echo $thumbnail; ?>" name="thumbnail" id="thumbnail" /><br />
                            	<?php if (get_option('wp_dlm_enable_file_browser')!=='no') : ?>
                            	<a class="browsefilesthumbnail" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                    			<div id="file_browser_thumbnail"></div>
                    			<?php endif; ?>
		                    </td> 
		                </tr>
                        <tr valign="top">												
                            <th scope="row"><strong><?php _e('Member only file?',"wp-download_monitor"); ?></strong></th> 
                            <td><select name="change_memberonly" style="vertical-align:middle">
                            	<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            	<option value="1"><?php _e('Change to &rarr;',"wp-download_monitor"); ?></option>
                            </select> <input type="checkbox" name="memberonly" style="vertical-align:middle" <?php if (isset($members) && $members==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, only logged in users will be able to access the file via a download link. You can also add a custom field called min-level or req-role to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></span></td>
                        </tr>
		                <tr valign="top">												
		                    <th scope="row"><strong><?php _e('Force Download?',"wp-download_monitor"); ?></strong></th> 
		                    <td><select name="change_forcedownload" style="vertical-align:middle">
                            	<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            	<option value="1"><?php _e('Change to &rarr;',"wp-download_monitor"); ?></option>
                            </select> <input type="checkbox" name="forcedownload" style="vertical-align:middle" <?php if (isset($forcedownload) && $forcedownload==1) echo "checked='checked'"; ?> /> <span class="setting-description"><?php _e('If chosen, Download Monitor will attempt to force the download rather than redirect. This setting is not compatible with all servers (so test it), and in most cases will only work on files hosted on the local server.',"wp-download_monitor"); ?></span></td>
		                </tr>						
					</table>
					<hr/>
		            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>
		            <p><?php _e('Custom fields can be used to add extra metadata to a download. Leave blank to add none. Name should be lower case with no spaces (changed automatically, e.g. <code>Some Name</code> will become <code>some-name</code>.',"wp-download_monitor"); ?></p>
		            <p><select name="change_customfields" style="vertical-align:middle">
                            	<option value=""><?php _e('No Change',"wp-download_monitor"); ?></option>
                            	<option value="1"><?php _e('Replace with:',"wp-download_monitor"); ?></option>
                            	<option value="2"><?php _e('Add/Update only (keep existing fields):',"wp-download_monitor"); ?></option>
                            </select></p>
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
							if ($custom_fields) foreach ($custom_fields as $meta) 
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
							if (isset($_POST['addmeta'])) {
								echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td>
								</tr>';
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
					<p class="submit"><input type="submit" class="btn button-primary" name="save" style="padding:5px 30px 5px 30px;" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" />
					
					<input type="hidden" name="dobulkaction" value="1" />
					<input type="hidden" name="bulkactions" value="edit" />
					<input type="hidden" name="check" value="<?php echo implode(',',$bulk_ids);  ?>" />
					</p>
				</form>
			</div>													
			<?php
			endif;			
		}		
	}
	/* End Bulk Editing */

	//show downloads page
	if ( ( isset($show) && $show==true) || ( empty($action) ) )
	{
	
	global $downloadurl, $downloadtype;
	
	?>
	<div class="wrap alternate">    
    	<div id="downloadadminicon" class="icon32"><br/></div>
        <h2><?php _e('Edit Downloads',"wp-download_monitor"); ?></h2>
		<form id="downloads-form" action="admin.php?page=download-monitor/wp-download_monitor.php" method="POST">

		<div class="tablenav">
			<div class="alignleft actions">
				<label class="hidden" for="bulkactions"><?php _e('Actions:',"wp-download_monitor"); ?></label>
				<select name="bulkactions" id="bulkactions">
					<option value=""><?php _e('Bulk Actions',"wp-download_monitor"); ?></option>
					<option value="edit"><?php _e('Edit',"wp-download_monitor"); ?></option>
					<option value="delete"><?php _e('Delete',"wp-download_monitor"); ?></option>
					<option value="reset"><?php _e('Reset Stats',"wp-download_monitor"); ?></option>
				</select>
				<input value="<?php _e('Apply',"wp-download_monitor"); ?>" class="button dobulkaction" name="dobulkaction" type="submit" />
			</div>
			<div class="alignright">
				<label class="hidden" for="post-search-input"><?php _e('Search Downloads:',"wp-download_monitor"); ?></label>
				<input class="search-input" id="post-search-input" name="search_downloads" value="<?php if (isset($_REQUEST['search_downloads'])) echo $_REQUEST['search_downloads']; ?>" type="text" />
				<input value="<?php _e('Search Downloads',"wp-download_monitor"); ?>" class="button" type="submit" />
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
		<?php
			$sort 	= "title";
			$dir	= 'asc';
			if (isset($_REQUEST['sort']) && ($_REQUEST['sort']=="id" || $_REQUEST['sort']=="filename" || $_REQUEST['sort']=="postDate")) $sort = $_REQUEST['sort'];
			
			if ($sort=='postDate') $dir = 'desc';
			
			if (isset($_REQUEST['dir']) && $_REQUEST['dir']=="desc") $dir ='desc';
			if (isset($_REQUEST['dir']) && $_REQUEST['dir']=="asc") $dir ='asc';
		?>
        <table class="widefat" style="margin-top:4px"> 
			<thead>
				<tr>
				<th scope="col" class="check-column"><input type="checkbox" name="check_all" id="check_all" class="checkbox" /></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=id<?php if ($sort=='id' && $dir!='desc') echo '&amp;dir=desc'; ?>"><?php _e('ID',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=title<?php if ($sort=='title' && $dir!='desc') echo '&amp;dir=desc'; ?>"><?php _e('Download',"wp-download_monitor"); ?></a></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=filename<?php if ($sort=='filename'  && $dir!='desc') echo '&amp;dir=desc'; ?>"><?php _e('File',"wp-download_monitor"); ?></a></th>
                <th scope="col"><?php _e('Categories',"wp-download_monitor"); ?></th>
				<th scope="col" style="text-align:left;width:150px;"><?php _e('Tags',"wp-download_monitor"); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Member only',"wp-download_monitor"); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Force Download',"wp-download_monitor"); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Custom fields',"wp-download_monitor"); ?></th>
                <th scope="col" style="text-align:center"><img src="<?php echo WP_CONTENT_URL; ?>/plugins/download-monitor/img/grey_arrow.gif" style="vertical-align:middle" alt="<?php _e('Hits',"wp-download_monitor"); ?>" title="<?php _e('Hits',"wp-download_monitor"); ?>" /></th>
				<th scope="col"><a href="?page=download-monitor/wp-download_monitor.php&amp;sort=postDate<?php if ($sort=='postDate' && $dir!='asc') echo '&amp;dir=asc'; ?>"><?php _e('Posted',"wp-download_monitor"); ?></a></th>					
				<?php /*<th scope="col"><?php _e('Action',"wp-download_monitor"); ?></th> */ ?>
				</tr>
			</thead>						
		<?php	
				// If current page number, use it 
				if(!isset($_REQUEST['p'])){ 
					$page = 1; 
				} else { 
					$page = $_REQUEST['p']; 
				}
				
				// Search
				if(!isset($_REQUEST['search_downloads'])){ 
					$search = ""; 
				} else { 
					$search = " WHERE (title LIKE '%".$wpdb->escape($_REQUEST['search_downloads'])."%' OR filename LIKE '%".$wpdb->escape($_REQUEST['search_downloads'])."%' OR ID = '".$wpdb->escape($_REQUEST['search_downloads'])."' ) ";
				}
				
				$total_results = sprintf("SELECT COUNT(id) FROM %s %s;",
					$wpdb->escape($wp_dlm_db), $search );
					
				// Figure out the limit for the query based on the current page number. 
				$from = (($page * 20) - 20); 
			
				$paged_select = sprintf("SELECT $wp_dlm_db.* FROM $wp_dlm_db %s
				ORDER BY %s LIMIT %s,20;",
					$search,
					$wpdb->escape( $sort.' '.$dir ),
					$wpdb->escape( $from ));
					
				$download = $wpdb->get_results($paged_select);
				$total = $wpdb->get_var($total_results);
			
				// Figure out the total number of pages. Always round up using ceil() 
				$total_pages = ceil($total / 20);
			
				if (!empty($download)) {
					echo '<tbody id="the-list">';
					foreach ( $download as $d ) {
					
						switch ($downloadtype) {
							case ("Title") :
									$downloadlink = urlencode($d->title);
							break;
							case ("Filename") :
									$downloadlink = $d->filename;
									$links = explode("/",$downloadlink);
									$downloadlink = urlencode(end($links));
									$downloadlink = str_replace('%26', '%2526', $downloadlink);
							break;
							default :
									$downloadlink = $d->id;
							break;
						}
					
						// Changed from jS M Y
						$date = date_i18n(__("Y/m/d","wp-download_monitor"), strtotime($d->postDate));
						
						$path 	= get_bloginfo('wpurl').'/'.get_option('upload_path').'/';
						$file = str_replace($path, "", $d->filename);
						$links = explode("/",$file);
						$file = end($links);
						echo '<tr class="alternate">';
						echo '<th class="check-column"><input type="checkbox" name="check[]['.$d->id.']" id="check_'.$d->id.'" class="checkbox check" /></th>';
						
						//$onclickcode = "if ( confirm('You are about to delete this download \'".$d->title."\'.\\n \'Cancel\' to stop, \'OK\' to delete.') ){return true;}return false;";
						
						echo '<td>'.$d->id.'</td>
						<td class="column-title">';
						
						$thumb = $wpdb->get_var('SELECT meta_value FROM '.$wp_dlm_db_meta.' WHERE download_id = '.$d->id.' AND meta_name = "thumbnail" LIMIT 1');
						if (!$thumb) $thumb = $wp_dlm_root.'page-addon/thumbnail.gif';
						
						echo '<img src="'.$thumb.'" alt="Thumbnail" style="float: left; margin: 0 8px 0 0; padding: 3px; background: #fff; border: 1px solid #E0E0E0" width="32" height="32" />';
						
						echo '<strong>'.$d->title.'';
						if ($d->dlversion) echo ' ('.__('Version',"wp-download_monitor").' '.$d->dlversion.')';
						echo '</strong>
						<div class="row-actions">
							<span class="edit"><a title="'.__('Edit this Download', 'wp-download_monitor').'" href="?page=download-monitor/wp-download_monitor.php&amp;action=edit&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'">'.__('Edit',"wp-download_monitor").'</a> | </span><span class="delete"><a class="submitdelete" href="?page=download-monitor/wp-download_monitor.php&amp;action=delete&amp;id='.$d->id.'&amp;sort='.$sort.'&amp;p='.$page.'" title="'.__('Delete this download',"wp-download_monitor").'">'.__('Delete',"wp-download_monitor").'</a></span>
						</div>						
						</td>
						<td><a href="'.$downloadurl.$downloadlink.'">'.$file.'</a></td>
						<td style="max-width:175px; text-align:left;">';
						
						$cats = get_download_taxonomy($d->id);
						if (sizeof($cats)==0) {
							_e('N/A',"wp-download_monitor");
						} else {							
							echo implode(', ', $cats['list']);
						}
										
						echo '</td>';
      
						echo '<td style="max-width:175px; text-align:left;">';
						
						$tags = get_download_taxonomy($d->id, 'tag');
						if (sizeof($tags)==0) {
							_e('N/A',"wp-download_monitor");
						} else {							
							echo implode(', ', $tags['names']);
						}
						
						echo '</td>
						<td style="text-align:center">';
						if ($d->members) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
						echo '</td>
						<td style="text-align:center">';
						if ( $wpdb->get_var('SELECT meta_value FROM '.$wp_dlm_db_meta.' WHERE download_id = '.$d->id.' AND meta_name = "force" LIMIT 1') ) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
						echo '</td>
						<td style="text-align:center">';
						echo $wpdb->get_var('SELECT COUNT(id) FROM '.$wp_dlm_db_meta.' WHERE download_id = '.$d->id.' AND meta_name NOT IN ("tags","thumbnail","force","filesize")');
						echo '</td>
						<td style="text-align:center">'.$d->hits.'</td><td>'.$date.'<br/>'.__('by',"wp-download_monitor").' '.$d->user.'</td>';
						
					}
					echo '</tbody>';
				} else echo '<tr><th colspan="11">'.__('No downloads found.',"wp-download_monitor").'</th></tr>'; // FIXED: 1.6 - Colspan changed
		?>			
		</table>
		<div class="tablenav">
			<div class="alignleft actions">
				<label class="hidden" for="bulkactions2"><?php _e('Actions:',"wp-download_monitor"); ?></label>
				<select name="bulkactions2" id="bulkactions2">
					<option value=""><?php _e('Bulk Actions',"wp-download_monitor"); ?></option>
					<option value="edit"><?php _e('Edit',"wp-download_monitor"); ?></option>
					<option value="delete"><?php _e('Delete',"wp-download_monitor"); ?></option>
					<option value="reset"><?php _e('Reset Stats',"wp-download_monitor"); ?></option>
				</select>
				<input value="<?php _e('Apply',"wp-download_monitor"); ?>" class="button dobulkaction" name="dobulkaction2" type="submit" />
			</div>
        	<div class="tablenav-pages alignright">
				<?php
					if ($total_pages>1) {
						
						if (isset($_REQUEST['search_downloads'])) $search_downloads = $_REQUEST['search_downloads']; else $search_downloads = '';
						$arr_params = array (
							'sort' => $sort,
							'page' => 'download-monitor/wp-download_monitor.php',
							'search_downloads' => $search_downloads,
							'p' => "%#%"
						);
						
						$arr_params2 = array (
							'action', 'id'
						);
						
						$query_page = remove_query_arg($arr_params2);
						$query_page = add_query_arg( $arr_params , $query_page );
					
						echo paginate_links( array(
							'base' => $query_page,
							'prev_text' => __('&laquo; Previous'),
							'next_text' => __('Next &raquo;'),
							'total' => $total_pages,
							'current' => $page,
							'end_size' => 1,
							'mid_size' => 5,
						));
					}
				?>	
				<div class="clear"></div>
            </div> 
			<div class="clear"></div>
		</div>
        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
		<script type="text/javascript">
		/* <![CDATA[ */
			jQuery('#check_all').click(function(){
				jQuery('.check').attr('checked', jQuery(this).is(':checked'));
			});
			jQuery('#check_all, .check').attr('checked',false);
			
			// Confirm
			jQuery('.dobulkaction').click(function(){
			
				if ( jQuery('select[name=bulkactions], select[name=bulkactions2]', jQuery(this).parent() ).val() == 'delete' ) {
					if ( confirm('<?php echo js_escape(__("You are about to delete the selected items.\n  'Cancel' to stop, 'OK' to delete.")); ?>') ) 
					{
						return true;
					}
					return false;
				}				
			});
		/* ]]> */
		</script>
    </form>
    </div>
    <?php if (!get_option('wp_dlm_disable_news') || get_option('wp_dlm_disable_news')=='no') : ?>
    <hr />
    <div class="about">
    	<div class="about-widget">
    		<h3><?php _e('Download Monitor News',"wp-download_monitor"); ?></h3>
    		<div class="inside">
    		<?php
    			if (file_exists(ABSPATH.WPINC.'/class-simplepie.php')) {
	    			
	    			include_once(ABSPATH.WPINC.'/class-simplepie.php');
	    			
					$rss = fetch_feed('http://mikejolley.com/tag/download-monitor/feed');
					
					if (!is_wp_error( $rss ) ) :
					
						$maxitems = $rss->get_item_quantity(5); 
						$rss_items = $rss->get_items(0, $maxitems); 					
					
						if ( $maxitems > 0 ) :
						
							echo '<ul>';
						
								foreach ( $rss_items as $item ) :
							
								$title = wptexturize($item->get_title(), ENT_QUOTES, "UTF-8");

								$link = $item->get_permalink();
											
			  					$date = $item->get_date('U');
			  
								if ( ( abs( time() - $date) ) < 86400 ) : // 1 Day
									$human_date = sprintf(__('%s ago','wp-download_monitor'), human_time_diff($date));
								else :
									$human_date = date(__('F jS Y','wp-download_monitor'), $date);
								endif;
			
								echo '<li><a href="'.$link.'">'.$title.'</a> &ndash; <span class="rss-date">'.$human_date.'</span></li>';
						
							endforeach;
						
							echo '</ul>';
							
						else :
							echo '<ul><li>'.__('No items found.','wp-download_monitor').'</li></ul>';
						endif;
					
					else :
						echo '<ul><li>'.__('No items found.','wp-download_monitor').'</li></ul>';
					endif;
				
				}
    		?>
    		</div>
    	</div>
    	<div class="about-widget" style="margin-right:0;">
    		<h3><?php _e('Links &amp; Documentation',"wp-download_monitor"); ?></h3>
    		<div class="inside">
    			<?php _e('<p>Need help? FAQ, Usage instructions and other notes can be found on the WordPress.org plugin page.</p>',"wp-download_monitor"); ?>
    			<ul>
    				<li><a href="http://wordpress.org/extend/plugins/download-monitor/"><?php _e('Download Monitor on WordPress.org',"wp-download_monitor"); ?></a></li>
    				<li><a href="http://mikejolley.com/projects/download-monitor/"><?php _e('Download Monitor documentation + FAQ',"wp-download_monitor"); ?></a></li>
    				<li><a href="https://github.com/mikejolley/download-monitor"><?php _e('Download Monitor on GitHub',"wp-download_monitor"); ?></a></li>
    			</ul>
    		</div>
    	</div> 
    	<div class="about-widget" style="margin-right:0; float:right;">
    		<h3><?php _e('Support Download Monitor',"wp-download_monitor"); ?></h3>
    		<div class="inside">
    			<p><?php _e('The Wordpress Download monitor plugin was created by <a href="http://mikejolley.com/">Mike Jolley</a>. The development of this plugin took a lot of time and effort, so please don\'t forget to donate if you found this plugin useful.',"wp-download_monitor"); ?></p>
	    
	    		<p><?php _e('There are also other ways of supporting download monitor to ensure it is maintained and well supported in the future! Rating the plugin on wordpress.org (if you like it), linking/spreading the word, and submitting code contributions will all help.',"wp-download_monitor"); ?></p>
	    		
	    		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right">
					<input type="hidden" name="cmd" value="_s-xclick" />
					<input type="hidden" name="hosted_button_id" value="10691945" />
					<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." />
					<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
				</form>
    		</div>
    	</div>		
		<div class="clear"></div>
	</div>
	<?php endif; ?>

<?php
	}
	
	echo '</div>';
}

?>