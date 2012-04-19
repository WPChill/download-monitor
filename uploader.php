<?php
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
if(file_exists($wp_root . '/wp-load.php')) {
	require_once($wp_root . "/wp-load.php");
} else if(file_exists($wp_root . '/wp-config.php')) {
	require_once($wp_root . "/wp-config.php");
} else {
	exit;
}

global $wp_db_version;
if ($wp_db_version < 8201) {
	// Pre 2.6 compatibility (BY Stephen Rider)
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
		else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

require_once(ABSPATH.'wp-admin/admin.php');

load_plugin_textdomain('wp-download_monitor', false, 'download-monitor/languages/');

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
// REPLACE ADMIN URL
################################################################################

if (function_exists('admin_url')) {
	wp_admin_css_color('classic', __('Blue'), admin_url("css/colors-classic.css"), array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), admin_url("css/colors-fresh.css"), array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
} else {
	wp_admin_css_color('classic', __('Blue'), get_bloginfo('wpurl').'/wp-admin/css/colors-classic.css', array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), get_bloginfo('wpurl').'/wp-admin/css/colors-fresh.css', array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
}

wp_enqueue_script( 'common' );
wp_enqueue_script( 'jquery-color' );

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

if (!current_user_can('upload_files') || !current_user_can('user_can_add_new_download'))
	wp_die(__('You do not have permission to upload files/downloads.'));
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Uploads'); ?> &#8212; <?php _e('WordPress'); ?></title>
	<?php
		wp_enqueue_style( 'global' );
		wp_enqueue_style( 'wp-admin' );
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
	?>
	<script type="text/javascript">
	//<![CDATA[
		function addLoadEvent(func) {if ( typeof wpOnload!='function'){wpOnload=func;}else{ var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}}
	//]]>
	</script>
	<?php
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	if ( isset($content_func) && is_string($content_func) )
		do_action( "admin_head_{$content_func}" );
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.css" />
	<script type="text/javascript" src="<?php echo $wp_dlm_root; ?>js/jqueryFileTree/jqueryFileTree.js"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
		
		jQuery.noConflict();
		(function($) { 
		  $(function() {
		  
		    $('#file_browser').hide().fileTree({
		      root: '<?php echo get_option('wp_dlm_file_browser_root'); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTree.php',
		    }, function(file) {
		        var path = file;
		        $('#filename, #dlfilename').val(path);
		        $('#file_browser').slideToggle();
		    });
		    
		     $('#file_browser_thumbnail').hide().fileTree({
		      root: '<?php echo get_option('wp_dlm_file_browser_root'); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTree.php',
		    }, function(file) {
		        var path = file.replace('<?php echo ABSPATH; ?>', '<?php bloginfo('wpurl'); ?>/');
		        $('#thumbnail').val(path);
		        $('#file_browser_thumbnail').slideToggle();
		    });
		    
		    $('#file_browser2').hide().fileTree({
		      root: '<?php echo get_option('wp_dlm_file_browser_root'); ?>',
		      script: '<?php echo $wp_dlm_root; ?>js/jqueryFileTree/connectors/jqueryFileTreeDir.php',
		    }, function(file) {
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
</head>
<body id="media-upload">
	<div id="media-upload-header">
		<ul id='sidemenu'>
			<li id='tab-add'><a href='uploader.php?tab=add' <?php if ($_GET['tab']=='add') echo "class='current'"; ?>><?php _e('Add New Download',"wp-download_monitor"); ?></a></li>
			<li id='tab-downloads'><a href='uploader.php?tab=downloads' <?php if ($_GET['tab']=='downloads') echo "class='current'"; ?>><?php _e('View Downloads',"wp-download_monitor"); ?></a></li>
		</ul>
	</div>
	<?php
	// Get the Tab
	$tab = esc_attr( $_GET['tab'] );
	switch ($tab) {
	
		case 'add' :
			if ($_POST) {
						
				// Form processing
				global $wpdb;
				$wp_dlm_db = $wpdb->prefix."download_monitor_files";
				$wp_dlm_db_taxonomies = $wpdb->prefix."download_monitor_taxonomies";
				$wp_dlm_db_relationships = $wpdb->prefix."download_monitor_relationships";
				$wp_dlm_db_formats = $wpdb->prefix."download_monitor_formats";
				$wp_dlm_db_stats = $wpdb->prefix."download_monitor_stats";
				$wp_dlm_db_log = $wpdb->prefix."download_monitor_log";
				$wp_dlm_db_meta = $wpdb->prefix."download_monitor_file_meta";
											
				//get postdata
				$title = htmlspecialchars(trim($_POST['title']));
				$filename = htmlspecialchars(trim($_POST['filename']));									
				$dlversion = htmlspecialchars(trim($_POST['dlversion']));
				$dlhits = htmlspecialchars(trim($_POST['dlhits']));
				$postDate = esc_attr( $_POST['postDate'] );
				$user = esc_attr( $_POST['user'] );
				$members = (isset($_POST['memberonly'])) ? 1 : 0;
				$forcedownload = (isset($_POST['forcedownload'])) ? 1 : 0;
				$download_cat = esc_attr( $_POST['download_cat'] );
				$mirrors = htmlspecialchars(trim($_POST['mirrors']));
				$file_description = trim($_POST['file_description']);
				
				$tags = esc_attr( $_POST['tags'] );
				$thumbnail = esc_attr( $_POST['thumbnail'] );
								
				if ($_POST['insertonlybutton']) {
											
					//validate fields
					if (empty( $_POST['title'] )) $errors=__('<div id="media-upload-error">Required field: <strong>Title</strong> omitted</div>',"wp-download_monitor");
					if (empty( $_POST['dlhits'] )) $_POST['dlhits'] = 0;						
					if (!is_numeric($_POST['dlhits'] )) $errors=__('<div id="media-upload-error">Invalid <strong>hits</strong> entered</div>',"wp-download_monitor");
						
					if ($thumbnail) {
						if( !strstr($thumbnail, '://') ) { 
						
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
					if ( empty($errors ) && isset($_FILES['upload']) ) {										
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
							if ( empty( $_POST['filename']) ) $errors = '<div id="media-upload-error">'.__('No file selected',"wp-download_monitor").'</div>';
							else $errors = '';
						}								
					}	
					//attempt to upload thumbnail
					if ( empty($errors ) && isset($_FILES['thumbnail_upload']) ) {										
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
							if ($_POST['meta']) foreach ($_POST['meta'] as $meta) 
							{
								if (trim($meta['key'])) {
									$values[] = '("'.$wpdb->escape(strtolower((str_replace(' ','-',trim(stripslashes($meta['key'])))))).'", "'.$wpdb->escape($meta['value']).'", '.$download_insert_id.')';
									$index ++;
								}
							}
							if (sizeof($values)>0) $wpdb->query("INSERT INTO $wp_dlm_db_meta (meta_name, meta_value, download_id) VALUES ".implode(',', $values)."");
							
							do_action('download_added', $download_insert_id);
					
						}
						else _e('<div id="media-upload-error">Error saving to database</div>',"wp-download_monitor");										
					} else echo $errors;

				}
				
			}
			if ((isset($errors) && !empty($errors)) || !isset($_POST['insertonlybutton'])) {
			?>
			<form enctype="multipart/form-data" method="post" action="uploader.php?tab=add" id="media-upload-form type-form validate" id="download-form">
								
				<h3><?php _e('Download Information',"wp-download_monitor"); ?></h3>
				<?php if (isset($errors)) echo $errors; ?>
				
				<table class="describe"><tbody>
					<tr>
						<th valign="top" scope="row" class="label" >
							<span class="alignleft"><label for="dltitle"><?php _e('Title',"wp-download_monitor"); ?></label></span>
							<span class="alignright"><abbr title="required" class="required">*</abbr></span>
						</th>
						<td class="field"><input type="text" value="<?php if (isset($title)) echo $title; ?>" name="title" id="dltitle" maxlength="200" /></td> 
					</tr>
					<tr>
                        <th valign="top" scope="row" class="label"><?php _e('Description',"wp-download_monitor"); ?>:</th> 
                        <td class="field"><textarea  name="file_description" cols="50" rows="2"><?php if (isset($file_description)) echo $file_description; ?></textarea></td>
                    </tr>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="dlversion"><?php _e('Version',"wp-download_monitor"); ?></label></span>
						</th> 
						<td class="field"><input type="text" value="<?php if (isset($dlversion)) echo $dlversion; ?>" name="dlversion" id="dlversion" /></td> 
					</tr>
					<tr>
						<th valign="top" scope="row" class="label">
							<span class="alignleft"><label for="dlhits"><?php _e('Starting hits',"wp-download_monitor");?></label></span>
						</th> 
						<td class="field"><input type="text" value="<?php if (isset($dlhits) && $dlhits>0) echo $dlhits; else echo 0; ?>" name="dlhits" id="dlhits" maxlength="50" /></td> 
					</tr>
					<tr valign="top">
							<th valign="top" scope="row" class="label">
								<span class="alignleft"><?php _e('Select a file...',"wp-download_monitor"); ?></span>
								<span class="alignright"><abbr title="required" class="required">*</abbr></span>
							</th> 
							<td class="field">
								<input type="hidden" name="MAX_FILE_SIZE" value="<?php 
													
									$max_upload_size = "";
				
									if (function_exists('ini_get')) {
										$max_upload_size = min(dlm_let_to_num(ini_get('post_max_size')), dlm_let_to_num(ini_get('upload_max_filesize')));
										$max_upload_size_text = __(' (defined in php.ini)',"wp-download_monitor");
									}
									
									if (!$max_upload_size || $max_upload_size==0) {
										$max_upload_size = 8388608;
										$max_upload_size_text = '';
									}	
									
									echo $max_upload_size;
					
								 ?>" />
								<h4 style="margin:0.1em 0 0.5em"><?php _e('Upload File',"wp-download_monitor"); ?></h4>
								<input type="file" name="upload" style="width: 452px;" />							
								<p class="help" style="font-size:11px; color: #9A9A9A;"><?php _e('Max. filesize',"wp-download_monitor"); echo $max_upload_size_text; ?> = <?php echo $max_upload_size; ?> <?php _e('bytes',"wp-download_monitor"); ?>. <?php _e('If a file with the same name already exists in the upload directly, this file will be renamed.',"wp-download_monitor"); ?></p>
								
								<p style="width: 460px; margin:4px 0; font-weight:bold; font-size:16px; color: #999; line-height: 18px; text-align:center;">&uarr;<br/><?php _e('OR',"wp-download_monitor"); ?><br/>&darr;</p>							
								<h4 style="margin:0.1em 0 0.5em"><?php _e('Enter file URL',"wp-download_monitor"); ?></h4>
								<input type="text" class="cleardefault" value="<?php if (isset($filename)) echo $filename; ?>" name="filename" id="filename" /><br /><a class="browsefiles" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                    			<div id="file_browser" style="width:444px;"></div>
								 <input type="hidden" name="postDate" value="<?php echo date(__('Y-m-d H:i:s',"wp-download_monitor")) ;?>" />
								<?php 
									global $userdata;
									get_currentuserinfo();										
									echo '<input type="hidden" name="user" value="'.$userdata->user_login.'" />';
								?>	
							</td>
	                </tr> 
					<tr valign="top">												
	                    <th valign="top" scope="row" class="label">
							<span class="alignleft"><?php _e('Mirrors',"wp-download_monitor"); ?></span>
	                    </th> 
	                    <td class="field"><textarea name="mirrors" cols="50" rows="2"><?php if (isset($mirrors)) echo $mirrors; ?></textarea></td>
                	</tr>
                	<tr><td></td><td class="help" style="font-size:11px;"><?php _e('Optionally list the url\'s of any mirrors here (1 per line). Download monitor will randomly pick one of these mirrors when serving the download.',"wp-download_monitor"); ?></td></tr>
	                <tr valign="top">	
	                	<th valign="top" scope="row" class="label">
							<span class="alignleft"><?php _e('Categories',"wp-download_monitor"); ?></span>
	                    </th> 											
	                    <td class="field"><div id="categorydiv"><ul class="categorychecklist" style="background: #fff; border: 1px solid #DFDFDF; height: 200px; margin: 4px 1px; overflow: auto; padding: 3px 6px; width: 444px;">
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
	                    <th valign="top" scope="row" class="label">
	                    	<span class="alignleft"><label for="dltags"><?php _e('Tags',"wp-download_monitor"); ?></label></span>
						</th> 
	                    <td class="field">
	                        <input type="text" class="cleardefault" value="<?php if (isset($tags)) echo $tags; ?>" name="tags" id="dltags" />
	                    </td> 
	                </tr>
					<tr><td></td><td class="help" style="font-size:11px;"><?php _e('Separate tags with commas.',"wp-download_monitor"); ?> <a class="browsetags" style="display:none" href="#"><?php _e('Toggle Tags',"wp-download_monitor"); ?></a><div id="tag-list" style="display:none; width:456px;">
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
                	</div></td></tr>
	                <tr valign="middle">
	                	<th valign="top" scope="row" class="label">
	                    	<span class="alignleft"><?php _e('Thumbnail',"wp-download_monitor"); ?></span>
						</th> 
						<td class="field">
							<h4 style="margin:0.1em 0 0.5em"><?php _e('Upload thumbnail',"wp-download_monitor"); ?></h4>
							<input type="file" name="thumbnail_upload" style="width:452px;" />

							<p class="help" style="font-size:11px; color: #9A9A9A;"><?php _e('This will be displayed on the download page or with {thumbnail} in a custom format (a placeholder will be shown if not set).',"wp-download_monitor"); ?></p>
						
							<p style="width: 460px; margin:4px 0; font-weight:bold; font-size:16px; color: #999; line-height: 18px; text-align:center;">&uarr;<br/><?php _e('OR',"wp-download_monitor"); ?><br/>&darr;</p>							
							<h4 style="margin:0.1em 0 0.5em"><?php _e('Enter thumbnail URL',"wp-download_monitor"); ?></h4>
							<input type="text" class="cleardefault" value="<?php if (isset($thumbnail)) echo $thumbnail; ?>" name="thumbnail" id="thumbnail" /><br /><a class="browsefilesthumbnail" style="display:none" href="#"><?php _e('Toggle File Browser',"wp-download_monitor"); ?></a>
                			<div id="file_browser_thumbnail" style="width:444px;"></div>
						</td>
	                </tr>
	                <tr valign="middle">
	                	<th valign="top" scope="row" class="label">
	                    	<span class="alignleft"><label for="memberonly"><?php _e('Member only file?',"wp-download_monitor"); ?></label></span>
						</th> 												
	                    <td class="field"><input type="checkbox" id="memberonly" name="memberonly" style="vertical-align:middle; margin-top: 0.2em;" <?php if (isset($members) && $members==1) echo "checked='checked'"; ?> /></td>
	                </tr>
	                <tr><td></td><td class="help" style="font-size:11px;"><?php _e('If chosen, only logged in users will be able to access the file via a download link. You can also add a custom field called min-level or req-role to set the minimum user level needed to download the file.',"wp-download_monitor"); ?></td></tr>
	                <tr valign="middle">
	                	<th valign="top" scope="row" class="label">
	                    	<span class="alignleft"><label for="forcedownload"><?php _e('Force Download?',"wp-download_monitor"); ?></label></span>
						</th> 											
	                    <td class="field"><input type="checkbox" name="forcedownload" id="forcedownload" style="vertical-align:middle; margin-top: 0.2em;" <?php if (isset($forcedownload) && $forcedownload==1) echo "checked='checked'"; ?> /></td>
	                </tr>
	                <tr><td></td><td class="help" style="font-size:11px;"><?php _e('If chosen, Download Monitor will attempt to force the download rather than redirect. This setting is not compatible with all servers (so test it), and in most cases will only work on files hosted on the local server.',"wp-download_monitor"); ?></td></tr>

				</tbody></table>


	            <h3><?php _e('Custom fields',"wp-download_monitor"); ?></h3>	            
				<table style="width:100%">
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
								if (!$meta['remove']) {
									if (trim($meta['key'])) {
									echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="'.strtolower((str_replace(' ','-',stripslashes($meta['key'])))).'" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%">'.stripslashes($meta['value']).'</textarea></td>
									</tr>';	
									}							
								}		
								$index ++;					
							}
							if ($_POST['addmeta']) {
								echo '<tr class="alternate">
										<td class="left" style="vertical-align:top;">
											<label class="hidden" for="meta['.$index.'][key]">Key</label><input name="meta['.$index.'][key]" id="meta['.$index.'][key]" tabindex="6" size="20" value="" type="text" style="width:95%">
											<input type="submit" name="meta['.$index.'][remove]" class="button" value="'.__('remove',"wp-download_monitor").'" />
										</td>
										<td style="vertical-align:top;"><label class="hidden" for="meta['.$index.'][value]">Value</label><textarea name="meta['.$index.'][value]" id="meta['.$index.'][value]" tabindex="6" rows="2" cols="30" style="width:95%"></textarea></td>
								</tr>';
							}
						} 											
						?>
						<tr id="addmetarow">
							<td colspan="2" class="submit"><input id="addmetasub" name="addmeta" value="<?php _e('Add Custom Field',"wp-download_monitor"); ?>" type="submit" style="margin-bottom: 6px !important;" /><br/><a class="seemetafields" style="display:none;" href="#"><?php _e('Toggle Existing Custom Fields',"wp-download_monitor"); ?></a>
							<div id="meta_fields" style="display:none;width:95%;">
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
				<p class="submit"><input type="submit" class="button button-primary" name="insertonlybutton" value="<?php _e('Save new download',"wp-download_monitor"); ?>" /></p>
				
			</form>
			<?php } else {
				// GENERATE CODE TO INSERT
				$html = '[download id="'.$download_insert_id.'"';
				?>
				<div style="margin:1em;">
				<h3><?php _e('Insert new download into post',"wp-download_monitor"); ?></h3>
				
				<p class="submit"><label for="format"><?php _e('Insert into post using format:',"wp-download_monitor"); ?></label> <select style="vertical-align:middle;" name="format" id="format">
							<option value="0"><?php _e('Default',"wp-download_monitor"); ?></option>
							<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<option value="'.$f->id.'">'.$f->name.'</option>';
									}
								}
							?>         
						</select>
				<?php echo '<input type="submit" id="insertdownload" class="button button-primary" name="insertintopost" value="'.__('Insert into post',"wp-download_monitor").'" />'; ?></p>

				<script type="text/javascript">
					/* <![CDATA[ */
					jQuery('#insertdownload').click(function(){
					var win = window.dialogArguments || opener || parent || top;
					if (jQuery('#format').val()>0) win.send_to_editor('<?php echo $html; ?> format="' + jQuery('#format').val() + '"]');
					else win.send_to_editor('<?php echo $html; ?>]');
					});
					/* ]]> */
				</script>
				</div>
			<?php
			exit;
			}
			
		break;
		case 'downloads' :
			// Show table of downloads			
			?>
			<form id="downloads-filter" action="uploader.php?tab=downloads" method="post">
				<p class="search-box">
					<label class="hidden" for="post-search-input"><?php _e('Search Downloads:',"wp-download_monitor"); ?></label>
					<input class="search-input" id="post-search-input" name="s" value="<?php echo esc_attr( $_REQUEST['s'] ); ?>" type="text" />
					<input value="<?php _e('Search Downloads',"wp-download_monitor"); ?>" class="button" type="submit" />
				</p>
			</form>
			<div class="clear:both"></div>
			<form enctype="multipart/form-data" method="post" style="clear:both" action="uploader.php?tab=downloads" class="media-upload-form" id="gallery-form">
			<table style="float:right;width:300px;margin:0.7em 0 0" cellpadding="0" cellspacing="0">
				<tr>
					<th scope="row" style="vertical-align:middle;">
						<label for="format" style="font-size:12px;text-align:right;margin-right:8px;margin-top:4px;"><?php _e('Insert into post using format:',"wp-download_monitor"); ?></label>
					</th>
					<td style="vertical-align:middle;text-align:right;"><select name="format" id="format">
						<option value="0"><?php _e('Default',"wp-download_monitor"); ?></option>
						<?php								
							$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
								$wpdb->escape( $wp_dlm_db_formats ));	
							$formats = $wpdb->get_results($query_select_formats);
		
							if (!empty($formats)) {
								foreach ( $formats as $f ) {
									echo '<option value="'.$f->id.'">'.$f->name.'</option>';
								}
							}
						?>         
					</select></td>
				</tr>
			</table>
			<h3 style="float:left"><?php _e('Downloads'); ?></h3>		
			<div class="clear:both"></div>
	        <table class="widefat" style="width:100%;" cellpadding="0" cellspacing="0"> 
				<thead>
					<tr>				
						<th scope="col" style="text-align:center;vertical-align:middle"><a href="?tab=downloads&amp;sort=id"><?php _e('ID',"wp-download_monitor"); ?></a></th>
						<th scope="col" style="vertical-align:middle"><a href="?tab=downloads&amp;sort=title"><?php _e('Title',"wp-download_monitor"); ?></a></th>
						<th scope="col" style="vertical-align:middle"><a href="?tab=downloads&amp;sort=filename"><?php _e('File',"wp-download_monitor"); ?></a></th>
		                <th scope="col" style="text-align:center;vertical-align:middle;"><?php _e('Categories',"wp-download_monitor"); ?></th>
		                <th scope="col" style="text-align:center;vertical-align:middle;"><?php _e('Tags',"wp-download_monitor"); ?></th>
		                <th scope="col" style="text-align:center"><?php _e('Member only',"wp-download_monitor"); ?></th>
                		<th scope="col" style="text-align:center"><?php _e('Force Download',"wp-download_monitor"); ?></th>
						<th scope="col" style="text-align:center;vertical-align:middle"><?php _e('Action',"wp-download_monitor"); ?></th>
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
					if(!isset($_REQUEST['s'])){ 
						$search = ""; 
					} else { 
						$search = " WHERE (title LIKE '%".$wpdb->escape($_REQUEST['s'])."%' OR filename LIKE '%".$wpdb->escape($_REQUEST['s'])."%') ";
					}
					
					// Sort column
					$sort = "title";
					if (isset($_REQUEST['sort']) && ($_REQUEST['sort']=="id" || $_REQUEST['sort']=="filename" || $_REQUEST['sort']=="postDate")) $sort = $_REQUEST['sort'];
					
					$total_results = "SELECT COUNT(id) FROM $wp_dlm_db".$search.";";
						
					// Figure out the limit for the query based on the current page number. 
					$from = (($page * 10) - 10); 
				
					$paged_select = "SELECT * FROM $wp_dlm_db".$search." ORDER BY ".$wpdb->escape( $sort )." LIMIT ".$wpdb->escape( $from ).",10;";
						
					$download = $wpdb->get_results($paged_select);
					$total = $wpdb->get_var($total_results);
				
					// Figure out the total number of pages. Always round up using ceil() 
					$total_pages = ceil($total / 10);
				
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
								break;
								default :
										$downloadlink = $d->id;
								break;
							}
						
							$date = date("jS M Y", strtotime($d->postDate));
							
							$path = get_bloginfo('wpurl').'/'.get_option('upload_path').'/';
							$file = str_replace($path, "", $d->filename);
							$links = explode("/",$file);
							$file = end($links);
							echo ('<tr class="alternate">');
							echo '<td style="text-align:center;vertical-align:middle">'.$d->id.'</td>
							<td style="vertical-align:middle">'.$d->title.'</td>
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
							
							echo '</td>';
							
							echo '<td style="text-align:center;vertical-align:middle">';
							if ($d->members) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
							echo '</td>';
							echo '<td style="text-align:center;vertical-align:middle">';
							if ( $wpdb->get_var('SELECT meta_value FROM '.$wp_dlm_db_meta.' WHERE download_id = '.$d->id.' AND meta_name = "force" LIMIT 1') ) echo __('Yes',"wp-download_monitor"); else echo __('No',"wp-download_monitor");
							echo '</td>';
							echo '<td style="text-align:center;vertical-align:middle"><a href="#" style="display:block" class="button insertdownload" id="download-'.$d->id.'">'.__('Insert',"wp-download_monitor").'</a></td>';
							
						}
						echo '</tbody>';
					} 
			?>			
			</table>
	        <div class="tablenav">
	        	<div class="tablenav-pages alignleft">
					<?php
						if ($total_pages>1) {
							
							global $wp_dlm_root; 
							
							$arr_params = array (
								'sort' => $sort,
								's' => $_REQUEST['s'],
								'p' => "%#%"
							);
							
							$query_page = add_query_arg( $arr_params );
			
							echo paginate_links( array(
								'base' => $query_page,
								'prev_text' => __('&laquo; Previous'),
								'next_text' => __('Next &raquo;'),
								'total' => $total_pages,
								'current' => $_REQUEST['p'],
								'end_size' => 25,
								'mid_size' => 5,
							));
						}
					?>	
	            </div> 
	        </div>
	        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
	    </div>
		<script type="text/javascript">
			/* <![CDATA[ */
			jQuery('.insertdownload').click(function(){
				var win = window.dialogArguments || opener || parent || top;
				var did = jQuery(this).attr('id');
				did=did.replace('download-', '');
				if (jQuery('#format').val()>0) win.send_to_editor('[download id="' + did + '" format="' + jQuery('#format').val() + '"]');
				else win.send_to_editor('[download id="' + did + '"]');
			});
			/* ]]> */
		</script>

		<?php
			
		break;
	}
	?>
</body>
</html>