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
		add_action( 'dlm_page_header_links', array( $this, 'header_reports_settings' ) );

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
					'tab_label'       => esc_html__( 'Overview', 'download-monitor' ),
					// Label to be displayed on tab nav.
						'description' => esc_html__( 'General information about your downloads', 'download-monitor' ),
					// Description to be displayed on tab nav.
						'callback'    => array( $this, 'general_info' ),
					// The callback to display the content.
						'priority'    => 10,
					// Tab priority.
				),
			)
		);

		if ( 'off' !== get_option( 'dlm_toggle_user_reports' ) ) {
			$this->tabs['user_reports'] = array(
				'tab_label'       => esc_html__( 'User reports', 'download-monitor' ),
				// Label to be displayed on tab nav.
					'description' => esc_html__( 'Reports based on user activity', 'download-monitor' ),
				// Description to be displayed on tab nav.
					'callback'    => array( $this, 'user_reports' ),
				// The callback to display the content.
					'priority'    => 20,
				// Tab priority.
			);
		}

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
		<a href="<?php echo esc_url( add_query_arg( 'dlm_download_logs', 'true', admin_url('edit.php') ) )  ?>" target="_blank" id="dlm-download-log" class="button button-primary"><?php echo esc_html__( 'Download log', 'download-monitor' ); ?></a>
		<div class="dlm-reports-header-date-selector <?php echo ( 'on' !== get_option( 'dlm_toggle_compare' ) ) ? esc_attr( 'disabled' ) : ''; ?>" id="dlm-date-range-picker__compare">
			<label><?php echo esc_html__( 'Select date to compare', 'download-monitor' ); ?></label>
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' to ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
			<span class="dlm-arrow"></span>
		</div>
		<div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
			<label><?php echo esc_html__( 'Select date', 'download-monitor' ); ?></label>
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' to ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
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
		$reports_settings = apply_filters(
			'dlm_reports_settings',
			array(
				'dlm_toggle_compare'      => array(
					'label'   => 'Compare dates',
					'default' => 'on',
				),
				'dlm_toggle_user_reports' => array(
					'label'   => 'User reports',
					'default' => 'on',
				),
				// Option to clear the cache. Functionality already present
				/*'dlm_clear_api_cache'     => array(
					'label'   => 'Clear reports cache',
					'default' => false,
				),*/
			)
		);
		?>
		<div id="dlm-toggle-settings" class="dashicons dashicons-admin-generic">
			<div class="dlm-toggle-settings__settings reports-block">
				<?php
				foreach ( $reports_settings as $key => $value ) {
					?>
					<div>
						<div class="wpchill-toggle">
							<input class="wpchill-toggle__input" type="checkbox"
								   name="<?php echo esc_attr( $key ); ?>" <?php  checked( get_option( $key, $value['default']  ), 'on'); ?> value="on">
							<div class="wpchill-toggle__items">
								<span class="wpchill-toggle__track"></span>
								<span class="wpchill-toggle__thumb"></span>
								<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img"
									 focusable="false" viewBox="0 0 6 6">
									<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
								</svg>
								<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img"
									 focusable="false" viewBox="0 0 2 6">
									<path d="M0 0h2v6H0z"></path>
								</svg>
							</div>
						</div>
						<label for="dlm_reports_page[<?php echo esc_attr( $key ); ?>]" > <?php echo esc_html( $value['label'] ); ?></label>
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
		?>
		<div class="dlm-insights-header">
			<div class="dlm-insights-navigation">
				<?php
				$this->insights_navigation();
				?>
			</div>
			<div class="dlm-insights-datepicker dlm-reports-actions">
				<?php
					$this->date_range_button();
					$this->page_settings();
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
				<li id="total" class="reports-block"><label><?php esc_html_e( 'Total Downloads', 'download-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'Number of downloads between the selected date range.', 'download-monitor' ); ?></div></div></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				<li id="average" class="reports-block"><label><?php esc_html_e( 'Daily Average Downloads', 'download-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'Average number of downloads between the selected date range.', 'download-monitor' ); ?></div></div></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				<!--
					<li id="popular"><label><?php esc_html_e( 'Most Popular Download', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				-->
				<li id="today" class="reports-block"><label><?php esc_html_e( 'Today Downloads', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
			</ul>
		</div>

		<div class="total_downloads_chart-wrapper">
			<canvas class="dlm-reports-block-chart"	id="total_downloads_chart"></canvas>
		</div>
		</div>


		<div id="total_downloads_table_wrapper" class="empty reports-block half-reports-block">
			<h3><?php esc_html_e( 'Top downloads', 'donwload-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'The most accessed Downloads.', 'download-monitor' ); ?></div></div></h3>
			<div class="dlm-reports-block dlm-reports-block-table" id="total_downloads_table" data-page="0">
				<div class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></div>
			</div>
			<div id="downloads-block-navigation">
				<button class="hidden dashicons dashicons-arrow-left-alt2" disabled="disabled" title="<?php esc_html_e( 'Previous 15 downloads', 'download-monitor' ); ?>"></button>
				<button class="hidden dashicons dashicons-arrow-right-alt2" data-action="load-more" title="<?php esc_html_e( 'Next 15 downloads', 'download-monitor' ); ?>"></button>
			</div>
		</div>
		<?php if ( 'on' === get_option( 'dlm_toggle_user_reports' ) ) { ?>
			<div id="total_downloads_summary_wrapper" class="reports-block half-reports-block">
				<h3><?php esc_html_e( 'Downloads summary', 'donwload-monitor' ); ?><div class="wpchill-tooltip"><i>[?]</i><div class="wpchill-tooltip-content"><?php esc_html_e( 'The most accessed Downloads.', 'download-monitor' ); ?></div></div></h3>
				<div class="half-reports-block">
					<label><?php echo esc_html__( 'Logged in downloads: ', 'download-monitor' ); ?></label>
					<span class="dlm-reports-logged-in"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></span>
				</div>
				<div class="half-reports-block">
					<label><?php echo esc_html__( 'Logged out downloads:', 'download-monitor' ); ?></label>
					<span class="dlm-reports-logged-out"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Insights page general info content
	 *
	 * @return void
	 */
	public function user_reports() {
		?>
		<div class="dlm-reports-wrapper">
			<div class="dlm-reports-block dlm-reports-block-summary" id="user_downloads_summary">
				<ul>
					<li id="logged_in" class="reports-block"><label><?php esc_html_e( 'Logged in downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged in users.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="logged_out" class="reports-block"><label><?php esc_html_e( 'Logged out/visitor downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged out users or visitors.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="most_active_user" class="reports-block">
						<label><?php esc_html_e( 'Most active user', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
				</ul>
			</div>
		</div>


		<div id="users_downloads_table_wrapper" class="empty">
			<div class="user-downloads-filters">
				<h3 class="user-downloads-filters__heading"><?php echo esc_html__( 'Filter logs by:', 'download-monitor' ); ?></h3>
				<select id="dlm-filter-by-status" class="user-downloads-filters__filter" data-type="download_status">
					<option value=""><?php echo esc_html__( 'Filter by status', 'download-monitor' ); ?></option>
					<option value="completed"><?php echo esc_html__( 'Completed', 'download-monitor' ); ?></option>
					<option value="redirected"><?php echo esc_html__( 'Redirected', 'download-monitor' ); ?></option>
					<option value="failed"><?php echo esc_html__( 'Failed', 'download-monitor' ); ?></option>
				</select>
				<select id="dlm-filter-by-user" class="user-downloads-filters__filter" data-type="user_id">
					<option value=""><?php echo esc_html__( 'Filter by user', 'download-monitor' ); ?></option>
				</select>
			</div>
			<div class="dlm-reports-block dlm-reports-block-table reports-block" id="users_download_log" data-page="0">
				<div class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></div>
			</div>
			<div id="user-downloads-block-navigation">
				<button class="hidden dashicons dashicons-arrow-left-alt2" disabled="disabled"
						title="<?php esc_html_e( 'Previous 15', 'download-monitor' ); ?>"></button>
				<button class="hidden dashicons dashicons-arrow-right-alt2" data-action="load-more"
						title="<?php esc_html_e( 'Next 15', 'download-monitor' ); ?>"></button>
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

		if ( DLM_DB_Upgrader::do_upgrade() ) {
			/* Upgrade DB View */
			?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="main">
					<h3><?php esc_html_e( 'Please upgrade the database in order to further use Download Monitor\'s Reports page.', 'download-monitor' ); ?></h3>
				</div>
				</div>
			<?php
		} else {
			/* Display page */
			?>
			<div class="wrap dlm-reports wp-clearfix">
				<hr class="wp-header-end">
				<div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>
				<?php $this->insights_header(); ?>
				<br/>
				<?php do_action( 'dlm_reports_page_start' ); ?>
				<?php $this->insights_content(); ?>
				<?php do_action( 'dlm_reports_page_end' ); ?>
				<div class="dlm-loading-data"><h1><?php esc_html_e( 'Loading data...', 'download-monitor' ); ?></div>
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

		$settings = apply_filters(
			'dlm_reports_settings',
			array(
				'dlm_user_reports' => array(
					'label'       => esc_html__( 'Enable user reports', 'donwload-monitor' ),
					'description' => esc_html__( 'Toggle to enable or disable the user reports section', 'download-monitor' ),
					'default'     => '1',
					'type'        => 'checkbox',
				),
			)
		);
	}

}
