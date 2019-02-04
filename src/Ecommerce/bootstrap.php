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
require_once( 'functions.php' );

/**
 * Only add following things in the admin
 */
if ( is_admin() ) {

	// Setup the write panels (meta boxes)
	$write_panels = new \Never5\DownloadMonitor\Ecommerce\Admin\WritePanels();
	$write_panels->setup();

	// Admin pages
	$order_page = new \Never5\DownloadMonitor\Ecommerce\Admin\Pages\Orders();
	$order_page->setup();

}

/**
 * Setup Access manager
 */
$access_manager = new \Never5\DownloadMonitor\Ecommerce\Access\Manager();
$access_manager->setup();

/**
 * Setup Cart hooks
 */
$cart_hooks = new \Never5\DownloadMonitor\Ecommerce\Cart\Hooks();
$cart_hooks->setup();

/**
 * Setup shortcodes
 */
$cart = new \Never5\DownloadMonitor\Ecommerce\Shortcode\Cart();
$cart->register();

$checkout = new \Never5\DownloadMonitor\Ecommerce\Shortcode\Checkout();
$checkout->register();

/**
 * Setup assets
 */
$assets = new \Never5\DownloadMonitor\Ecommerce\Util\Assets();
$assets->setup();

/**
 * Setup AJAX
 */
$ajax = new \Never5\DownloadMonitor\Ecommerce\Ajax\Manager();
$ajax->setup();

/**
 * Run setup for every enabled payment gateway
 */
add_action( 'init', function () {
	\Never5\DownloadMonitor\Ecommerce\Services\Services::get()->service( 'payment_gateway' )->setup_gateways();
} );
