<?php

namespace WPChill\DownloadMonitor\Shop\Admin;

class ShopAdminHelper {

	/**
	 * Holds the class object.
	 *
	 * @since 4.5.4
	 *
	 * @var object
	 */
	public static $instance;

	public function __construct() {
		// Admin menus
		add_action( 'admin_menu', array( $this, 'add_shop_menu' ), 20 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The ShopAdminHelper object.
	 *
	 * @since 4.5.4
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof ShopAdminHelper ) ) {
			self::$instance = new ShopAdminHelper();
		}

		return self::$instance;

	}

	/**
	 * Add our menu for Shop menu entry
	 *
	 * @return void
	 *
	 * @since 4.5.4
	 */
	public function add_shop_menu() {

		/**
		 * Hook for Shop menu link
		 *
		 * @hooked Orders orders_menu() - 50
		 */
		$shop_links = apply_filters(
			'dlm_shop_admin_menu_links',
			array()
		);

		uasort( $shop_links, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );

		if ( ! empty( $shop_links ) ) {
			foreach ( $shop_links as $link ) {
				add_submenu_page( 'edit.php?post_type=dlm_product', $link['page_title'], $link['menu_title'], $link['capability'], $link['menu_slug'], $link['function'], $link['priority'] );
			}
		}

	}
}
