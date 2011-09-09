<?php
/*  
	WORDPRESS DOWNLOAD MONITOR - ADMIN (DASHBOARD)
	
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
// Dashboard widgets
################################################################################

// Only for wordpress 2.5 and above
if ($wp_db_version > 6124) {
	
	function dlm_download_stats_widget() {
		global $wp_dlm_db,$wpdb,$wp_dlm_db_stats, $wp_dlm_root;			
				
		// select all downloads 	
			$downloads = $wpdb->get_results("SELECT * FROM $wp_dlm_db ORDER BY title;");
		
		// Get stats for download
		if (isset($_REQUEST['download_stats_id']) && $_REQUEST['download_stats_id']>0) 
			$d = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wp_dlm_db WHERE id = %s LIMIT 1;", $_REQUEST['download_stats_id'] ));
		else 
			$d = (object) array('id' => 0);	
			
		if (isset($_REQUEST['show_download_stats']) && $_REQUEST['show_download_stats']=='monthly')
			$stattype = 'monthly';
		else
			$stattype = 'weekly';
			
		// Get post/get data
		$period = isset($_GET['download_stats_period']) ? $_GET['download_stats_period'] : false;
		if (!$period || !is_numeric($period)) 
			$period = '-1';
		else 
			$period = $period-1;
		
		$mindate = $wpdb->get_var( $wpdb->prepare("SELECT MIN(date) FROM $wp_dlm_db_stats;", $d->id));
		
		if ($stattype=='weekly') {
		
			if ($period<-1) 
				$maxdate = strtotime(($period+1).' week');
			else 
				$maxdate = strtotime(date('Y-m-d'));

			// get stats
			$max = $wpdb->get_var( $wpdb->prepare("SELECT MAX(hits) FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s;", $d->id, date('Y-m-d', strtotime("".$period." week") ), date('Y-m-d', strtotime("".($period+1)." week") ) ));						
		 												
			$stats = $wpdb->get_results( $wpdb->prepare("SELECT *, hits as thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s ORDER BY date ASC LIMIT 7;", $d->id, date('Y-m-d', strtotime("".$period." week") ), date('Y-m-d', strtotime("".($period+1)." week") ) ));
				
			$prev = strtotime(''.$period.' week');
			
			$prevcalc = '+1 day';
			$gapcalc = '-1 day';	
			
			$dateformat = __('D j M',"wp-download_monitor");
			
			$previous_text = '&laquo; '.__('Previous Week',"wp-download_monitor").'';
			$this_text = ''.__('This Week',"wp-download_monitor").'';
			$next_text = ''.__('Next Week',"wp-download_monitor").' &raquo;';
		
		} elseif ($stattype=='monthly') {
		
			$monthperiod = $period*6;
		
			if ($period<-1) 
				$maxdate = strtotime(($monthperiod+6).' month');
			else 
				$maxdate = strtotime(date('Y-m-d'));
				
			// get stats
			$max = $wpdb->get_var( $wpdb->prepare("SELECT MAX(t1.thehits) FROM (SELECT SUM(hits) AS thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s group by month(date)) AS t1;", $d->id, date('Y-m-d', strtotime("".$monthperiod." month") ), date('Y-m-d', strtotime("".($monthperiod+6)." month") ) ));						
		 												
			$stats = $wpdb->get_results( $wpdb->prepare("SELECT *, SUM(hits) as thehits FROM $wp_dlm_db_stats WHERE download_id = %s AND date>=%s AND date<=%s group by month(date) ORDER BY date ASC;", $d->id, date('Y-m-d', strtotime("".$monthperiod." month") ), date('Y-m-d', strtotime("".($monthperiod+6)." month") ) ));
				
			$prev = strtotime(''.$monthperiod.' month');
			
			$prevcalc = '+1 month';	
			$gapcalc = '-1 month';	
			
			$dateformat = __('F Y',"wp-download_monitor");
			
			$previous_text = '&laquo; '.__('Previous 6 months',"wp-download_monitor").'';
			$this_text = ''.__('Last 6 months',"wp-download_monitor").'';
			$next_text = ''.__('Next 6 months',"wp-download_monitor").' &raquo;';
		
		}		

		if (!empty($downloads)) {				

			// Output download select form
			echo '<form action="" method="post" style="margin-bottom:8px"><select name="show_download_stats">';
				echo '<option ';
			
				if (isset($_REQUEST['show_download_stats']) && $_REQUEST['show_download_stats']=='weekly') 
				  echo 'selected="selected" '; 
				
				echo 'value="weekly">'.__('Weekly',"wp-download_monitor").'</option>';
				echo '<option ';
				
				if (isset($_REQUEST['show_download_stats']) && $_REQUEST['show_download_stats']=='monthly') 
				  echo 'selected="selected" '; 
				
				echo 'value="monthly">'.__('Monthly',"wp-download_monitor").'</option>';
			echo '</select><select name="download_stats_id" style="width:50%;"><option value="">'.__('Select a download',"wp-download_monitor").'</option>';
				
				foreach( $downloads as $download )
				{
				  $version = $download->dlversion ? 'v' . $download->dlversion : '';
					
					echo '<option ';
					
					if (isset($_REQUEST['download_stats_id']) && $_REQUEST['download_stats_id']==$download->id) 
					  echo 'selected="selected" '; 
	
					echo 'value="'.$download->id.'">'.$download->id.' - '.$download->title.' '.$version.'</option>';
				}
			echo '</select><input type="submit" value="'.__('Show',"wp-download_monitor").'" class="button" /></form>';
			
			if ($d) {
			
			echo '<div style="text-align:center;overflow:hidden">';
			
			if (strtotime($period.' week')>strtotime($mindate))
				echo '<a style="float:left" href="?download_stats_period='.($period).'&download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$previous_text.'</a>';
			
			if ($period<-1)
				echo '<a style="float:right" href="?download_stats_period='.($period+2).'&download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$next_text.'</a>';
				
			echo '<a style="margin:0 auto; width:100px; display:block" href="?download_stats_id='.$d->id.'&show_download_stats='.$stattype.'">'.$this_text.'</a>';
			
			echo '</div>';				
			echo '<div style="clear:both;margin-bottom:8px"></div>';
					
		?>
		<table class="download_chart" summary="<?php _e('Downloads per day for',"wp-download_monitor"); ?> <?php echo $d->title ?>" cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<th scope="col"><span class="auraltext"><?php _e('Day',"wp-download_monitor"); ?></span> </th>
					<th scope="col"><span class="auraltext"><?php _e('Number of downloads',"wp-download_monitor"); ?></span> </th>
				</tr>
				<?php					
	
					if ($stats) {
					
						$loop = 1;
						
						foreach ($stats as $stat) {						
							$hits = $stat->thehits;
							$date = strtotime($stat->date);
							
							$width = ($hits / $max * 90);
							
							// Fill in gaps
							echo dlm_fill_date_gaps($prev, $date, $gapcalc, $dateformat);
							
							$prev = strtotime($prevcalc, $date);							
							
							echo '
							<tr>			
								<td style="width:25%;">'.date_i18n($dateformat,$date).'</td>
								<td class="value"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="'.$width.'%" />'.$hits.'</td>
							</tr>
							';
							$loop++;
						}
						
					} 
					echo dlm_fill_date_gaps($prev, $maxdate, $gapcalc, $dateformat);
				?>						
		</tbody></table>
		<?php
			}
		
		} else echo '<p>'.__('None Found',"wp-download_monitor").'</p>';
	}
	
	function dlm_download_top_widget() {
		global $wp_dlm_db,$wpdb,$wp_dlm_db_stats, $wp_dlm_root, $download_taxonomies, $wp_dlm_db_relationships;			

		if (isset($_POST['download_cat_id'])) $showing = (int) $_POST['download_cat_id'];
		
		if (isset($showing) && $showing>0) {
		
			$the_cats = array();
			// Traverse through categories to get sub-cats
			if (isset($download_taxonomies->categories[$showing])) {	
				$the_cats = $download_taxonomies->categories[$showing]->get_decendents();
				$the_cats[] = $showing;
			}
			$the_cats = implode(',',$the_cats);	
		
			$downloads = $wpdb->get_results( "SELECT * FROM $wp_dlm_db WHERE $wp_dlm_db.id IN ( SELECT download_id FROM $wp_dlm_db_relationships WHERE taxonomy_id IN ($the_cats) ) ORDER BY hits DESC LIMIT 5;" );	
		} else {
			$downloads = $wpdb->get_results( "SELECT * FROM $wp_dlm_db ORDER BY hits DESC LIMIT 5;" );	
		}
		
		echo '<form action="" method="post" style="margin-bottom:8px"><select name="download_cat_id" style="width:50%;"><option value="">'.__('Select a category',"wp-download_monitor").'</option>';
			$cats = $download_taxonomies->get_parent_cats();
			if (!empty($cats)) {
				foreach ( $cats as $c ) {
					echo '<option ';
					if (isset($showing)  && $showing==$c->id) echo 'selected="selected"';
					echo 'value="'.$c->id.'">'.$c->name.'</option>';
					echo get_option_children_cats($c->id, "$c->name &mdash; ", $showing, 0);
				}
			} 
		echo '</select><input type="submit" value="'.__('Show',"wp-download_monitor").'" class="button" /></form>';		
					
		?>
		<table class="download_chart" style="margin-bottom:0" summary="<?php _e('Most Downloaded',"wp-download_monitor"); ?>" cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<th scope="col"><span class="auraltext"><?php _e('Day',"wp-download_monitor"); ?></span> </th>
					<th scope="col"><span class="auraltext"><?php _e('Number of downloads',"wp-download_monitor"); ?></span> </th>
				</tr>
				<?php
					// get stats
					$max = $wpdb->get_var( "SELECT MAX(hits) FROM $wp_dlm_db");
					$first = 'first';						
					$loop = 1;
					$size = sizeof($downloads);
					$last = "";
					if ($downloads && $max>0) {
						foreach ($downloads as $d) {
							$hits = $d->hits;
							$date = $d->postDate;
							$width = ($hits / $max * 90); // Thanks lggemini
							if ($loop==$size) $last = 'last';
							$version = '';
							if ($d->dlversion) $version = 'v'.$d->dlversion;
							echo '
							<tr>			
								<td class="'.$first.'" style="width:25%;">'.$d->title.' '.$version.'</td>
								<td class="value '.$first.' '.$last.'"><img src="'.$wp_dlm_root.'img/bar.png" alt="" height="16" width="'.$width.'%" />'.$hits.'</td>
							</tr>
							';
							$first = "";
							$loop++;
						}
					} else {
						echo '<tr><td class="first last" style="border-right:1px solid #e5e5e5" colspan="2">'.__('No stats yet',"wp-download_monitor").'</td></tr>';
					}
				?>						
		</tbody></table>
		<?php
	}
	
	// Different handling if supported (2.7 and 2.8)
	//if (function_exists('wp_add_dashboard_widget')) {
	if ($wp_db_version > 8644) {
		
		function dlm_download_stats_widget_setup() {
			if (current_user_can( 'manage_options' )) {
				wp_add_dashboard_widget( 'dlm_download_stats_widget', __( 'Download Stats' ), 'dlm_download_stats_widget' );
				wp_add_dashboard_widget( 'dlm_download_top_widget', __( 'Top 5 Downloads' ), 'dlm_download_top_widget' );
			}
		}
		add_action('wp_dashboard_setup', 'dlm_download_stats_widget_setup');
		
	} else {
	
		// Old Method using Classes	
		class wp_dlm_dash {
		
			// Class initialization
			function wp_dlm_dash() {
				// Add to dashboard
				add_action( 'wp_dashboard_setup', array(&$this, 'register_widget') );
				add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
			}
			// Register the widget for dashboard use
			function register_widget() {
				global $wp_db_version;
				$adminpage = 'admin.php';
				
				wp_register_sidebar_widget( 'download_monitor_dash', __( 'Download Stats', 'wp-download_monitor' ), array(&$this, 'widget') );
			}
			// Insert into dashboard
			function add_widget( $widgets ) {
				global $wp_registered_widgets;
				if ( !isset($wp_registered_widgets['download_monitor_dash']) ) return $widgets;
				array_splice( $widgets, 2, 0, 'download_monitor_dash' );
				return $widgets;
			}
			// Output the widget
			function widget( $args ) {
				if (is_array($args)) extract( $args, EXTR_SKIP );
				echo $before_widget;
				echo $before_title;
				echo $widget_name;
				echo $after_title;
				dlm_download_stats_widget();							
				echo $after_widget;
			}
		}
		add_action( 'plugins_loaded', create_function( '', 'global $wp_dlm_dash; $wp_dlm_dash = new wp_dlm_dash();' ) );
		
		class wp_dlm_dash2 {
		
			// Class initialization
			function wp_dlm_dash2() {
				// Add to dashboard
				add_action( 'wp_dashboard_setup', array(&$this, 'register_widget') );
				add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
			}
			// Register the widget for dashboard use
			function register_widget() {
				global $wp_db_version;
				$adminpage = 'admin.php';
				wp_register_sidebar_widget( 'download_monitor_dash2', __( 'Top 5 Downloads', 'wp-download_monitor' ), array(&$this, 'widget') );
			}
			// Insert into dashboard
			function add_widget( $widgets ) {
				global $wp_registered_widgets;
				if ( !isset($wp_registered_widgets['download_monitor_dash2']) ) return $widgets;
				array_splice( $widgets, 2, 0, 'download_monitor_dash2' );
				return $widgets;
			}
			// Output the widget
			function widget( $args ) {
				if (is_array($args)) extract( $args, EXTR_SKIP );
				echo $before_widget;
				echo $before_title;
				echo $widget_name;
				echo $after_title;
				dlm_download_top_widget();								
				echo $after_widget;
			}
		}
		add_action( 'plugins_loaded', create_function( '', 'global $wp_dlm_dash2; $wp_dlm_dash2 = new wp_dlm_dash2();' ) );
	}
}
?>