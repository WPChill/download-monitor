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

/**
 * Import functions file
 */
require_once( 'functions.php' );

/**
 * Only add following things in the admin
 */
if ( is_admin() ) {

	// Add e-commerce settings
	$settings = new \Never5\DownloadMonitor\Ecommerce\Admin\Settings();
	$settings->setup();

	// Setup the write panels (meta boxes)
	$write_panels = new \Never5\DownloadMonitor\Ecommerce\Admin\WritePanels();
	$write_panels->setup();

}

/**
 * Setup shortcodes
 */
$cart = new \Never5\DownloadMonitor\Ecommerce\Shortcode\Cart();
$cart->register();