<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backwards Compatibility class
 *
 * @since 4.6.0
 */
class DLM_Backwards_Compatibility {

	/**
	 * Holds the class object.
	 *
	 * @since 4.6.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The filters for the query.
	 *
	 * @since 4.6.0
	 *
	 * @var object
	 */
	private $filters;

	/**
	 * The upgrade option.
	 *
	 * @since 4.6.0
	 *
	 * @var mixed
	 */
	private $upgrade_option;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// Add post meta count to total downloads.
		add_filter( 'dlm_shortcode_total_downloads', array( $this, 'total_downloads_shortcode' ) );
		// Add orderby postmeta compatibility.
		add_action( 'dlm_query_args', array( $this, 'orderby_compatibility' ), 15, 1 );
		// Reset postdata after the loop.
		add_action( 'dlm_reset_postdata', array( $this, 'reset_postdata' ), 15, 1 );
		// Add version postmeta downloads to the version download count.
		add_filter( 'dlm_add_version_meta_download_count', array( $this, 'add_meta_download_count' ), 15, 2 );
		// Add Download postmeta downloads to the Download's download count.
		add_filter( 'dlm_add_meta_download_count', array( $this, 'add_meta_download_count' ), 30, 2 );
		// If the DB upgrade functionality did not take place we won't have the option stored.
		$this->upgrade_option = get_option( 'dlm_db_upgraded' );
		// Hashes backwards compatibility.
		$this->hashes_compatibility();
		// Compatibility mode for X-Sendfile.
		add_filter( 'dlm_x_sendfile', array( $this, 'x_sendfile_compatibility' ), 5 );
		// Compatibility mode for X-Forwarded-For.
		add_filter( 'dlm_allow_x_forwarded_for', array( $this, 'allow_x_forwarded_compatibility' ), 5 );
		// Compatibility mode for hotlink protection.
		add_filter( 'dlm_hotlink_protection', array( $this, 'hotlink_protection_compatibility' ), 5 );

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Backwards_Compatibility object.
	 *
	 * @since 4.6.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Backwards_Compatibility ) ) {
			self::$instance = new DLM_Backwards_Compatibility();
		}

		return self::$instance;

	}

	/**
	 * Backwards compatibility function for the total_downloads shortcode.
	 *
	 * @param mixed $total Total downloads to be displayed.
	 *
	 * @return mixed
	 *
	 * @since 4.6.0
	 */
	public function total_downloads_shortcode( $total ) {

		global $wpdb;
		$meta_counts = 0;
		// Apply the meta count to the total downloads.
		if ( apply_filters( 'dlm_count_meta_downloads', true ) ) {

			$meta_counts = $wpdb->get_var(
				"
               SELECT SUM( meta_value ) FROM 
                ( SELECT post_id, meta_value, meta_key FROM $wpdb->postmeta WHERE meta_key = '_download_count' GROUP BY post_id ) PM
                LEFT JOIN $wpdb->posts on PM.post_id = $wpdb->posts.ID
                WHERE PM.meta_key = '_download_count'
                AND post_type = 'dlm_download'
                AND post_status = 'publish'
            "
			);
		}

		return absint( $total ) + absint( $meta_counts );
	}

	/**
	 * Order by post meta download_count compatibility
	 *
	 * @param mixed $filters Filters for the query.
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	public function orderby_compatibility( $filters ) {

		global $wpdb;

		if ( ! isset( $filters['post_type'] ) ) {
			return;
		}

		if ( 'dlm_download' !== $filters['post_type'] ) {
			return;
		}

		do_action( 'dlm_backwards_compatibility_orderby_meta_before', $filters );

		if ( apply_filters( 'dlm_backwards_compatibility_orderby_meta', false ) ) {
			add_filter( 'dlm_admin_sort_columns', array( $this, 'no_log_query_args_compatibility' ), 15, 1 );
			add_filter( 'dlm_query_args_filter', array( $this, 'no_log_query_args_compatibility' ), 15, 1 );

			return;
		}

		add_filter( 'posts_fields', array( $this, 'select_download_count_compatibility' ) );
		add_filter( 'posts_join', array( $this, 'join_download_count_compatibility' ) );

		if ( ! DLM_Utils::table_checker( $wpdb->download_log ) || ! DLM_Logging::is_logging_enabled() ) {
			return;
		}

		$download_count_order = false;

		// We should keep this if custom functionality using our retrieve function was made by users / other developers.
		if ( isset( $filters['meta_query'] ) && isset( $filters['meta_query']['orderby_meta'] ) && '_download_count' === $filters['meta_query']['orderby_meta']['key'] ) {
			$download_count_order = true;
		}

		if ( ! empty( $filters ) && isset( $filters['orderby'] ) && isset( $filters['meta_key'] ) && 'meta_value_num' === $filters['orderby'] && '_download_count' === $filters['meta_key'] ) {
			$download_count_order = true;
		}

		if ( ! empty( $filters ) && isset( $filters['order_by_count'] ) && '1' === $filters['order_by_count'] ) {
			$download_count_order = true;
		}

		if ( ! $download_count_order ) {
			return;
		}

		$this->filters = $filters;
		// @todo: Think the below filters are not useful anymore as we changed the SQL query.
		//	add_filter( 'dlm_admin_sort_columns', array( $this, 'query_args_download_count_compatibility' ), 60 );
		//	add_filter( 'dlm_query_args_filter', array( $this, 'query_args_download_count_compatibility' ), 60 );

		// @todo: delete this filter and function after feedback, as version 4.7.0 doesn't need it.
		// add_filter( 'posts_where', array( $this, 'where_download_count_compatibility' ) );
		add_filter( 'posts_groupby', array( $this, 'groupby_download_count_compatibility' ) );
		add_filter( 'posts_orderby', array( $this, 'orderby_download_count_compatibility' ) );
		do_action( 'dlm_backwards_compatibility_orderby_meta_after', $filters );
	}

	/**
	 * Add custom table to query JOIN
	 *
	 * @param mixed $join The join query part.
	 *
	 * @return string
	 * @since 4.6.0
	 *
	 */
	public function join_download_count_compatibility( $join ) {
		global $wpdb;

		if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
			return $join;
		}

		$join .= " LEFT JOIN {$wpdb->dlm_downloads} ON ({$wpdb->posts}.ID = {$wpdb->dlm_downloads}.download_id)";
		$join .= " LEFT JOIN {$wpdb->postmeta} AS meta_downloads ON ({$wpdb->posts}.ID = meta_downloads.post_id AND meta_downloads.meta_key = '_download_count')";

		return $join;
	}

	/**
	 * Add where clause on our query
	 *
	 * @param string $where The query where clause.
	 *
	 * @return string
	 */
	// @todo: delete this filter and function after feedback, as version 4.7.0 doesn't need it.
	public function where_download_count_compatibility( $where ) {
		global $wpdb;

		$where .= " AND {$wpdb->download_log}.download_status IN ('completed', 'redirected') ";

		return $where;
	}

	/**
	 * Add select from custom table to the query fields part
	 *
	 * @param mixed $fields The fields query part.
	 *
	 * @return string
	 * @since 4.6.0
	 *
	 */
	public function select_download_count_compatibility( $fields ) {

		global $wpdb;

		if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
			return $fields;
		}

		if ( apply_filters( 'dlm_count_meta_downloads', true ) ) {
			$fields .= ", {$wpdb->dlm_downloads}.download_count, (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) + 
			IFNULL( meta_downloads.meta_value, 0 ) ) total_downloads, {$wpdb->dlm_downloads}.download_versions as download_versions ";
		} else {
			$fields .= ", {$wpdb->dlm_downloads}.download_count, (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) ) 
			total_downloads, {$wpdb->dlm_downloads}.download_versions as download_versions";
		}

		return $fields;
	}

	/**
	 * Group by download_id from download_log in order to properly return our downloads
	 *
	 * @param [type] $group_by
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	public function groupby_download_count_compatibility( $group_by ) {

		global $wpdb;

		return " {$wpdb->posts}.ID ";

	}

	/**
	 * Add orderby custom table count value
	 *
	 * @param mixed $orderby The orderby string which we overwrite.
	 *
	 * @return string
	 * @since 4.6.0
	 *
	 */
	public function orderby_download_count_compatibility( $orderby ) {

		global $wpdb;
		if ( ! DLM_Utils::table_checker( $wpdb->dlm_downloads ) ) {
			return $orderby;
		}
		$order = 'DESC';

		if ( isset( $this->filters['order'] ) ) {

			$order = $this->filters['order'];
		}

		if ( apply_filters( 'dlm_count_meta_downloads', true ) ) {
			return " (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) + 
			IFNULL( meta_downloads.meta_value, 0 ) ) {$order}";
		} else {
			return " (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) ) {$order}";
		}
	}

	/**
	 * Let's reset the query if we have completed our display of downloads, removing our added filters.
	 *
	 * @return void
	 * @since 4.6.0
	 *
	 */
	public function reset_postdata() {
		do_action( 'dlm_backwards_compatibility_reset_postdata' );

		remove_filter( 'posts_join', array( $this, 'join_download_count_compatibility' ) );
		remove_filter( 'posts_groupby', array( $this, 'groupby_download_count_compatibility' ) );
		remove_filter( 'posts_fields', array( $this, 'select_download_count_compatibility' ) );
		remove_filter( 'posts_orderby', array( $this, 'orderby_download_count_compatibility' ) );
		// @todo: delete this filter and function after feedback, as version 4.7.0 doesn't need it.
		//remove_filter( 'posts_where', array( $this, 'where_download_count_compatibility' ) );
	}

	/**
	 * Backwards compatiblity for query args if using custom functionality
	 *
	 * @param [type] $query_args
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	public function query_args_download_count_compatibility( $filters ) {

		if ( isset( $filters['meta_query'] ) && isset( $filters['meta_query']['orderby_meta'] ) && '_download_count' === $filters['meta_query']['orderby_meta']['key'] ) {

			unset( $filters['meta_query'] );
			unset( $filters['orderby_meta'] );
		}

		if ( ! empty( $filters ) && isset( $filters['orderby'] ) && isset( $filters['meta_key'] ) && 'meta_value_num' === $filters['orderby'] && '_download_count' === $filters['meta_key'] ) {

			unset( $filters['meta_key'] );
			unset( $filters['orderby'] );
		}

		if ( isset( $filters['orderby'] ) && 'download_count' == $filters['orderby'] ) {
			unset( $filters['orderby'] );
		}

		return $filters;
	}

	/**
	 * Backwards compatibility for query args if user wants to still order by post meta
	 *
	 * @param [type] $query_args
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	public function no_log_query_args_compatibility( $filters ) {

		if ( isset( $filters['order_by_count'] ) ) {

			$filters['meta_key'] = '_download_count';
			$filters['orderby']  = 'meta_value_num';
		}

		return $filters;
	}

	/**
	 * Backwards compatibility to take meta count into consideration for Downloads & Versions
	 *
	 * @param [type] $count
	 * @param [type] $download_id
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	public function add_meta_download_count( $counts, $download_id ) {

		// Filter to enable adding meta counts to download counts.
		$count_meta = apply_filters( 'dlm_count_meta_downloads', true );

		if ( ( isset( $this->upgrade_option['using_logs'] ) && '0' === $this->upgrade_option['using_logs'] ) || $count_meta ) {

			if ( 'dlm_download' !== get_post_type( $download_id ) && 'dlm_download_version' !== get_post_type( $download_id ) ) {

				return $counts;
			}

			$meta_counts = get_post_meta( $download_id, '_download_count', true );

			if ( isset( $meta_counts ) && '' !== $meta_counts ) {
				return ( (int) $counts + (int) $meta_counts );
			}
		}

		return $counts;
	}

	/**
	 * Hashes backwards compatibility.
	 *
	 * @return void
	 *
	 * @since 4.9.6
	 */
	private function hashes_compatibility() {
		if ( is_admin() && ! DLM_Admin_Helper::is_dlm_admin_page() ) {
			return;
		}

		// Create the used hashes array.
		$hashes = array( 'md5', 'sha1', 'crc32b', 'sha256' );
		// Cycle through hash types and add filter if option exists.
		foreach ( $hashes as $hash ) {
			if ( '1' == get_option( 'dlm_generate_hash_' . $hash, 0 ) ) {
				// Return true to enable hash generation.
				add_filter( 'dlm_generate_hash_' . $hash, '__return_true', 5 );
			}
		}
	}

	/**
	 * Compatibility mode for X-Sendfile.
	 *
	 * @param  bool  $return  The return value.
	 *
	 * @return bool
	 *
	 * @since 4.9.6
	 */
	public function x_sendfile_compatibility( $return ) {
		// Check if the X-Sendfile option exists in the DB. IF exists, most likely the user had it enabled.
		if ( '1' === get_option( 'dlm_xsendfile_enabled', '0' ) ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Compatibility mode for X-Forwarded-For.
	 *
	 * @param  bool  $return  The return value.
	 *
	 * @return bool
	 *
	 * @since 4.9.6
	 */
	public function allow_x_forwarded_compatibility( $return ) {
		// Check if the X-Forwarded-For option exists in the DB. IF exists, most likely the user had it enabled.
		if ( '1' === get_option( 'dlm_allow_x_forwarded_for', '0' ) ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Compatibility mode for hotlink protection.
	 *
	 * @param  bool  $return  The return value.
	 *
	 * @return bool
	 *
	 * @since 4.9.6
	 */
	public function hotlink_protection_compatibility( $return ) {
		// Check if the hotlink protection option exists in the DB. IF exists, most likely the user had it enabled.
		if ( '1' === get_option( 'dlm_hotlink_protection_enabled', '0' ) ) {
			$return = true;
		}

		return $return;
	}
}
