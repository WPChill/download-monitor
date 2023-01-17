<?php

/**
 * -----------------------------------------------------------------------------------------------------------------
 *
 *                                  DOWNLOAD MONITOR E-COMMERCE BOOTSTRAP FILE
 *
 * -----------------------------------------------------------------------------------------------------------------
 *
 * THIS FILE SETS UP ALL DOWNLOAD MONITOR E-COMMERCE RELATED THINGS.
 * DO NOT DIRECTLY EDIT THIS FILE (OR ANY OTHER FILES IN THIS DIRECTORY).
 *
 * -----------------------------------------------------------------------------------------------------------------
 *
 * THIS FILE IS AUTOMATICALLY INCLUDED WHEN THE E-COMMERCE FEATURE IS ENABLED AND ALL REQUIREMENTS ARE MET
 * DO NOT INCLUDE THIS FILE MANUALLY, THIS WILL BREAK YOUR WEBSITE.
 *
 * -----------------------------------------------------------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Import functions file
 */
require_once( plugin_dir_path( DLM_PLUGIN_FILE ) . 'src/Shop/functions.php' );

if ( ! dlm_is_shop_enabled() ) {
	return;
}

/**
 * Setup product post type
 */
$post_type = new \WPChill\DownloadMonitor\Shop\Util\PostType();
$post_type->setup();

/**
 * Only add following things in the admin
 */
if ( is_admin() ) {

	// Setup Shop Admin Helper
	$shop_admin_helper = \WPChill\DownloadMonitor\Shop\Admin\ShopAdminHelper::get_instance();

	// Setup the write panels (meta boxes)
	$write_panels = new \WPChill\DownloadMonitor\Shop\Admin\WritePanels();
	$write_panels->setup();

	// Admin pages
	$order_page = new \WPChill\DownloadMonitor\Shop\Admin\Pages\Orders();
	$order_page->setup();

	// Product table columns
	$columns = new \WPChill\DownloadMonitor\Shop\Admin\ProductTableColumns();
	$columns->setup();

	// Download Option
	$download_option = new \WPChill\DownloadMonitor\Shop\Admin\DownloadOption();
	$download_option->setup();

}

/**
 * Setup Template Inejctor
 */
$template_injector = new \WPChill\DownloadMonitor\Shop\Util\TemplateInjector();
$template_injector->init();


/**
 * Setup Access manager
 */
$access_manager = new \WPChill\DownloadMonitor\Shop\Access\Manager();
$access_manager->setup();

/**
 * Setup Cart hooks
 */
$cart_hooks = new \WPChill\DownloadMonitor\Shop\Cart\Hooks();
$cart_hooks->setup();

/**
 * Setup shortcodes
 */
$shortcode_cart = new \WPChill\DownloadMonitor\Shop\Shortcode\Cart();
$shortcode_cart->register();

$shortcode_checkout = new \WPChill\DownloadMonitor\Shop\Shortcode\Checkout();
$shortcode_checkout->register();


$shortcode_buy = new \WPChill\DownloadMonitor\Shop\Shortcode\Buy();
$shortcode_buy->register();

/**
 * Setup assets
 */
$assets = new \WPChill\DownloadMonitor\Shop\Util\Assets();
$assets->setup();

/**
 * Setup AJAX
 */
$ajax = new \WPChill\DownloadMonitor\Shop\Ajax\Manager();
$ajax->setup();

/**
 * Run setup for every enabled payment gateway
 */
add_action( 'init', function () {
	\WPChill\DownloadMonitor\Shop\Services\Services::get()->service( 'payment_gateway' )->setup_gateways();
} );
