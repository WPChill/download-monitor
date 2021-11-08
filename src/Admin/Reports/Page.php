<?php

/**
 * DLM_Reports_Page class
 */
class DLM_Reports_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item.
		if ( DLM_Logging::is_logging_enabled() ) {
			add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );
		}

	}

	/**
	 * Add settings menu item
	 *
	 * @param  mixed $links The links for the menu.
	 * @return array
	 */
	public function add_admin_menu( $links ) {

		// Reports page page.
		$links[] = array(
			'page_title' => __( 'Insights', 'download-monitor' ),
			'menu_title' => __( 'Insights', 'download-monitor' ),
			'capability' => 'dlm_view_reports',
			'menu_slug'  => 'download-monitor-reports',
			'function'   => ( DLM_DB_Upgrader::check_if_migrated() ) ? array( $this, 'view' ) : array( $this, 'upgrade_dv_view' ),
			'priority'   => 50,
		);

		return $links;
	}

	/**
	 * Date range filter element
	 */
	private function date_range_button() {

		$to_date = new DateTime( current_time( 'mysql' ) );
		$to_date->setTime( 0, 0, 0 );
		$to   = $to_date->format( 'Y-m-d' );
		$from = $to_date->modify( '-1 month' )->format( 'Y-m-d' );

		$end   = new DateTime( $to );
		$start = new DateTime( $from );
		?>
		<div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' to ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
			<span class="dlm-arrow"></span>
		</div>
		<?php
	}

	/**
	 * Display page
	 */
	public function view() {

		?>
		<div class="wrap dlm-reports wp-clearfix">
			<div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

			<h1>
				<?php
					esc_html_e( 'Download Reports', 'download-monitor' );
					echo '<div class="wp-clearfix text-right"><div class="dlm-reports-actions">';
					$this->date_range_button();
					echo '</div></div>';
				?>
			</h1>
			<br/>
			<?php do_action( 'dlm_reports_page_start' ); ?>
		
			<div class="total_downloads_chart-wrapper">
				<canvas class="dlm-reports-block-chart"
						id="total_downloads_chart"></canvas>
			</div>

			<div class="dlm-reports-block dlm-reports-block-summary"
				 id="total_downloads_summary">			
				<ul>
					<li><span><?php esc_html_e( 'General info', 'download-monitor' ); ?></span></li>
					<li id="total"><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span><label><?php esc_html_e( 'Total Downloads', 'download-monitor' ); ?></label></li>
					<li id="average"><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span><label><?php esc_html_e( 'Daily Average Downloads', 'download-monitor' ); ?></label></li>
					<li id="popular"><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span><label><?php esc_html_e( 'Most Popular Download', 'download-monitor' ); ?></label></li>
					<li id="today"><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span><label><?php esc_html_e( 'Today Downloads', 'download-monitor' ); ?></label></li>
				</ul>
			</div>

			<div id="total_downloads_table_wrapper">
				<div id="downloads-block-navigation">
					<button class="button button-primary hidden dashicons dashicons-arrow-left-alt2" disabled="disabled" title="<?php esc_html_e( 'Previous 15 downloads', 'download-monitor' ); ?>"></button>
					<button class="button button-primary hidden dashicons dashicons-arrow-right-alt2" data-action="load-more" title="<?php esc_html_e( 'Next 15 downloads', 'download-monitor' ); ?>"></button>
				</div>
				<div class="dlm-reports-block dlm-reports-block-table"
					 id="total_downloads_table" data-page="0">		
					 <span class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></span>
				</div>
			</div>

			<?php do_action( 'dlm_reports_page_end' ); ?>
		</div>
		<?php
	}

	/**
	 * Upgrade DB View
	 *
	 * @return void
	 */
	public function upgrade_dv_view() {

		?>
		<div class="wrap">
			<hr class="wp-header-end">
			<div class="main">
				<h3><?php esc_html_e( 'Please upgrade the database in order to further use Download Monitor\'s reports page.', 'download-monitor' ); ?></h3>	
			</div>
			</div>
		<?php
	}
}
