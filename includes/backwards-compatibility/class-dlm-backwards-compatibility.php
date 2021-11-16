<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backwards Compatibility class
 *
 * @since 4.5.0
 */
class DLM_Backwards_Compatibility {

	/**
	 * Holds the class object.
	 *
	 * @since 4.5.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The filters for the query.
	 *
	 * @since 4.5.0
	 *
	 * @var object
	 */
	private $filters;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {

		add_filter( 'dlm_shortcode_total_downloads', array( $this, 'total_downloads_shortcode' ) );
		add_action( 'dlm_backwards_compatibility', array( $this, 'orderby_compatibility' ), 15, 1 );
		add_action( 'dlm_reset_postdata', array( $this, 'reset_postdata' ), 15, 1 );

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Backwards_Compatibility object.
	 * @since 4.5.0
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
	 * @param  mixed $total Total downloads to be displayed.
	 * @return mixed
	 */
	public function total_downloads_shortcode( $total ) {

		if ( false === $total ) {
			global $wpdb;

			$total = $wpdb->get_var(
				"
                SELECT SUM( meta_value ) FROM $wpdb->postmeta
                LEFT JOIN $wpdb->posts on $wpdb->postmeta.post_id = $wpdb->posts.ID
                WHERE meta_key = '_download_count'
                AND post_type = 'dlm_download'
                AND post_status = 'publish'
            "
			);
		}

		return $total;

	}

	/**
	 * Order by post meta download_count compatibility
	 *
	 * @param  mixed $filters Filters for the query.
	 * @return void
	 */
	public function orderby_compatibility( $filters ) {

		global $wpdb;

		if ( ! DLM_Utils::table_checker( $wpdb->download_log ) || ! DLM_Logging::is_logging_enabled() ) {
			return;
		}

		$download_count_order = false;

		if ( isset( $filters['meta_query'] ) && isset( $filters['meta_query']['orderby_meta'] ) && '_download_count' === $filters['meta_query']['orderby_meta']['key'] ) {
			$download_count_order = true;
		}

		if ( ! empty( $filters ) && isset( $filters['orderby'] ) && isset( $filters['meta_key'] ) && 'meta_value_num' === $filters['orderby'] && '_download_count' === $filters['meta_key'] ) {
			$download_count_order = true;
		}

		if ( ! $download_count_order ) {
			return;
		}

		$this->filters = $filters;

		add_filter( 'posts_join', array( $this, 'join_download_count_compatibility' ) );
		add_filter( 'posts_fields', array( $this, 'select_download_count_compatibility' ) );
		add_filter( 'posts_orderby', array( $this, 'orderby_download_count_compatibility' ) );

	}

	/**
	 * Add custom table to query JOIN
	 *
	 * @since 4.5.0
	 *
	 * @param  mixed $join The join query part.
	 * @return string
	 */
	public function join_download_count_compatibility( $join ) {
		global $wpdb;

		$join .= " INNER JOIN {$wpdb->download_log} ON ({$wpdb->posts}.ID = {$wpdb->download_log}.download_id) ";

		return $join;

	}

	/**
	 * Add select from custom table to the query fields part
	 *
	 * @since 4.5.0
	 *
	 * @param  mixed $fields The fields query part.
	 * @return string
	 */
	public function select_download_count_compatibility( $fields ) {

		global $wpdb;

		$fields .= ", COUNT({$wpdb->download_log}.ID) as counts ";

		return $fields;
	}

	/**
	 * Add orderby custom table count value
	 *
	 * @since 4.5.0
	 *
	 * @param  mixed $orderby The orderby string which we overwrite.
	 * @return string
	 */
	public function orderby_download_count_compatibility( $orderby ) {

		$order = 'DESC';

		if ( isset( $this->filters['order'] ) ) {

			$order = $this->filters['order'];
		}

		return ' counts ' . $order;

	}

	/**
	 * Let's reset the query if we have completed our display of downloads, removing our added filters.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function reset_postdata() {

		remove_filter( 'posts_join', array( $this, 'join_download_count_compatibility' ) );
		remove_filter( 'posts_fields', array( $this, 'select_download_count_compatibility' ) );
		remove_filter( 'posts_orderby', array( $this, 'orderby_download_count_compatibility' ) );
	}

}
