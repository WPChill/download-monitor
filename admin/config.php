<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (CONFIG)
	
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
// Configuration page
################################################################################

function wp_dlm_config() {

	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats,$dlm_url,$downloadtype,$wp_dlm_db_relationships;

	// turn off magic quotes
	wp_dlm_magic();
	
	?>
	
	<div class="download_monitor">
	
	<div class="wrap alternate">
    
    <div id="downloadadminicon" class="icon32"><br/></div>
    <h2><?php _e('Download Monitor Configuration',"wp-download_monitor"); ?></h2>
    <?php
	if (isset($_GET['action'])) $action = $_GET['action']; else $action = '';
	if (!empty($action)) {
		switch ($action) {
				case "saveurl" :
				  $dlm_url = $_POST['url'];						 
					update_option('wp_dlm_url', trim($dlm_url));
					update_option('wp_dlm_type', $_POST['type']);
					$downloadtype = get_option('wp_dlm_type');
					if (!empty($dlm_url)) {
						echo '<div id="message"class="updated fade">';	
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options/settings -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, add the following to your 
					.htaccess file above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/([^/]+)$ *your wp-content dir*/plugins/download-monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your custom url and "*your wp-content dir*" with your wp-content directory.</p>',"wp-download_monitor");			
						echo '</div>';
					} else {
					echo '<div id="message"class="updated fade">';				
						_e('<p>Download URL updated - You need to <strong>re-save your permalinks settings</strong> (Options/settings -> Permalinks) for 
						the changes to occur in your blog.</p>
						<p>If your .htaccess file cannot be written to by WordPress, remove the following from your 
					.htaccess file if it exists above the "# BEGIN WordPress" line:</p>
						<p>Options +FollowSymLinks<br/>
						RewriteEngine on<br/>
						RewriteRule ^download/([^/]+)$ *your wp-content dir*/plugins/download-monitor/download.php?id=$1 [L]</p>
						<p>replacing "download/" with your previous custom url and "*your wp-content dir*" with your wp-content directory.</p>',"wp-download_monitor");
					echo '</div>';
					}
					$save_url = true;
					$show=true;
				break;
				case "saveoptions" :
					update_option('wp_dlm_disable_news', $_POST['wp_dlm_disable_news']);
					
					update_option('wp_dlm_image_url', $_POST['wp_dlm_image_url']);
					update_option('wp_dlm_default_format', $_POST['wp_dlm_default_format']);
					update_option('wp_dlm_does_not_exist', $_POST['wp_dlm_does_not_exist']);
					update_option('wp_dlm_member_only', $_POST['wp_dlm_member_only']);
					update_option('wp_dlm_log_downloads', $_POST['wp_dlm_log_downloads']);					
					update_option('wp_dlm_enable_file_browser', $_POST['wp_dlm_enable_file_browser']);
					update_option('wp_dlm_auto_mirror', $_POST['wp_dlm_auto_mirror']);
					
					update_option('wp_dlm_global_member_only', $_POST['wp_dlm_global_member_only']);
					
					update_option('wp_dlm_log_timeout', $_POST['wp_dlm_log_timeout']);
					
					update_option('wp_dlm_ip_blacklist', $_POST['wp_dlm_ip_blacklist']);
					
					
					if ($_POST['wp_dlm_file_browser_root'])
						update_option('wp_dlm_file_browser_root', $_POST['wp_dlm_file_browser_root']);	
					else 
						update_option('wp_dlm_file_browser_root', ABSPATH);			   
					
					$save_opt=true;
					echo '<div id="message"class="updated fade">';	
						_e('<p>Options updated</p>',"wp-download_monitor");			
						echo '</div>';
					$show=true;
				break;	
				case "upgrade" :
					wp_dlm_upgrade();					
					$show=true;				
				break;			
				case "cleanup" :
					wp_dlm_cleanup();
					echo '<div id="message" class="updated fade"><p><strong>'.__('Database Cleaned',"wp-download_monitor").'</strong></p></div>';
					$show=true;				
				break;
				case "formats" :
					wp_cache_flush();
					if (isset($_POST['savef'])) {
						$loop = 0;
						if (is_array($_POST['formatfieldid'])) {
							foreach($_POST['formatfieldid'] as $formatid) {
								if ($_POST['formatfield'][$loop]) {
									$query_update = sprintf("UPDATE %s SET `format`='%s' WHERE id = %s;",
										$wpdb->escape( $wp_dlm_db_formats ),
										$wpdb->escape( stripslashes($_POST['formatfield'][$loop]) ),
										$wpdb->escape( $formatid ));
									$wpdb->query($query_update);
								}
								//echo htmlspecialchars($wpdb->escape( stripslashes($_POST['formatfield'][$loop]) ));
								$loop++;	
							}						
							echo '<div id="message" class="updated fade"><p><strong>'.__('Formats updated',"wp-download_monitor").'</strong></p></div>';											$ins_format=true;
						}
					} else {
						$name = $_POST['format_name'];
						$format = $_POST['format'];
						if (!empty($name) && !empty($format)) {
							$query_ins = sprintf("INSERT INTO %s (name, format) VALUES ('%s','%s')",
								$wpdb->escape( $wp_dlm_db_formats ),
								$wpdb->escape( $name ),
								$wpdb->escape( $format ));
							$wpdb->query($query_ins);
							echo '<div id="message" class="updated fade"><p><strong>'.__('Format added',"wp-download_monitor").'</strong></p></div>';
							$ins_format=true;
						}
					}
					$show=true;
				break;
				case "deleteformat" :
					wp_cache_flush();
					$id = $_GET['id'];
					// Delete
					$query_delete = sprintf("DELETE FROM %s WHERE id=%s;",
						$wpdb->escape( $wp_dlm_db_formats ),
						$wpdb->escape( $id ));
					$wpdb->query($query_delete);					
					echo '<div id="message" class="updated fade"><p><strong>'.__('Format deleted Successfully',"wp-download_monitor").'</strong></p></div>';
					$ins_format=true;
					$show=true;
				break;
		}
	}
	if (!isset($show)) $show = false;
	
	if ( ($show==true) || ( empty($action) ) )
	{
	
	?>
	<br class="" style="clear: both;"/>
    <div id="poststuff" class="dlm meta-box-sortables">
            <div class="postbox <?php if (!$ins_format) echo 'close-me';?> dlmbox">
            <h3><?php _e('Custom Output Formats',"wp-download_monitor"); ?></h3>
            <div class="inside" style="padding:8px 16px 16px">
            	<?php _e('<p>This allows you to define formats in which to output your downloads however you want.</p>',"wp-download_monitor"); ?>                
                <form action="<?php echo admin_url('admin.php?page=dlm_config&amp;action=formats'); ?>" method="post">
                    <table class="widefat"> 
                        <thead>
                            <tr>
                            	<th scope="col"><?php _e('ID',"wp-download_monitor"); ?></th>
                                <th scope="col"><?php _e('Name',"wp-download_monitor"); ?></th>
                                <th scope="col"><?php _e('Format',"wp-download_monitor"); ?></th>
                                <th scope="col" style="text-align:center"><?php _e('Action',"wp-download_monitor"); ?></th>
                            </tr>
                        </thead>
                        <tbody id="the-list">
                        	<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<tr><td style="vertical-align:middle;">'.$f->id.'</td><td style="vertical-align:middle;">'.$f->name.'</td>
										<td style="vertical-align:middle;"><input type="hidden" value="'.$f->id.'" name="formatfieldid[]" /><textarea name="formatfield[]" style="width:100%;" rows="2">'.htmlspecialchars($f->format).'</textarea></td>
										<td style="text-align:center;vertical-align:middle;"><a href="'.admin_url('admin.php?page=dlm_config&amp;action=deleteformat&amp;id='.$f->id).'"><img src="'.WP_CONTENT_URL.'/plugins/download-monitor/img/cross.png" alt="'.__('Delete',"wp-download_monitor").'" title="'.__('Delete',"wp-download_monitor").'" /></a></td></tr>';
									}
								} else {
									echo '<tr><td colspan="3">'.__('No formats exist',"wp-download_monitor").'</td></tr>';
								}
							?>
                        </tbody>
                    </table>
                    <p class="submit" style="margin:0; padding-bottom:0;"><input name="savef" type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>

                	<h4 style="font-size:1.4em"><?php _e('Add format',"wp-download_monitor"); ?></h4>                	
                	
                	<table class="niceblue small-table" cellpadding="0" cellspacing="0">
                	    <tr>
                	        <th scope="col"><?php _e('Name',"wp-download_monitor"); ?>:</th>
                	        <td><input type="text" name="format_name" /></td>
                	    </tr>
                	    <tr>
                	        <th scope="col"><?php _e('Format',"wp-download_monitor"); ?>:</th>
                	        <td><input type="text" name="format" style="width:360px;" /></td>
                	    </tr>
                	</table>
                	<p class="submit"><input type="submit" value="<?php _e('Add',"wp-download_monitor"); ?>" /></p>
                	
                	<h5 style="float:left; color: #fff; background: #8A8A8A; padding: 8px; margin: 0;"><?php _e('Available Tags',"wp-download_monitor"); ?></h5>
                	<div style="margin:0 0 12px;height:150px;overflow:auto;border:2px solid #8A8A8A;padding:4px; clear:both;">
	                	<p><?php _e('Use the following tags in your custom formats: <em>note</em> if you use <code>"</code> (quote) characters within the special attributes e.g. <code>"before"</code> you should either escape them or use html entities.',"wp-download_monitor"); ?></p>
	                	<ul>
		                	<li><code>{url}</code> - <?php _e('Url of download (does not include hyperlink)',"wp-download_monitor"); ?></li>
		                	<li><code>{id}</code> - <?php _e('ID of download',"wp-download_monitor"); ?></li>
		                	<li><code>{user}</code> - <?php _e('Username of whoever posted download',"wp-download_monitor"); ?></li>
		                	<li><code>{version}</code> - <?php _e('Version of download',"wp-download_monitor"); ?></li>
		                	<li><code>{version,"before","after"}</code> - <?php _e('Version of download. Not outputted if none set. Replace "before" with preceding text/html and "after" with succeeding text/html.',"wp-download_monitor"); ?></li>
		                	<li><code>{title}</code> - <?php _e('Title of download',"wp-download_monitor"); ?></li>
		                	<li><code>{size}</code> - <?php _e('Filesize of download',"wp-download_monitor"); ?></li>
		                	<li><code>{categories}</code> - <?php _e('Outputs comma separated list of categories.',"wp-download_monitor"); ?></li>
		                	<li><code>{categories, "<em>link</em>"}</code> - <?php _e('Outputs comma separated list of categories contained in a link with the href you define. <code>%</code> replaced with category id. <code>%2</code> replaced with category name. This can be used with the <code>[download_page]</code>.',"wp-download_monitor"); ?></li>
		                	<li><code>{category,"before","after"}</code> <?php _e('or',"wp-download_monitor"); ?> <code>{category}</code> - <?php _e('(Top Level/First) Download Category. Replace "before" with preceding text/html and "after" with succeeding text/html.',"wp-download_monitor"); ?></li>
		                	<li><code>{category_other,"before","after"}</code> <?php _e('or',"wp-download_monitor"); ?> <code>{category_other}</code> - <?php _e('(Top Level/First) Download Category (but if no category is set "other" is returned. Replace "before" with preceding text/html and "after" with succeeding text/html.',"wp-download_monitor"); ?></li>
		                	<li><code>{category_ID}</code> - <?php _e('(Top Level/First) Download Category ID.',"wp-download_monitor"); ?></li>
		                	<li><code>{hits}</code> - <?php _e('Current hit count',"wp-download_monitor"); ?></li>
		                	<li><code>{hits,"No hits","1 Hit","% hits"}</code> - <?php _e('Formatted hit count depending on hits. <code>%</code> replaced with hit count.',"wp-download_monitor"); ?></li>
		                	<li><code>{image_url}</code> - <?php _e('URL of the download image',"wp-download_monitor"); ?></li>
		                	<li><code>{description,"before","after"}</code> <?php _e('or',"wp-download_monitor"); ?> <code>{description}</code> - <?php _e('Description you gave download. Not outputted if none set. Replace "before" with preceding text/html and "after" with succeeding text/html.',"wp-download_monitor"); ?></li>
		                	<li><code>{description-autop,"before","after"}</code> <?php _e('or',"wp-download_monitor"); ?> <code>{description-autop}</code> - <?php _e('Description formatted with autop (converts double line breaks to paragraphs)',"wp-download_monitor"); ?></li>
		                	<li><code>{date,"Y-m-d"}</code> - <?php _e('Date posted. Second argument is for date format.',"wp-download_monitor"); ?></li>
		                	<li><code>{tags}</code> - <?php _e('Outputs comma separated list of tags.',"wp-download_monitor"); ?></li>
		                	<li><code>{tags, "<em>link</em>"}</code> - <?php _e('Outputs comma separated list of tags contained in a link with the href you define. <code>%</code> replaced with tag id. <code>%2</code> replaced with tag name.',"wp-download_monitor"); ?></li>
		                	<li><code>{thumbnail}</code> - <?php _e('Output thumbnail URL (or placeholder)',"wp-download_monitor"); ?></li>
		                	<li><code>{meta-<em>key</em>}</code> - <?php _e('Custom field value',"wp-download_monitor"); ?></li>
		                	<li><code>{meta-autop-<em>key</em>}</code> - <?php _e('Custom field value formatted with autop',"wp-download_monitor"); ?></li>
		                	<li><code>{filetype}</code> - <?php _e('File extension (e.g. "zip")',"wp-download_monitor"); ?></li>
		                	<li><code>{filetype_icon}</code> - <?php _e('File extension icon (16x16)',"wp-download_monitor"); ?></li>
		                	<li><code>{mirror-1-url}</code> - <?php _e('Output a Mirror\'s url',"wp-download_monitor"); ?></li>
		                </ul>
	                </div>	                
	                <h5 style="float:left; color: #fff; background: #8A8A8A; padding: 8px; margin: 0;"><?php _e('Example Formats',"wp-download_monitor"); ?></h5>
	                <div style="margin:0;height:150px;overflow:auto;border:2px solid #8A8A8A;padding:4px; clear:both;">
	                	<p><?php _e('Here are some example custom formats you can use or modify.',"wp-download_monitor"); ?></p>
	                	<ul>
	                    	<li><?php _e('Link and description of download with hits in title:',"wp-download_monitor"); ?> &ndash; <code>&lt;a href="{url}" title="Downloaded {hits} times"&gt;{title}&lt;/a&gt; - {description}</code></li>
	                    	<li><?php _e('Standard link with no hits:',"wp-download_monitor"); ?> &ndash; <code>&lt;a href="{url}"&gt;{title}&lt;/a&gt;</code></li>
	                    	<li><?php _e('Image link:',"wp-download_monitor"); ?> &ndash; <code>&lt;a href="{url}"&gt;&lt;img src="{image_url}" alt="{title}" /&gt;&lt;/a&gt;</code></li>
	                    </ul>
	                </div>                    
                </form>
            </div>
        </div>
        <div class="postbox <?php if (!$save_url) echo 'close-me';?> dlmbox">
            <h3><?php _e('Custom Download URL',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<?php _e('<p>Set a custom url for your downloads, e.g. <code>download/</code>. You can also choose how to link to the download in it\'s url, e.g. selecting "filename" would make the link appear as <code>http://yoursite.com/download/filename.zip</code>. This option will only work if using wordpress permalinks (other than default).</p>
            	
                        <p>Leave this option blank to use the default download path (<code>/download-monitor/download.php?id=</code>)</p>
                        <p>If you fill in this option ensure the custom directory does not exist on the server nor does it match a page or post\'s url as this can cause problems redirecting to download.php.</p>',"wp-download_monitor"); ?>
                 
                 <div style="display:block; width:716px; clear:both; margin:12px auto 4px; border:3px solid #eee; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
                 <p style="background:#eee;padding:4px; margin:0;"><strong><?php _e('Without Custom URL:',"wp-download_monitor"); ?></strong></p>
                 <img style="padding:8px" src="<?php echo $wp_dlm_root; ?>img/explain.gif" alt="Explanation" />
                 </div>
                 
                 <div style="display:block; width:716px; clear:both; margin:12px auto 4px; border:3px solid #eee; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
                 <p style="background:#eee;padding:4px; margin:0;"><strong><?php _e('With Custom URL (downloads/ID):',"wp-download_monitor"); ?></strong></p>
                 <img style="padding:8px" src="<?php echo $wp_dlm_root; ?>img/explain2.gif" alt="Explanation" /></div>
                
                <form action="<?php echo admin_url('admin.php?page=dlm_config&amp;action=saveurl'); ?>" method="post">
                    <table class="niceblue form-table">
                        <tr>
                            <th scope="col"><strong><?php _e('Custom URL',"wp-download_monitor"); ?>:</strong></th>
                            <td><?php echo get_bloginfo('url'); ?>/<input type="text" name="url" value="<?php echo $dlm_url; ?>" />            
                            <select name="type" style="width:150px;padding:2px !important;cursor:pointer;">
                                    <option<?php if ($downloadtype=="ID") echo ' selected="selected" '; ?> value="ID"><?php _e('ID',"wp-download_monitor"); ?></option>
                                    <option<?php if ($downloadtype=="Title") echo ' selected="selected" '; ?> value="Title"><?php _e('Title',"wp-download_monitor"); ?></option>
                                    <option<?php if ($downloadtype=="Filename") echo ' selected="selected" '; ?> value="Filename"><?php _e('Filename',"wp-download_monitor"); ?></option>
                            </select></td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox <?php if (!$save_opt) echo 'close-me';?> dlmbox">
            <h3><?php _e('General Options',"wp-download_monitor"); ?></h3>
            <div class="inside">               
                <form action="<?php echo admin_url('admin.php?page=dlm_config&amp;action=saveoptions'); ?>" method="post">
                    <table class="niceblue form-table">
                       
                        <tr>
                            <th scope="col"><?php _e('Disable the news/links/donate box on the Edit Download Page?',"wp-download_monitor"); ?>:</th>
                            <td>
	                            <select name="wp_dlm_disable_news" id="wp_dlm_disable_news">
	                            	<option value="yes" <?php
	                            		if (get_option('wp_dlm_disable_news')=='yes') echo 'selected="selected" ';
	                            	?>><?php _e('Yes',"wp-download_monitor"); ?></option>
	                        		<option value="no" <?php
	                            		if (!get_option('wp_dlm_disable_news') || get_option('wp_dlm_disable_news')=='no') echo 'selected="selected" ';
	                            	?>><?php _e('No',"wp-download_monitor"); ?></option>                           	
	                            </select>                          
                            </td>
                        </tr>  
                        <tr>
                            <th scope="col"><?php _e('"Download not found" redirect URL',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_does_not_exist'); ?>" name="wp_dlm_does_not_exist" /> <span class="setting-description"><?php _e('Leave blank for no redirect.',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Member-only files non-member redirect',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_member_only'); ?>" name="wp_dlm_member_only" /> <span class="setting-description"><?php _e('Leave blank for no redirect.',"wp-download_monitor"); ?> <?php _e('Note: <code>{referrer}</code> will be replaced with current url. Useful if sending user to the login page and then back to the download :) e.g. <code>http://yourdomain.com/wp-login.php?redirect_to={referrer}</code>.',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Global member only files',"wp-download_monitor"); ?>:</th>
                            <td>
                            	<select name="wp_dlm_global_member_only" id="wp_dlm_global_member_only">                            		
                            		<option value="no" <?php
                            			if (get_option('wp_dlm_global_member_only')=='no') echo 'selected="selected" ';
                            		?>><?php _e('No',"wp-download_monitor"); ?></option>  
                            		<option value="yes" <?php
                            			if (get_option('wp_dlm_global_member_only')=='yes') echo 'selected="selected" ';
                            		?>><?php _e('Yes',"wp-download_monitor"); ?></option>                         	
                            	</select> <span class="setting-description"><?php _e('Makes all downloads member only, ignoring the individual download member only setting.',"wp-download_monitor"); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Download image path',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_image_url'); ?>" name="wp_dlm_image_url" /> <span class="setting-description"><?php _e('This image is used when using the <code>#image</code> download tag and the <code>{image_url}</code> tag on this page. Please use an absolute url (e.g. <code>http://yoursite.com/image.gif</code>).',"wp-download_monitor"); ?></span></td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Default output format',"wp-download_monitor"); ?>:</th>
                            <td><select name="wp_dlm_default_format" id="wp_dlm_default_format">
                            	<option value="0"><?php _e('None',"wp-download_monitor"); ?></option>
                        	<?php								
								$query_select_formats = sprintf("SELECT * FROM %s ORDER BY id;",
									$wpdb->escape( $wp_dlm_db_formats ));	
								$formats = $wpdb->get_results($query_select_formats);
			
								if (!empty($formats)) {
									foreach ( $formats as $f ) {
										echo '<option ';
										if (get_option('wp_dlm_default_format')==$f->id) echo 'selected="selected" ';
										echo 'value="'.$f->id.'">'.$f->name.'</option>';
									}
								}
							?>                            	
                            </select></td>
                        </tr>
                        <tr>
	                        <th scope="col"><?php _e('Auto-select mirror',"wp-download_monitor"); ?>:</th>
	                        <td>
	                        	<select name="wp_dlm_auto_mirror" id="wp_dlm_auto_mirror">
	                        		<option value="yes" <?php
	                        			if (get_option('wp_dlm_auto_mirror')=='yes') echo 'selected="selected" ';
	                        		?>><?php _e('Yes',"wp-download_monitor"); ?></option>
	                        		<option value="no" <?php
	                        			if (get_option('wp_dlm_auto_mirror')=='no') echo 'selected="selected" ';
	                        		?>><?php _e('No',"wp-download_monitor"); ?></option>                           	
	                        	</select> <span class="setting-description"><?php _e('If a download has "mirrors" set should download.php automatically pick one?',"wp-download_monitor"); ?></span>
	                        </td>
	                    </tr>
                        <tr>
                            <th scope="col"><?php _e('Log Downloads',"wp-download_monitor"); ?>:</th>
                            <td>
	                            <select name="wp_dlm_log_downloads" id="wp_dlm_log_downloads">
	                            	<option value="yes" <?php
	                            		if (get_option('wp_dlm_log_downloads')=='yes') echo 'selected="selected" ';
	                            	?>><?php _e('Yes',"wp-download_monitor"); ?></option>
	                        		<option value="no" <?php
	                            		if (get_option('wp_dlm_log_downloads')=='no') echo 'selected="selected" ';
	                            	?>><?php _e('No',"wp-download_monitor"); ?></option>                           	
	                            </select>               
                            </td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Log Timeout',"wp-download_monitor"); ?>:</th>
                            <td>
                            	<input type="text" value="<?php echo get_option('wp_dlm_log_timeout'); ?>" name="wp_dlm_log_timeout" /> <span class="setting-description"><?php _e('0 means all downloads are logged. Increase to set the timeout in minutes so that downloads by the same person are not logged multiple times.',"wp-download_monitor"); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Log/Count Blacklist',"wp-download_monitor"); ?>:</th>
                            <td>
                            	<textarea cols="15" rows="5" name="wp_dlm_ip_blacklist"><?php echo get_option('wp_dlm_ip_blacklist'); ?></textarea> <span class="setting-description"><?php _e('List IP addresses here that you wish to exclude from the logs/counts - 1 per line.',"wp-download_monitor"); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('Enable File Browser',"wp-download_monitor"); ?>:</th>
                            <td>
	                            <select name="wp_dlm_enable_file_browser" id="wp_dlm_enable_file_browser">
	                            	<option value="yes" <?php
	                            		if (get_option('wp_dlm_enable_file_browser')=='yes') echo 'selected="selected" ';
	                            	?>><?php _e('Yes',"wp-download_monitor"); ?></option>
	                            	<option value="no" <?php
	                            		if (get_option('wp_dlm_enable_file_browser')=='no') echo 'selected="selected" ';
	                            	?>><?php _e('No',"wp-download_monitor"); ?></option>                           	
	                            </select> 
                            </td>
                        </tr>
                        <tr>
                            <th scope="col"><?php _e('File Browser Root',"wp-download_monitor"); ?>:</th>
                            <td><input type="text" value="<?php echo get_option('wp_dlm_file_browser_root'); ?>" name="wp_dlm_file_browser_root" /> <span class="setting-description"><?php _e('The root directory the file browser can display.',"wp-download_monitor"); ?></span></td>
                        </tr> 
                        
                       <?php /* Playing <tr>
                            <th scope="col"><?php _e('&ldquo;Edit&rdquo; page role requirement',"wp-download_monitor"); ?>:</th>
                            <td><select name="edit_role">
                            	<?php
                            		$rolenames = $wp_roles->get_names();
                            		foreach ($rolenames as $key=>$role) {
                            			echo '<option ';
                            			
                            			echo ' value="'.$key.'">'.$role.'</option>';
                            		}
                            	?>
                            </select> and above.</td>
                        </tr> */ ?>
                         
                    </table>
                    <p class="submit"><input type="submit" value="<?php _e('Save Changes',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
        <div class="postbox close-me dlmbox">
            <h3><?php _e('Advanced Stuff',"wp-download_monitor"); ?></h3>
            <div class="inside">
            	<h4><?php _e('Upgrade from 3.2.3',"wp-download_monitor"); ?></h4>
            	<?php _e('<p>Download monitor uses new tables from version 3.3 onwards; this was to clean things up and add multiple category support.</p>',"wp-download_monitor"); ?>
                <?php _e('<p>This update should have been done when you activated the plugin, but if it didn\'t, use this function to create the new tables and import from the old ones.</p>',"wp-download_monitor"); ?>
                <form action="<?php echo admin_url('admin.php?page=dlm_config&amp;action=upgrade'); ?>" method="post">
                    <p class="submit"><input type="submit" value="<?php _e('Upgrade Database',"wp-download_monitor"); ?>" /></p>
                </form>
                <hr/>
                <h4><?php _e('Upgraded Successfully? Cleanup!',"wp-download_monitor"); ?></h4>
            	<?php _e('<p>As stated above, tables were renamed from 3.3 onwards - if the upgrade has been successful (woo) you may use this function to delete the old tables (I left them there as a backup).</p>',"wp-download_monitor"); ?>
                <?php _e('<p>WARNING: THIS MAY DELETE DOWNLOAD DATA IN THE DATABASE; BACKUP YOUR DATABASE FIRST!</p>',"wp-download_monitor"); ?>
                <form action="<?php echo admin_url('admin.php?page=dlm_config&amp;action=cleanup'); ?>" method="post">
                    <p class="submit"><input type="submit" value="<?php _e('Clean me up Scotty',"wp-download_monitor"); ?>" /></p>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript">
		<!--
		<?php
			global $wp_db_version;
			if ($wp_db_version >= 9872) {
				echo "jQuery('.postbox h3').before('<div class=\"handlediv\" title=\"".__('Click to toggle',"wp-download_monitor")."\"><br /></div>');";
			} else {
				echo "jQuery('.postbox h3').prepend('<a class=\"togbox\">+</a> ');";
			}
		?>
		
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
	</script>
	<?php
	}
	
	echo '</div></div>';
}

?>