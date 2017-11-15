<?php

class DLM_Reports_Page {

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 12 );
	}

	/**
	 * Add settings menu item
	 */
	public function add_admin_menu() {
		// Settings page
		add_submenu_page( 'edit.php?post_type=dlm_download', __( 'Reports', 'download-monitor' ), __( 'Reports', 'download-monitor' ), 'dlm_view_reports', 'download-monitor-reports', array(
			$this,
			'view'
		) );
	}

	/**
	 * Get Reports page URL
	 *
	 * @return string
	 */
	public function get_url() {
		return add_query_arg( array(
			'tab'   => $this->get_current_tab(),
			'chart' => $this->get_current_chart()
		), admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-reports' ) );
	}

	/**
	 * Get date range for data
	 *
	 * @return array
	 */
	private function get_date_range() {
		return array(
			'from' => '2017-11-01',
			'to'   => '2017-11-30'
		);
	}

	/**
	 * Generate data repo retrieve filters
	 *
	 * @return array
	 */
	private function generate_data_filters() {
		return array();
	}

	/**
	 * Get log items based on filters
	 *
	 * @return string
	 */
	public function get_chart_data() {
		/** @var DLM_Log_Item_Repository $repo */
		$repo     = download_monitor()->service( 'log_item_repository' );
		$data     = $repo->retrieve_grouped_count( $this->generate_data_filters() );
		$data_map = array();
		foreach ( $data as $data_row ) {
			$data_map[ $data_row->date ] = $data_row->amount;
		}

		$range = $this->get_date_range();

		$startDate = new DateTime( $range['from'] );
		$endDate   = new DateTime( $range['to'] );

		$data_formatted = array();

		while ( $startDate != $endDate ) {

			if ( isset( $data_map[ $startDate->format( "d-m-Y" ) ] ) ) {
				$data_formatted[] = absint( $data_map[ $startDate->format( "d-m-Y" ) ] );
			} else {
				$data_formatted[] = 0;
			}

			$startDate->modify( "+1 day" );
		}

		return '[ { title: "", color: "blue", values: [' . implode( ',', $data_formatted ) . ']}]';
	}

	/**
	 * Get current tab
	 *
	 * @return string
	 */
	private function get_current_tab() {
		return ( ! empty( $_GET['tab'] ) ) ? $_GET['tab'] : "totals";
	}

	/**
	 * Get current tab
	 *
	 * @return string
	 */
	private function get_current_chart() {
		return ( ! empty( $_GET['chart'] ) ) ? $_GET['chart'] : "line";
	}

	/**
	 * Char button
	 */
	private function chart_button() {
		$other_chart = ( "line" == $this->get_current_chart() ) ? "bar" : "line";
		echo "<a title='" . sprintf( __( "Switch to %s", 'download-monitor' ), $other_chart ) . "' href='" . add_query_arg( array(
				'tab'   => $this->get_current_tab(),
				'chart' => $other_chart,
			), $this->get_url() ) . "' class='button dlm-reports-header-chart-switcher dlm-" . $other_chart . "'></a>";
	}

	/**
	 * Date range filter element
	 */
	private function date_range_filter() {
	    $date_range = $this->get_date_range();
	    $start = new DateTime($date_range['from']);
	    $end = new DateTime($date_range['to']);
		?>
        <div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
            <input type="text" class="dlm-input-daterange" name="start" value="<?php echo $start->format("d M Y"); ?>"/>
            <span class="dlm-input-sep">to</span>
            <input type="text" class="dlm-input-daterange" name="end" value="<?php echo $end->format("d M Y"); ?>"/>
        </div>
		<?php
	}

	/**
	 * Generate labels
	 *
	 * @return string
	 */
	private function generate_labels() {

		$range = $this->get_date_range();

		$startDate = new DateTime( $range['from'] );
		$endDate   = new DateTime( $range['to'] );

		$labels = array();

		while ( $startDate != $endDate ) {
			$labels[] = $startDate->format( "j M Y" );
			$startDate->modify( "+1 day" );
		}

		return '["' . implode( '","', $labels ) . '"]';
	}

	/**
	 * Display page
	 */
	public function view() {

		$tabs = array(
			'totals'       => __( 'Totals', 'download-monitor' ),
			'per_download' => __( 'Per Download', 'download-monitor' ),
		);

		$current_tab = $this->get_current_tab();

		?>
        <div class="wrap dlm-reports">
            <div id="icon-edit" class="icon32 icon32-posts-dlm_download"><br/></div>

            <h1><?php
				_e( 'Download Reports', 'download-monitor' );
				echo '<div class="dlm-reports-actions">';
				$this->chart_button();
				$this->date_range_filter();
				echo "</div>";
				?></h1>

            <h2 class="nav-tab-wrapper">
				<?php
				foreach ( $tabs as $tab_key => $tab ) {
					echo "<a href='" . add_query_arg( array( 'tab' => $tab_key ), $this->get_url() ) . "' class='nav-tab" . ( ( $tab_key === $current_tab ) ? " nav-tab-active" : "" ) . "'>" . $tab . "</a>";
				}
				?>
            </h2>
            <div class="dlm-reports-chart" id="dlm-reports-chart"></div>

            <div class="dlm-reports-table"></div>

            <script type="text/javascript">
				jQuery( document ).ready( function ( $ ) {
					// Javascript
					var data = {
						labels: <?php echo $this->generate_labels(); ?>,
						datasets: <?php echo $this->get_chart_data(); ?>
					};

					<?php echo 'var chart = new Chart( {
						parent: "#dlm-reports-chart",
						title: "",
						data: data,
						type: "' . $this->get_current_chart() . '",
						height: 250,
						x_axis_mode: "tick",
						y_axis_mode: "span",
						is_series: 1,
						format_tooltip_x: d => (d + "").toUpperCase(),
						format_tooltip_y: d => d + " downloads"
						} );'; ?>

					$( '#dlm-date-range-picker .dlm-input-daterange' ).datepicker( {
						dateFormat: "dd M yy"
					} );
				} );

            </script>
        </div>
		<?php
	}
}