<?php

class DLM_Reports_Chart {

	private $current_period;
	private $date_range;
	private $chart_type;

	private $data;

	public function __construct( $data, $chart_type, $date_range, $current_period ) {
		$this->data           = $data;
		$this->chart_type     = $chart_type;
		$this->date_range     = $date_range;
		$this->current_period = $current_period;
	}

	/**
	 * Generate unique string for chart ID
	 *
	 * @return string
	 */
	private function generate_unique_id() {
		return md5( uniqid() );
	}

	/**
	 * Get currently used date format
	 *
	 * @return string
	 */
	private function get_current_date_format() {
		$format = "Y-m-d";
		if ( 'month' === $this->current_period ) {
			$format = "Y-m";
		}

		return $format;
	}

	/**
	 * Generate labels
	 *
	 * @return string
	 */
	private function generate_labels() {

		$range = $this->date_range;

		$startDate = new DateTime( $range['from'] );
		$endDate   = new DateTime( $range['to'] );

		$labels = array();

		$format = "j M Y";
		if ( 'month' === $this->current_period ) {
			$format = "M";
		}

		while ( $startDate <= $endDate ) {
			$labels[] = $startDate->format( $format );
			$startDate->modify( "+1 " . $this->current_period );
		}

		return '["' . implode( '","', $labels ) . '"]';
	}

	/**
	 * Get log items based on filters
	 *
	 * @return string
	 */
	public function generate_chart_data() {

		$data_map = array();
		foreach ( $this->data as $data_row ) {
			$data_map[ $data_row->date ] = $data_row->amount;
		}

		$range = $this->date_range;

		$startDate = new DateTime( $range['from'] );
		$endDate   = new DateTime( $range['to'] );

		$data_formatted = array();

		$format = $this->get_current_date_format();

		while ( $startDate <= $endDate ) {

			if ( isset( $data_map[ $startDate->format( $format ) ] ) ) {
				$data_formatted[] = absint( $data_map[ $startDate->format( $format ) ] );
			} else {
				$data_formatted[] = 0;
			}

			$startDate->modify( "+1 " . $this->current_period );
		}

		return '[ { title: "", color: "blue", values: [' . implode( ',', $data_formatted ) . ']}]';
	}

	/**
	 * Display chart
	 */
	public function display() {
		$id = $this->generate_unique_id();

		?>
        <div class="dlm-reports-chart" id="dlm-reports-chart-<?php echo $id; ?>"></div>

        <script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				// Javascript
				var data = {
					labels: <?php echo $this->generate_labels(); ?>,
					datasets: <?php echo $this->generate_chart_data(); ?>
				};

				<?php echo 'var chart = new Chart( {
						parent: "#dlm-reports-chart-' . $id . '",
						title: "",
						data: data,
						type: "' . $this->chart_type . '",
						height: 250,
						show_dots: 0, 
						x_axis_mode: "tick",
						y_axis_mode: "span",
						is_series: 1,
						format_tooltip_x: d => (d + "").toUpperCase(),
						format_tooltip_y: d => d + " downloads"
						} );'; ?>
			} );

        </script>
		<?php
	}

}