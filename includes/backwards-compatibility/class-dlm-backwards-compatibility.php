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
	 * Downloads orderby download counts backwards compatibility
	 *
	 * @return array
	 */
	public function orderby_backwards_compatibility() {

		global $wpdb;

		$sql = "SELECT posts.ID, posts.post_title, COUNT(dlm_logs.ID) as counts FROM {$wpdb->posts} posts INNER JOIN {$wpdb->download_log} dlm_logs WHERE posts.post_type = 'dlm_download' AND posts.ID = dlm_logs.download_id GROUP BY posts.ID ORDER BY counts DESC;";

		return $wpdb->get_results( $sql );
	}

}
