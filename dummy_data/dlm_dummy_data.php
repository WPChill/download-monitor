<?php

/**
 * These are the dummy data populating functions.
 *
 * add_dlm_dummy_data() adds dummy data to the report_log table ( so data used inside the Reports page of the plugin ) in order to test the Reports page. To use this you can add it to functions.php of your theme file and attach an action to it, for example wp_head, and visit the website. This way it will populate with data, and when you'll exit the website the function will terminate.
 *
 * add_dlm_dummy_logs() adds dummy data to the download_log table used to test the database tables upgrading process. To use this you can add it to functions.php of your theme file and attach an action to it, for example wp_head, and visit the website. This way it will populate with data, and when you'll exit the website the function will terminate.
 *
 * displayDates() is a function, used by the above two functions, that manipulates the dates that we need to set into the tables of our database. This function does not need a calling.
 */
function add_dlm_dummy_data() {
	global $wpdb;
	$dlms  = get_posts(
		array(
			'post_type'      => 'dlm_download',
			'posts_per_page' => - 1,
		)
	);
	$dates = displayDates( '2019-05-10', '2021-12-06' );

	foreach ( $dates as $date ) {

		$structured_dlm = array();
		foreach ( $dlms as $dlm ) {
			$structured_dlm[ $dlm->ID ] = array(
				'title'     => $dlm->post_title,
				'downloads' => rand( 1, 99999 ),
			);
		}

		$table = "{$wpdb->dlm_reports}";

		$sql_check  = "SELECT * FROM $table  WHERE date = %s;";
		$sql_insert = "INSERT INTO $table (date,download_ids) VALUES ( %s , %s );";
		$sql_update = "UPDATE $table dlm SET dlm.download_ids = %s WHERE dlm.date = %s";
		$check      = $wpdb->get_results( $wpdb->prepare( $sql_check, $date ), ARRAY_A );

		if ( null !== $check && ! empty( $check ) ) {

			$downloads = json_decode( $check[0]['download_ids'], ARRAY_A );

			foreach ( $structured_dlm as $item_key => $item ) {

				if ( isset( $downloads[ $item_key ] ) ) {
					$downloads[ $item_key ]['downloads'] = $downloads[ $item_key ]['downloads'] + $item['downloads'];
					unset( $downloads[ $item_key ]['date'] );
				} else {
					$downloads[ $item_key ] = array(
						'downloads' => $item['downloads'],
						'title'     => $item['title'],
					);
				}
			}

			$wpdb->query( $wpdb->prepare( $sql_update, wp_json_encode( $downloads ), $date ) );

		} else {

			foreach ( $structured_dlm as $item_key => $item ) {

				$downloads[ $item_key ] = array(
					'downloads' => $item['downloads'],
					'title'     => $item['title'],
				);
			}

			$wpdb->query( $wpdb->prepare( $sql_insert, $date, wp_json_encode( $downloads ) ) );
		}
	}

}

function add_dlm_dummy_logs() {

	global $wpdb;

	$dlms  = get_posts(
		array(
			'post_type'      => 'dlm_download',
			'posts_per_page' => - 1,
		)
	);
	$dates = displayDates( '2019-05-10', '2021-12-06' );

	foreach ( $dates as $date ) {

		$structured_dlm = array();
		foreach ( $dlms as $dlm ) {
			$structured_dlm[] = $dlm->ID;
		}

		$table = "{$wpdb->download_log}";

		foreach ( $dlms as $dlm ) {

			$id     = $structured_dlm[ $dlms[ ARRAY_RAND( $dlms ) ] ];
			$agents = array( 'Edge', 'Chrome', 'Firefox', 'Safari' );
			$status = array( 'completed', 'redirected', 'failed' );
			$wpdb->insert(
				$table,
				array(
					'user_id'         => wp_rand( 0, 500 ),
					'user_agent'      => $agents[ array_rand( $agents ) ],
					'download_id'     => $id,
					'version_id'      => $id,
					'download_date'   => $date,
					'download_status' => $status[ array_rand( $status ) ],
					'user_ip'         => '127.0.0.1'

				)
			);
		}
	}
}

function displayDates( $date1, $date2, $format = 'Y-m-d' ) {
	$dates   = array();
	$current = strtotime( $date1 );
	$date2   = strtotime( $date2 );
	$stepVal = '+1 day';
	while ( $current <= $date2 ) {
		$dates[] = date( $format, $current );
		$current = strtotime( $stepVal, $current );
	}

	return $dates;
}
