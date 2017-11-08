<?php

// What is happening?
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit(); // TODO: uncomment this
}

// get option
$clean_up = absint( get_option( 'dlm_clean_on_uninstall', 0 ) );

// check if we need to clean up
if ( 1 === $clean_up ) {

	global $wpdb;

	// WP Download Repository
	$repo = new DLM_WordPress_Download_Repository();

	/**
	 * Fetch all Download ID's
	 */
	$ids = $repo->retrieve( array( 'fields' => 'ids' ) );

	/**
	 * Remove all download meta data
	 */
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE `post_id` IN (" . implode( ",", $ids ) . ");" );

	/**
	 * Remove all downloads
	 */
	$wpdb->query( "DELETE FROM $wpdb->posts WHERE `ID` IN (" . implode( ",", $ids ) . ");" );

	/**
	 * Remove all options
	 */
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'dlm_%';" );

	/**
	 * Drop logs table
	 */
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}download_log ;" );

}