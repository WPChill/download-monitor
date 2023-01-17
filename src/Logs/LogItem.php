<?php

class DLM_Log_Item {

	/** @var int */
	private $id = 0;

	/** @var int */
	private $user_id;

	/** @var string */
	private $user_ip;

	/** @var string */
	private $user_uuid;

	/** @var string */
	private $user_agent;

	/** @var int */
	private $download_id;

	/** @var int */
	private $version_id;

	/** @var string */
	private $version;

	/** @var \DateTime */
	private $download_date;

	/** @var string */
	private $download_status;

	/** @var string */
	private $download_status_message;

	/** @var string */
	private $current_url;

	/** @var array */
	private $meta_data = array();

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * @return string
	 */
	public function get_user_ip() {
		return $this->user_ip;
	}

	/**
	 * @param string $user_ip
	 */
	public function set_user_ip( $user_ip ) {
		$this->user_ip = $user_ip;
	}

	/**
	 * @return string
	 */
	public function get_user_uuid() {
		return $this->user_uuid;
	}

	/**
	 * @param string $user_ip
	 */
	public function set_user_uuid( $user_ip ) {
		$this->user_uuid = md5( $user_ip );
	}

	/**
	 * Get the URL from which the download took part
	 *
	 * @return string
	 * @since 4.6.0
	 */
	public function get_current_url() {
		return $this->current_url;
	}

	/** Set the URL from which the download took part
	 *
	 * @param string $current_url the URL from which the download took part.
	 */
	public function set_current_url( $current_url ) {

		if ( get_option( 'permalink_structure' ) ) {
			$query_url = wp_parse_url( $current_url );
			$current_url = wp_parse_url( $current_url )['path'] . ( isset( $query_url['query'] ) ? '?' . $query_url['query'] : '' );
		} else {
			$current_url = '/' . wp_parse_url( $current_url )['query'];
		}

		$this->current_url = $current_url;
	}

	/**
	 * @return string
	 */
	public function get_user_agent() {
		return $this->user_agent;
	}

	/**
	 * @param string $user_agent
	 */
	public function set_user_agent( $user_agent ) {
		$this->user_agent = $user_agent;
	}

	/**
	 * @return int
	 */
	public function get_download_id() {
		return $this->download_id;
	}

	/**
	 * @param int $download_id
	 */
	public function set_download_id( $download_id ) {
		$this->download_id = $download_id;
	}

	/**
	 * @return int
	 */
	public function get_version_id() {
		return $this->version_id;
	}

