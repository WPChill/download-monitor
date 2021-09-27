<?php

class DLM_Reports_Chart {

	private $current_period;
	private $date_range;

	private $data;

	public function __construct( $data, $date_range, $current_period ) {
		$this->data           = $data;
		$this->date_range     = $date_range;
		$this->current_period = $current_period;
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
	 * @return array
	 */
	public function generate_chart_data() {

		$range = $this->date_range;
		$data_map = array();

		foreach ( $this->data as $data_row ) {
			$data_map[ $data_row->value ] = $data_row->amount;
		}

		$data_formatted = array();

		$format = $this->get_current_date_format();

		$startDate = new DateTime( $range['from'] );
		$endDate   = new DateTime( $range['to'] );

		$labels = array();

		$format_label = "j M Y";
		if ( 'month' === $this->current_period ) {
			$format_label = "M";
		}

		while ( $startDate <= $endDate ){

			//$labels[] = $startDate->format( $format_label );

			if ( isset( $data_map[ $startDate->format( $format ) ] ) ) {

				$data_formatted[] = array(
					'x' => $startDate->format( $format_label ),
					'y' => absint( $data_map[ $startDate->format( $format ) ] )
				);
			} else {
				$data_formatted[] = array( 'x' => $startDate->format( $format_label ), 'y' => 0 );
			}

			$startDate->modify( "+1 " . $this->current_period );

		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'      => __( 'Downloads', 'download-monitor' ),
					'color'      => '#000fff',
					'data'       => $data_formatted,
					'fill'       => array(
						'target' => 'origin',
						'above'  => 'rgba(255, 0, 206, 0.3)',
					),
					'elements'   => array(
						'line'  => array(
							'borderColor'     => 'rgb(255,0,206)',
							'borderWidth'     => 2,
						),
						'point' => array()
					),
					'normalized' => true,
					'spanGaps'   => true,
					'parsing'    => false,
					'animation'  => false
				),
			),
		);
	}
}