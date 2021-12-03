<?php

/**
 * DLM_Reports_Page class
 */
class DLM_Reports_Page {

	/**
	 * Navigation tabs
	 *
	 * @var mixed
	 */
	public $tabs;

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item.
		if ( DLM_Logging::is_logging_enabled() ) {
			add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );
		}

		// Set this action on order for other plugins/themes to tap into our tabs.
		add_action( 'admin_init', array( $this, 'set_tabs' ) );

	}

	/**
	 * Set our insights page navigation tabs
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function set_tabs() {

		$this->tabs = apply_filters(
			'dlm_insights_navigation',
			array(
				'general_info' => array(
					'tab_label'   => esc_html__( 'General Info', 'download-monitor' ), // Label to be displayed on tab nav.
					'description' => esc_html__( 'General information about your downloads', 'download-monitor' ), // Description to be displayed on tab nav.
					'callback'    => array( $this, 'general_info' ), // The callback to display the content.
					'priority'    => 10, // Tab priority.
				),
			)
		);

		uasort( $this->tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );
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
			'page_title' => __( 'Reports', 'download-monitor' ),
			'menu_title' => __( 'Reports', 'download-monitor' ),
			'capability' => 'dlm_view_reports',
			'menu_slug'  => 'download-monitor-reports',
			'function'   => ( ! DLM_DB_Upgrader::do_upgrade() ) ? array( $this, 'view' ) : array( $this, 'upgrade_db_view' ),
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
	 * Insights page header
	 *
	 * @return void
	 */
	public function insights_header() {
		?>
		<div class="dlm-insights-header">
			<h1 class="dlm-reports-heading"><?php esc_html_e( 'Reports overview', 'download-monitor' ) ?></h1>
			<div class="dlm-insights-navigation">
				<?php
				$this->insights_navigation();
				?>
			</div>
			<div class="dlm-insights-datepicker dlm-reports-actions">				
				<?php
					$this->date_range_button();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Insights page navigation
	 *
	 * @return void
	 */
	public function insights_navigation() {

		if ( empty( $this->tabs ) || count( $this->tabs ) <= 1 ) {
			return;
		}

		echo '<ul class="dlm-insights-tab-navigation">';

		foreach ( $this->tabs as $key => $tab ) {

			$active = '';

			if ( 'general_info' === $key ) {
				$active = 'active';
			}

			?>
			<li id="<?php echo esc_attr( $key ); ?>" class="dlm-insights-tab-navigation__element <?php echo esc_attr( $active ); ?>">
				<label class="dlm-insights-tab-navigation__label"><?php echo esc_html( $tab['tab_label'] ); ?></label>
				<span class="dlm-insights-tab-navigation__description"><?php echo esc_html( $tab['description'] ); ?></span>
			</li>
			<?php
		}

		echo '</ul>';
	}

	/**
	 * Insights page general info content
	 *
	 * @return void
	 */
	public function general_info() {
		?>
		<div class="dlm-reports-wrapper">		
			<div class="dlm-reports-block dlm-reports-block-summary" id="total_downloads_summary">			
			<ul>
				<li id="total"><label><?php esc_html_e( 'Total Downloads', 'download-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'Number of downloads between the selected date range.', 'download-monitor' ); ?></div></div></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>				
				<li id="average"><label><?php esc_html_e( 'Daily Average Downloads', 'download-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'Average number of downloads between the selected date range.', 'download-monitor' ); ?></div></div></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				<!-- 
					<li id="popular"><label><?php esc_html_e( 'Most Popular Download', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				-->
				<li id="today"><label><?php esc_html_e( 'Today Downloads', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
			</ul>
		</div>

		<div class="total_downloads_chart-wrapper">
			<canvas class="dlm-reports-block-chart"	id="total_downloads_chart"></canvas>
		</div>
		</div>


		<div id="total_downloads_table_wrapper" class="empty">			
			<h3><?php esc_html_e( 'Top downloads', 'donwload-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'The most accessed Downloads.', 'download-monitor' ); ?></div></div></h3>		
			<div class="dlm-reports-block dlm-reports-block-table" id="total_downloads_table" data-page="0">					
				<div class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></div>
			</div>
			<div id="downloads-block-navigation">
				<button class="hidden dashicons dashicons-arrow-left-alt2" disabled="disabled" title="<?php esc_html_e( 'Previous 15 downloads', 'download-monitor' ); ?>"></button>
				<button class="hidden dashicons dashicons-arrow-right-alt2" data-action="load-more" title="<?php esc_html_e( 'Next 15 downloads', 'download-monitor' ); ?>"></button>
			</div>	
		</div>
		
		<?php
	}

	/**
	 * Insights page tab content
	 *
	 * @return void
	 */
	public function insights_content() {

		foreach ( $this->tabs as $key => $tab ) {

			if ( ! isset( $tab['callback'] ) ) {
				continue;
			}

			$active = '';

			if ( 'general_info' === $key ) {
				$active = 'active';
			}

			ob_start();
			call_user_func( $tab['callback'] );
			$response = ob_get_clean();

			// $response should be escaped in callback function.
			echo '<div class="dlm-insights-tab-navigation__content ' . esc_attr( $active ) . '" data-id="' . esc_attr( $key ) . '">' . $response . '</div>';

		}

	}

	/**
	 * Display page
	 */
	public function view() {

		?>
		<div class="wrap dlm-reports wp-clearfix">
			<hr class="wp-header-end">
			<div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>		
			<?php $this->insights_header(); ?>
			<br/>
			<?php do_action( 'dlm_reports_page_start' ); ?>
			<?php $this->insights_content(); ?>
			<?php do_action( 'dlm_reports_page_end' ); ?>
		</div>
		<?php
	}

	/**
	 * Upgrade DB View
	 *
	 * @return void
	 */
	public function upgrade_db_view() {

		?>
		<div class="wrap">
			<hr class="wp-header-end">
			<div class="main">
				<h3><?php esc_html_e( 'Please upgrade the database in order to further use Download Monitor\'s insights page.', 'download-monitor' ); ?></h3>	
			</div>
			</div>
		<?php
	}
}