	/**
	 * @param int $version_id
	 */
	public function set_version_id( $version_id ) {
		$this->version_id = $version_id;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function set_version( $version ) {
		$this->version = $version;
	}

	/**
	 * @return DateTime
	 */
	public function get_download_date() {
		return $this->download_date;
	}

	/**
	 * @param DateTime $download_date
	 */
	public function set_download_date( $download_date ) {
		$this->download_date = $download_date;
	}

	/**
	 * @return string
	 */
	public function get_download_status() {
		return $this->download_status;
	}

	/**
	 * @param string $download_status
	 */
	public function set_download_status( $download_status ) {
		$this->download_status = $download_status;
	}

	/**
	 * @return string
	 */
	public function get_download_status_message() {
		return $this->download_status_message;
	}

	/**
	 * @param string $download_status_message
	 */
	public function set_download_status_message( $download_status_message ) {
		$this->download_status_message = $download_status_message;
	}

	/**
	 * @return array
	 */
	public function get_meta_data() {
		return $this->meta_data;
	}

	/**
	 * @param array $meta_data
	 */
	public function set_meta_data( $meta_data ) {
		$this->meta_data = $meta_data;
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function add_meta_data_item( $key, $value ) {

		// get meta
		$meta = $this->get_meta_data();

		// just to be sure we have an array
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		// set new meta. We're not checking if it exists, this means we override by default. Check in your code if exists before adding!
		$meta[ $key ] = $value;

		// set meta
		$this->set_meta_data( $meta );
	}

	/**
	 * Checks if meta data exists for given key
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function meta_data_exist( $key ) {
		$meta = $this->get_meta_data();

		return ( is_array( $meta ) && isset( $meta[ $key ] ) );
	}

	/**
	 * Return JSON encoded Download categories
	 *
	 * @param int $download_id The ID of the download.
	 *
	 * @return false|string
	 * @since 4.6.0
	 */
	public function get_download_categories( $download_id ) {

		$terms      = get_the_Terms( $download_id, 'dlm_download_category' );
		$categories = array();

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return wp_json_encode( $categories );
	}

	/**
	 * Increase the version and total download count
	 *
	 * @access public
	 * @return void
	 */
	public function increase_download_count() {
		global $wpdb;

		$user_id   = 0;
		$meta_data = null;

		$lmd = $this->get_meta_data();
		if ( ! empty( $lmd ) ) {
			$meta_data = wp_json_encode( $lmd );
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		$download_date   = current_time( 'mysql', false );
		$download_status = $this->get_download_status();

		// If there is no table we don't need to increase the download count as it will trigger an error.
		// Also, we don't need to update the table if the reports are deactivated.
		if ( DLM_Logging::is_logging_enabled() && DLM_Utils::table_checker( $wpdb->download_log ) ) {

			// Add filters for download_log column entries, so in case the upgrader failed we can still log the download.
			/**
			 * Filter for the download_log columns
			 *
			 * @hooked ( DLM_Logging, log_entries ) Adds uuid, download_category and download_location
			 */
			$log_entries = apply_filters(
				'dlm_log_entries',
				array(
					'user_id'                 => absint( $this->get_user_id() ),
					'user_ip'                 => $this->get_user_ip(),
					'user_agent'              => $this->get_user_agent(),
					'download_id'             => absint( $this->get_download_id() ),
					'version_id'              => absint( $this->get_version_id() ),
					'version'                 => $this->get_version(),
					'download_date'           => sanitize_text_field( $download_date ),
					'download_status'         => $download_status,
					'download_status_message' => $this->get_download_status_message(),
					'meta_data'               => $meta_data
				),
				$this
			);
			/**
			 * Filter for the download_log columns types
			 *
			 * @hooked: ( DLM_Logging, log_values )
			 */
			$log_values = apply_filters(
				'dlm_log_values',
				array(
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s'
				),
				$this
			);

			$result = $wpdb->insert(
				"{$wpdb->download_log}",
				$log_entries,
				$log_values
			);
		}

		// Let's check if table exists.
		if ( DLM_Utils::table_checker( $wpdb->dlm_downloads ) && 'failed' !== $download_status ) {
			// Table exists, now log new download into table. This is used for faster download counts,
			// performance issues introduced in version 4.6.0 of plugin.
			$download_id         = absint( $this->get_download_id() );
			$version_id          = absint( $this->get_version_id() );
			$downloads_table     = "{$wpdb->dlm_downloads}";
			$check_for_downloads = "SELECT * FROM {$downloads_table}  WHERE download_id = %s;";
			$downloads_insert    = "INSERT INTO {$downloads_table} (download_id,download_count,download_versions) VALUES ( %s , %s, %s );";
			$downloads_update    = "UPDATE {$downloads_table} dlm SET dlm.download_count = dlm.download_count + 1, dlm.download_versions = %s WHERE dlm.download_id = %s";
			$check               = $wpdb->get_results( $wpdb->prepare( $check_for_downloads, $download_id ), ARRAY_A );
			$download_versions   = array();
			// Check if there is anything there, else insert new row.
			if ( null !== $check && ! empty( $check ) ) {
				// If meta exists update it, lese insert it.
				$download_versions = ! empty( $check[0]['download_versions'] ) ? json_decode( $check[0]['download_versions'], true ) : array();
				if ( isset( $download_versions[ $version_id ] ) ) {
					$download_versions[ $version_id ] = absint( $download_versions[ $version_id ] ) + 1;
				} else {
					$download_versions[ $version_id ] = 1;
				}
				$wpdb->query( $wpdb->prepare( $downloads_update, json_encode( $download_versions ), $download_id ) );
			} else {
				$download_versions[ $version_id ] = 1;
				$wpdb->query( $wpdb->prepare( $downloads_insert, $download_id, 1, json_encode( $download_versions ) ) );
			}
		}

		do_action( 'dlm_increase_download_count', $this );
	}
}
