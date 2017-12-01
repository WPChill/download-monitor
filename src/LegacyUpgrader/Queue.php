<?php

class DLM_LU_Queue {

	const TABLE = 'legacy_upgrade_queue';

	/**
	 * Get legacy tables
	 *
	 * @return array
	 */
	private function get_legacy_tables() {
		global $wpdb;

		return array(
			'files'   => $wpdb->prefix . "download_monitor_files",
			'tax'     => $wpdb->prefix . "download_monitor_taxonomies",
			'rel'     => $wpdb->prefix . "download_monitor_relationships",
			'formats' => $wpdb->prefix . "download_monitor_formats",
			'stats'   => $wpdb->prefix . "download_monitor_stats",
			'log'     => $wpdb->prefix . "download_monitor_log",
			'meta'    => $wpdb->prefix . "download_monitor_file_meta"
		);
	}

	/**
	 * Get the queue table
	 *
	 * @return string
	 */
	private function get_queue_table() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create database table if not exists
	 *
	 * @return bool
	 */
	private function create_table_if_not_exists() {
		global $wpdb;

		// create table
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->get_queue_table() . "` ( `legacy_id` INT NOT NULL , `new_id` INT NULL DEFAULT NULL , `processing` DATETIME NULL DEFAULT NULL , `done` DATETIME NULL DEFAULT NULL , PRIMARY KEY (`legacy_id`)) ;";
		$r   = $wpdb->query( $sql );

		return ( false === $r );
	}

	/**
	 * Build queue of downloads that need upgrading
	 *
	 * @return bool
	 */
	public function build_queue() {
		global $wpdb;

		// create database table if not exists
		$this->create_table_if_not_exists();

		// legacy tables we're fetching from
		$legacy_tables = $this->get_legacy_tables();

		// fetch legacy downloads that aren't in our queue
		$legacy_downloads = $wpdb->get_results( "SELECT F.`ID` FROM `{$legacy_tables['files']}` F LEFT JOIN `" . $this->get_queue_table() . "` Q ON F.ID=Q.legacy_id WHERE Q.legacy_id IS NULL ;" );

		// loop and insert into queue
		if ( count( $legacy_downloads ) > 0 ) {
			foreach ( $legacy_downloads as $legacy_download ) {
				$wpdb->insert( $this->get_queue_table(), array( 'legacy_id' => $legacy_download->ID ) );
			}
		}

		return true;
	}

	/**
	 * Get queue of downloads that need upgrading.
	 * This means we only return items that aren't currently upgrading or are already upgraded.
	 *
	 * @return array
	 */
	public function get_queue() {
		global $wpdb;

		return $wpdb->get_col( "SELECT `legacy_id` AS `id` from `" . $this->get_queue_table() . "` WHERE `new_id` IS NULL AND `processing` IS NULL AND `done` IS NULL " );
	}

	/**
	 * Mark download as currently being upgraded
	 *
	 * @param int $legacy_id
	 *
	 * @return bool
	 */
	public function mark_download_upgrading( $legacy_id ) {
		return true;
	}

	/**
	 * Mark download as successfully upgraded
	 *
	 * @param int $legacy_id
	 * @param int $new_id
	 *
	 * @return bool
	 */
	public function mark_download_upgraded( $legacy_id, $new_id ) {
		return true;
	}

}