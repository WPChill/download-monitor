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
		// Add Download postmeta downloads to the Download download count.
		add_filter( 'dlm_add_meta_download_count', array( $this, 'add_meta_download_count' ), 30, 2 );
		// If the DB upgrade functionality did not take place we won't have the option stored.
		$this->upgrade_option = get_option( 'dlm_db_upgraded' );

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
                SELECT SUM( meta_value ) FROM $wpdb->postmeta
                LEFT JOIN $wpdb->posts on $wpdb->postmeta.post_id = $wpdb->posts.ID
                WHERE meta_key = '_download_count'
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
	 * @param  mixed $filters Filters for the query.
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

		if ( apply_filters( 'dlm_backwards_compatibility_orderby_meta', false ) ) {
			add_filter( 'dlm_admin_sort_columns', array( $this, 'no_log_query_args_compatibility' ), 15, 1 );
			add_filter( 'dlm_query_args_filter', array( $this, 'no_log_query_args_compatibility' ), 15, 1 );
			return;
		}

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
		add_filter( 'dlm_admin_sort_columns', array( $this, 'query_args_download_count_compatibility' ), 60 );
		add_filter( 'dlm_query_args_filter', array( $this, 'query_args_download_count_compatibility' ), 60 );
		add_filter( 'posts_join', array( $this, 'join_download_count_compatibility' ) );
		// @todo: delete this filter and function after feedback, as version 4.7.0 doesn't need it.
		// add_filter( 'posts_where', array( $this, 'where_download_count_compatibility' ) );
		add_filter( 'posts_groupby', array( $this, 'groupby_download_count_compatibility' ) );
		add_filter( 'posts_fields', array( $this, 'select_download_count_compatibility' ) );
		add_filter( 'posts_orderby', array( $this, 'orderby_download_count_compatibility' ) );

	}

	/**
	 * Add custom table to query JOIN
	 *
	 * @since 4.6.0
	 *
	 * @param  mixed $join The join query part.
	 * @return string
	 */
	public function join_download_count_compatibility( $join ) {
		global $wpdb;

		$join .= " LEFT JOIN {$wpdb->dlm_downloads} ON ({$wpdb->posts}.ID = {$wpdb->dlm_downloads}.download_id) LEFT JOIN ( SELECT {$wpdb->postmeta}.meta_value, {$wpdb->postmeta}.post_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = '_download_count') as meta_downloads  ON ( meta_downloads.post_id = {$wpdb->posts}.ID )";

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
	 * @since 4.6.0
	 *
	 * @param  mixed $fields The fields query part.
	 * @return string
	 */
	public function select_download_count_compatibility( $fields ) {

		global $wpdb;
		if ( apply_filters( 'dlm_count_meta_downloads', true ) ) {
			$fields .= ", {$wpdb->dlm_downloads}.download_count, (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) +   IFNULL( meta_downloads.meta_value, 0 ) ) total_downloads";
		} else {
			$fields .= ", {$wpdb->dlm_downloads}.download_count, (  IFNULL( {$wpdb->dlm_downloads}.download_count, 0 ) ) total_downloads";
		}

		return $fields;
	}

	/**
	 * Group by download_id from download_log in order to properly return our downloads
	 *
	 * @param [type] $group_by
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
	 * @since 4.6.0
	 *
	 * @param  mixed $orderby The orderby string which we overwrite.
	 * @return string
	 */
	public function orderby_download_count_compatibility( $orderby ) {

		$order = 'DESC';

		if ( isset( $this->filters['order'] ) ) {

			$order = $this->filters['order'];
		}

		return ' total_downloads ' . $order;

	}

	/**
	 * Let's reset the query if we have completed our display of downloads, removing our added filters.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function reset_postdata() {

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
	 * Backwards compatiblity for query args if user wants to still order by post meta
	 *
	 * @param [type] $query_args
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
}
