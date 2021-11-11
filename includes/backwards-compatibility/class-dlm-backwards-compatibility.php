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
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {

		add_filter( 'dlm_shortcode_total_downloads', array( $this, 'total_downloads_shortcode' ) );
		add_filter( 'dlm_dashboard_popular_downloads', array( $this, 'admin_dashboard_backwards_compatibility' ), 15 );
		add_filter( 'posts_join', array( $this, 'orderby_download_count_compatibility' ) );

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
	 * Admin dashboard backwards compatibility
	 *
	 * @param [type] $downloads Array of Downloads object.
	 * @return array
	 */
	public function admin_dashboard_backwards_compatibility( $downloads ) {

		$filters = apply_filters(
			'dlm_admin_dashboard_popular_downloads_filters',
			array(
				'no_found_rows' => 1,
				'orderby'       => array(
					'orderby_meta' => 'DESC',
				),
				'meta_query'    => array(
					'orderby_meta' => array(
						'key'  => '_download_count',
						'type' => 'NUMERIC',
					),
					array(
						'key'     => '_download_count',
						'value'   => '0',
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			),
		);

		if ( false === $downloads ) {
			return download_monitor()->service( 'download_repository' )->retrieve( $filters, 10 );
		}

		return $downloads;
	}

	public function orderby_download_count_compatibility( $join ) {
		global $wpdb;

		$join .= " INNER JOIN {$wpdb->download_log} ON ({$wpdb->posts}.ID = {$wpdb->download_log}.download_id) ";

		return $join;

	/* 	SELECT posts.*, COUNT(dlm_logs.ID) as counts FROM wp_posts posts INNER JOIN wp_postmeta meta ON ( posts.ID = meta.post_id ) INNER JOIN wp_download_log dlm_logs ON (posts.ID = dlm_logs.download_id) WHERE 1=1 AND ( meta.meta_key = '_download_count' ) AND posts.post_type = 'dlm_download' AND ((posts.post_status = 'publish')) GROUP BY posts.ID ORDER BY counts DESC; */

	}

}
