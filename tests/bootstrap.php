<?php


class DLM_Unit_Tests_Bootstrap {

	/** @var \DLM_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment
	 *
	 * @since 2.2
	 */
	public function __construct() {

		ini_set( 'display_errors', 'on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		// load Download Monitor
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_dlm' ) );

		// install Download Monitor
		tests_add_filter( 'setup_theme', array( $this, 'install_dlm' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load testing framework
		$this->includes();
	}

	/**
	 * Load Download Monitor
	 */
	public function load_dlm() {
		require_once( $this->plugin_dir . '/download-monitor.php' );
	}

	/**
	 * Install Download Monitor after the test environment have been loaded
	 */
	public function install_dlm() {

		// clean existing install first
		define( 'WP_UNINSTALL_PLUGIN', true );
//		update_option( 'rp4wp_misc', array( 'clean_on_uninstall' => 1 ) );
//		include( $this->plugin_dir . '/uninstall.php' );

//		$installer = include( $this->plugin_dir . '/includes/installer-functions.php' );
		require_once( 'includes/installer-functions.php' );
		__download_monitor_install();

		echo "Installing Download Monitor..." . PHP_EOL;
	}

	/**
	 * Load specific test cases and factories
	 */
	public function includes() {
		// test cases
		require_once( $this->tests_dir . '/framework/class-dlm-unit-test-case.php' );
	}

	/**
	 * Get the single class instance
	 *
	 * @return DLM_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

DLM_Unit_Tests_Bootstrap::instance();
