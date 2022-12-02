<?php

/**
 * TODO:
 * - pagination improvements, like WooCommerce does it: https://www.download-monitor.com/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue
 * In a future version, we could also have comparison here and potentially the ability to chart these values by clicking on them and displaying them in comparison on a chart
 */

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
		add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );

		// Set this action on order for other plugins/themes to tap into our tabs.
		add_action( 'admin_init', array( $this, 'set_tabs' ) );
		add_action( 'dlm_page_header_links', array( $this, 'header_reports_settings' ) );

	}

	/**
	 * Set our insights page navigation tabs
	 *
	 * @return void
	 * @since 4.5.0
	 *
	 */
	public function set_tabs() {

		$this->tabs = apply_filters( 'dlm_insights_navigation', array(
			'general_info' => array(
				'tab_label'   => esc_html__( 'Overview', 'download-monitor' ),
				// Label to be displayed on tab nav.
				'description' => esc_html__( 'General information about your downloads', 'download-monitor' ),
				// Description to be displayed on tab nav.
				'callback'    => array( $this, 'general_info' ),
				// The callback to display the content.
				'priority'    => 10,
				// Tab priority.
			),
		) );

		$this->tabs['user_reports'] = array(
			'tab_label'   => esc_html__( 'User reports', 'download-monitor' ),
			// Label to be displayed on tab nav.
			'description' => esc_html__( 'Reports based on user activity', 'download-monitor' ),
			// Description to be displayed on tab nav.
			'callback'    => array( $this, 'user_reports' ),
			// The callback to display the content.
			'priority'    => 20,
			// Tab priority.
		);

		uasort( $this->tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );
	}

	/**
	 * Add settings menu item
	 *
	 * @param mixed $links The links for the menu.
	 *
	 * @return array
	 */
	public function add_admin_menu( $links ) {
		// If Reports are disabled don't add the menu item.
		if ( ! DLM_Logging::is_logging_enabled() ) {
			return $links;
		}

		// Reports page.
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

		$to_date = new DateTime( current_time( 'mysql' ) );
		$to_date->setTime( 0, 0, 0 );
		$to   = $to_date->format( 'Y-m-d' );
		$from = $to_date->modify( '-1 month' )->format( 'Y-m-d' );

		$end   = new DateTime( $to );
		$start = new DateTime( $from );
		?>
		<div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
			<label><?php echo esc_html__( 'Date Range', 'download-monitor' ); ?></label>
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span
				class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' - ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
			<span class="dlm-arrow"></span>
		</div>
		<?php
	}

	/**
	 * The settings for the Reports page
	 *
	 * @return void
	 */
	private function page_settings() {
		$reports_settings = apply_filters( 'dlm_reports_settings', array() );

		if ( empty( $reports_settings ) ) {
			return;
		}
		?>
		<div id="dlm-toggle-settings" class="dashicons dashicons-admin-generic">
			<div class="dlm-toggle-settings__settings reports-block">
				<?php
				foreach ( $reports_settings as $key => $value ) {
					?>
					<div>
						<div class="wpchill-toggle">
							<input class="wpchill-toggle__input" type="checkbox"
							       name="<?php echo esc_attr( $key ); ?>" <?php checked( get_option( $key ), 'on' ); ?>
							       value="on">
							<div class="wpchill-toggle__items">
								<span class="wpchill-toggle__track"></span>
								<span class="wpchill-toggle__thumb"></span>
								<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img"
								     focusable="false" viewBox="0 0 6 6">
									<path
										d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
								</svg>
								<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img"
								     focusable="false" viewBox="0 0 2 6">
									<path d="M0 0h2v6H0z"></path>
								</svg>
							</div>
						</div>
						<label
							for="dlm_reports_page[<?php echo esc_attr( $key ); ?>]"> <?php echo esc_html( $value['label'] ); ?></label>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Insights page header
	 *
	 * @return void
	 */
	public function insights_header() {
		$per_page = ( $item = get_option('dlm-reports-per-page') ) ? $item : 10;
		$per_page_options = array(10, 25, 50, 100);
		?>
		<div class="dlm-insights-header">
			<div class="dlm-insights-navigation">
				<?php
				$this->insights_navigation();
				?>
			</div>
			<div class="dlm-reports-actions">
				<span><?php esc_html_e( 'Show per page:' ); ?>  </span>
				<select class="dlm-reports-per-page">
					<?php
					foreach ( $per_page_options as $option ) {
						echo '<option value="' . absint( $option ) . '" ' . selected( $option, $per_page, false ) . '>' . absint( $option ) . '</option>';
					}
					?>
				</select>

			<div class="dlm-insights-datepicker dlm-reports-actions">
				<?php
				do_action( 'dlm_insights_header' );
				$this->date_range_button();
				$this->page_settings();
				?>
			</div>
			</div>
		</div>
		<!-- Textarea used to decode HTML entities that are retrieved from RESTP API -->
		<textarea id="dlm_reports_decode_area" class="hidden"></textarea>
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
			<li id="<?php echo esc_attr( $key ); ?>"
			    class="dlm-insights-tab-navigation__element <?php echo esc_attr( $active ); ?>">
				<label class="dlm-insights-tab-navigation__label"><?php echo esc_html( $tab['tab_label'] ); ?></label>
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
			<div class="dlm-reports-block dlm-reports-block-summary " id="total_downloads_summary">
				<ul class="reports-block">
					<li id='today'>
						<label><?php esc_html_e( 'Today Downloads', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
					<li id="total">
						<label><?php esc_html_e( 'Total Downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Number of downloads between the selected date range.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
					<li id="average">
						<label><?php esc_html_e( 'Daily Average Downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Average number of downloads between the selected date range.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
					<li id='most_popular'>
						<label><?php esc_html_e( 'Most Downloaded', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'The most downloaded file for the time period.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
				</ul>
			</div>

			<div class="total_downloads_chart-wrapper">
				<canvas class="dlm-reports-block-chart" id="total_downloads_chart"></canvas>
			</div>
		</div>

		<?php $reports = DLM_Reports::get_instance(); ?>
		<div id="total_downloads_table_wrapper2" class="empty dlm-reports-table" data-page="0">
			<?php echo $reports->header_top_downloads_markup(); ?>
			<tbody class="total_downloads_table__list">
			</tbody>
			<?php echo $reports->footer_top_downloads_markup(); ?>
		</div>

		<?php
	}

	/**
	 * Insights page general info content
	 *
	 * @return void
	 */
	public function user_reports() {
		$reports = DLM_Reports::get_instance();
		?>
		<div class="dlm-reports-wrapper">
			<div class="dlm-reports-block dlm-reports-block-summary" id="user_downloads_summary">
				<ul class="reports-block">
					<li id="logged_in">
						<label><?php esc_html_e( 'Logged in downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged in users.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="logged_out">
						<label><?php esc_html_e( 'Logged out/visitor downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged out users or visitors.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="most_active_user">
						<label><?php esc_html_e( 'Most active user', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
				</ul>
			</div>
		</div>

		<div id="users_downloads_table_wrapper">
			<div class="user-downloads-filters">
				<h3 class="user-downloads-filters__heading"><?php echo esc_html__( 'Filter logs by:', 'download-monitor' ); ?></h3>
				<?php
				echo $this->filters();
				?>
			</div>
			<div class="dlm-reports-block dlm-reports-block-table reports-block dlm-reports-table" id="users_download_log" data-page="0">
				<?php
				echo $reports->header_user_logs_markup();
				?>
				<tbody class="user-logs__list"></tbody>
				<?php
				echo $reports->footer_user_logs_markup();
				?>
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

			/**
			 *  Hook mainly used to attach extra content to the tab.
			 */
			do_action( 'dlm_reports_' . $key, $tab );

			$response = ob_get_clean();

			// $response should be escaped in callback function.
			echo '<div id="tab_' . esc_attr( $key ) . '" class="dlm-insights-tab-navigation__content ' . esc_attr( $active ) . '" data-id="' . esc_attr( $key ) . '">' . $response . '</div>'; //phpcs:ignore

		}

	}

	/**
	 * Display page
	 */
	public function view() {

		if ( DLM_DB_Upgrader::do_upgrade() ) {
			/* Upgrade DB View */ ?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="main">
					<h3><?php echo __( 'Hello there! We made some changes on how Download Monitor\'s data is structured. Please <a href="#" class="dlm-db-upgrade-link">upgrade the database</a>.', 'download-monitor' ); ?></h3>
				</div>
			</div>
			<?php
		} else {
			/* Display page */ ?>
			<div class="wrap dlm-reports wp-clearfix">
				<hr class="wp-header-end">
				<div id="icon-edit" class="icon32 icon32-posts-dlm_download"></div>
				<?php $this->insights_header(); ?>
				<?php do_action( 'dlm_reports_page_start' ); ?>
				<?php $this->insights_content(); ?>
				<?php do_action( 'dlm_reports_page_end' ); ?>
			</div>
			<?php
		}
	}

	/**
	 * The reports settings
	 *
	 * @return void
	 */
	public function header_reports_settings() {

		$settings = apply_filters( 'dlm_reports_settings', array(
			'dlm_user_reports' => array(
				'label'       => esc_html__( 'Enable user reports', 'download-monitor' ),
				'description' => esc_html__( 'Toggle to enable or disable the user reports section', 'download-monitor' ),
				'default'     => '1',
				'type'        => 'checkbox',
			),
		) );
	}

	/**
	 * Logs filters
	 *
	 * @return void
	 */
	public function filters() {
		$filters = apply_filters( 'dlm_reports_logs_filters', '' );
		if ( ! empty( $filters ) ) {
			return $filters;
		}
		?>
		<select class="user-downloads-filters__filter dlm-available-with-pro__overlay" data-type="download_status">
			<option value=""><?php echo esc_html__( 'Filter by status', 'download-monitor' ); ?></option>
		</select>
		<a target="_blank" href="https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced-metrics"><span class="dlm-available-with-pro__label">PRO</span></a>
		<select class="user-downloads-filters__filter dlm-available-with-pro__overlay" data-type="user_id">
			<option value=""><?php echo esc_html__( 'Filter by user', 'download-monitor' ); ?></option>
		</select>
		<a target="_blank" href="https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced-metrics"><span class="dlm-available-with-pro__label">PRO</span></a>
		<?php
	}
}
