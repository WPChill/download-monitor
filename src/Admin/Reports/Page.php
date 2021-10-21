<?php

class DLM_Reports_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item
		add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );

		// setup Reports AJAX calls
		$ajax = new DLM_Reports_Ajax();
		$ajax->setup();
	}

	/**
	 * Add settings menu item
	 */
	public function add_admin_menu( $links ) {

		// Reports page page
		$links[] = array(
				'page_title' => __( 'Reports', 'download-monitor' ),
				'menu_title' => __( 'Reports', 'download-monitor' ),
				'capability' => 'dlm_view_reports',
				'menu_slug'  => 'download-monitor-reports',
				'function'   => array( $this, 'view' ),
				'priority'   => 50,
		);

		return $links;
	}

	/**
	 * Date range filter element
	 */
	private function date_range_button() {

		$to_date = new DateTime( current_time( "mysql" ) );
		$to_date->setTime( 0, 0, 0 )->modify( '-1 day' );
		$to   = $to_date->format( 'Y-m-d' );
		$from = $to_date->modify( '-1 month' )->format( 'Y-m-d' );

		$start      = new DateTime( $to );
		$end        = new DateTime( $from );
		?>
		<div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span class="date-range-info"><?php echo $start->format( 'M d, Y' ) . " to " . $end->format( 'M d, Y' ); ?></span>
			<span class="dlm-arrow"></span>
		</div>
		<?php
	}

	/**
	 * Display page
	 */
	public function view() {

		?>
		<div class="wrap dlm-reports">
			<div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

			<h1><?php
				_e( 'Download Reports', 'download-monitor' );
				echo '<div class="wp-clearfix text-right"><div class="dlm-reports-actions">';
				$this->date_range_button();
				echo "</div></div>";
				?></h1>
			<br/>
			<?php do_action( 'dlm_reports_page_start' ); ?>
			<div class="dlm-reports-block dlm-reports-block-summary"
				 id="total_downloads_summary">
				<ul>
					<li id="total"><label>Total
							Downloads</label><span></span>
					</li>
					<li id="average"><label>Daily Average
							Downloads</label><span></span>
					</li>
					<li id="popular"><label>Most Popular
							Download</label><span></span>
					</li>
				</ul>
			</div>
			<div class="total_downloads_chart-wrapper">
				<canvas class="dlm-reports-block-chart"
						id="total_downloads_chart"></canvas>
			</div>

			<div id="total_downloads_table_wrapper">
				<div class="dlm-reports-block dlm-reports-block-table"
					 id="total_downloads_table" data-page="0">
					<span class="dlm-reports-placeholder-no-data">NO DATA</span>
				</div>
				<div id="downloads-block-navigation">
					<button class="button button-primary hidden"><?php esc_html_e( 'Prev 15', 'download-monitor' ); ?></button>
					<button class="button button-primary hidden"
							data-action="load-more"><?php esc_html_e( 'Next 15', 'download-monitor' ); ?></button>
				</div>
			</div>

			<?php do_action( 'dlm_reports_page_end' ); ?>
		</div>
		<?php
	}
}