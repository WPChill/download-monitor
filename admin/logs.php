<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (LOGS)
	
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
// LOG VIEWER PAGE
################################################################################

function wp_dlm_log()
{
	//set globals
	global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies,$wp_dlm_db_formats, $wp_dlm_db_log;
	
	echo '<div class="download_monitor">';
	
	if (isset($_GET['action'])) $action = $_GET['action']; else $action = '';
	if (!empty($action)) {
		switch ($action) {
				case "clear_logs" :
					$wpdb->query("DELETE FROM $wp_dlm_db_log;");
				break;
		}
	}	
	?>
	
    <div class="wrap alternate">
    	<div id="downloadadminicon" class="icon32"><br/></div>
        <h2><?php _e('Download Logs',"wp-download_monitor"); ?></h2>
        <p><a href="<?php echo $wp_dlm_root; ?>/admin/log_to_csv.php" class="button-primary"><?php _e('Download CSV',"wp-download_monitor"); ?></a> <a href="?page=dlm_log&action=clear_logs" class="button" id="dlm_clearlog"><?php _e('Clear Log',"wp-download_monitor"); ?></a></p>
        <table class="widefat"> 
			<thead>
				<tr>
				<th scope="col" style="text-align:center"><?php _e('ID',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('Title',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('File',"wp-download_monitor"); ?></th>
                <th scope="col"><?php _e('User',"wp-download_monitor"); ?></th>
                <th scope="col"><?php _e('IP Address',"wp-download_monitor"); ?></th>
				<th scope="col"><?php _e('Date',"wp-download_monitor"); ?></th>
				</tr>
			</thead>						
		<?php	
				// If current page number, use it 
				if(!isset($_REQUEST['p'])){ 
					$page = 1; 
				} else { 
					$page = $_REQUEST['p']; 
				}
									
				// Figure out the limit for the query based on the current page number. 
				$from = (($page * 20) - 20); 
			
				$paged_select = sprintf("SELECT $wp_dlm_db.*, $wp_dlm_db_log.ip_address, $wp_dlm_db_log.date, $wp_dlm_db_log.user_id
					FROM $wp_dlm_db_log  
					INNER JOIN $wp_dlm_db ON $wp_dlm_db_log.download_id = $wp_dlm_db.id 
					ORDER BY $wp_dlm_db_log.date DESC LIMIT %s,20;",
						$wpdb->escape( $from ));
					
				$logs = $wpdb->get_results($paged_select);
				$total = $wpdb->get_var("SELECT COUNT(*) FROM $wp_dlm_db_log INNER JOIN $wp_dlm_db ON $wp_dlm_db_log.download_id = $wp_dlm_db.id;");
			
				// Figure out the total number of pages. Always round up using ceil() 
				$total_pages = ceil($total / 20);
			
				if (!empty($logs)) {
					echo '<tbody id="the-list">';
					foreach ( $logs as $log ) {
						$date = date_i18n(__("jS M Y H:i:s","wp-download_monitor"), strtotime($log->date));
		
						$path 	= get_bloginfo('wpurl').'/'.get_option('upload_path').'/';
						
						$file = str_replace($path, "", $log->filename);
						$links = explode("/",$file);
						$file = end($links);
						echo '<tr class="alternate">';
						echo '<td style="text-align:center">'.$log->id.'</td>
						<td>'.$log->title.'</td>
						<td>'.$file.'</td>
						<td>';
						if ($log->user_id) {
							$user_info = get_userdata($log->user_id);
				    		echo '<a href="./user-edit.php?user_id='.$user_info->ID.'">'.$user_info->user_login . '</a> (#'.$user_info->ID.')';
				    	}			
						echo '</td>
						<td><a href="http://whois.arin.net/rest/net/NET-'.$log->ip_address.'" target="_blank">'.$log->ip_address.'</a></td>
						<td>'.$date.'</td>';
						
					}
					echo '</tbody>';
				} else echo '<tr><th colspan="6">'.__('No downloads logged.',"wp-download_monitor").'</th></tr>';
		?>			
		</table>

        <div class="tablenav">
        	<div style="float:left" class="tablenav-pages">
				<?php
					if ($total_pages>1)  {
					
						// Build Page Number Hyperlinks 
						if($page > 1){ 
							$prev = ($page - 1); 
							echo "<a href=\"?page=dlm_log&amp;p=$prev\">&laquo; ".__('Previous',"wp-download_monitor")."</a> "; 
						} else echo "<span class='current page-numbers'>&laquo; ".__('Previous',"wp-download_monitor")."</span>";

						for($i = 1; $i <= $total_pages; $i++){ 
							if(($page) == $i){ 
								echo " <span class='page-numbers current'>$i</span> "; 
								} else { 
									echo " <a href=\"?page=dlm_log&amp;p=$i\">$i</a> "; 
							} 
						} 

						// Build Next Link 
						if($page < $total_pages){ 
							$next = ($page + 1); 
							echo "<a href=\"?page=dlm_log&amp;p=$next\">".__('Next',"wp-download_monitor")." &raquo;</a>"; 
						} else echo "<span class='current page-numbers'>".__('Next',"wp-download_monitor")." &raquo;</span>";
						
					}
				?>	
            </div>        	
        </div>
        <br style="clear: both; margin-bottom:1px; height:2px; line-height:2px;" />
    </div>

	</div>
	
	<?php
}
?>