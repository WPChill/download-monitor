<?php

/**
 * The welcome page shown on fresh installs
 */
class DLM_Welcome_Page {

	/**
	 * Holds the class object.
	 *
	 * @since 4.5.9
	 *
	 * @var object
	 */
	public static $instance;


	/**
	 * Primary class constructor.
	 *
	 * @since 4.5.9
	 */
	private function __construct() {

		add_filter( 'dlm_admin_menu_links', array( $this, 'dlm_about_menu' ) );
		add_filter( 'submenu_file', array( $this, 'remove_about_submenu_item' ) );
		add_action( 'dlm_after_install_setup', array( $this, 'dlm_on_activation' ), 15 );
	}


	/**
	 * Add the About submenu
	 *
	 * @param $links
	 *
	 * @return mixed
	 * @since 4.5.9
	 */
	public function dlm_about_menu( $links ) {

		// Register the hidden submenu.
		$links[] = array(
			'page_title' => esc_html__( 'About', 'download-monitor' ),
			'menu_title' => esc_html__( 'About', 'download-monitor' ),
			'capability' => 'manage_options',
			'menu_slug'  => 'download-monitor-about-page',
			'function'   => array( $this, 'about_page' ),
			'priority'   => 45,
		);

		return $links;
	}

	/**
	 * @param $submenu_file
	 * @return mixed
	 *
	 * Remove the About submenu
	 */
	public function remove_about_submenu_item( $submenu_file ) {

		remove_submenu_page( 'edit.php?post_type=dlm_download', 'download-monitor-about-page' );

		return $submenu_file;
	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Welcome_Page object.
	 * @since 4.5.9
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Welcome_Page ) ) {
			self::$instance = new DLM_Welcome_Page();
		}

		return self::$instance;
	}


	/**
	 * Add activation hook. Need to be this way so that the About page can be created and accessed
	 *
	 * @param $first_install
	 * @since 4.5.9
	 */
	public function dlm_on_activation( $first_install ) {

		if ( $first_install ) {
			add_action( 'activated_plugin', array( $this, 'redirect_on_activation' ) );
		}
	}

	/**
	 * Redirect to About page when activated
	 *
	 * @param $plugin
	 * @since 4.5.9
	 */
	public function redirect_on_activation( $plugin ) {

		if ( DLM_FILE === $plugin ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-about-page' ) );
			exit();
		}
	}


	/**
	 * @since 4.5.9
	 * Display About page
	 */
	public function about_page() {

		// WPChill Welcome Class
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

		if ( ! class_exists( 'WPChill_Welcome' ) ) {
			return;
		}

		$welcome = WPChill_Welcome::get_instance();
		?>
			<div id="wpchill-welcome">
	
				<div class="container">
	
					<div class="hero features">
	
						<div class="mascot">
							<img src="<?php echo esc_attr( DLM_URL . 'assets/images/logo.png' ); ?>" alt="<?php esc_attr_e( 'Download Monitor Logo', 'download-monitor' ); ?>">
						</div>
	
						<div class="block">
						<?php $welcome->display_heading( esc_html__( 'Thank you for installing Download Monitor', 'download-monitor' ) ); ?>
						<?php $welcome->display_subheading( esc_html__( 'You\'re just a few steps away from adding, displaying and tracking your first download on your website with the easiest to use WordPress download plugin.', 'download-monitor' ) ); ?>
						</div>
						<div class="button-wrap-single">
						<?php $welcome->display_button( esc_html__( 'Read our step-by-step guide to get started', 'download-monitor' ), 'https://www.download-monitor.com/kb/add-your-first-download/', true, '#7364ff' ); ?>
						</div>
						<div class="block">
						<?php $welcome->layout_start( 2, 'feature-list clear' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Gated content', 'download-monitor' ), esc_html__( 'Make use of forms or lock downloads behind emails to gather leads or require a Twitter share to get the word about your products out into the world.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/gated-content.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Easy data importing/exporting', 'download-monitor' ), esc_html__( ' Import/export all download data including categories, tags and all file versions to and from a CSV file.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/data-importing-exporting.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Link downloads from Cloud', 'download-monitor' ), esc_html__( 'Easily link files from Amazon S3 and Google Drive to your website.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/link-downloads-from-cloud.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Track your content', 'download-monitor' ), esc_html__( 'Gain access to detailed reports to see how your downloads are behaving.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/track-your-content.png' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Content grouping', 'download-monitor' ), esc_html__( 'Easily assign categories, tags or other meta to your downloads.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/content-grouping.png' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Customisable endpoints', 'download-monitor' ), esc_html__( 'For showing appealing download links and engaging buttons.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/customisable-endpoints.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Spam protection', 'download-monitor' ), esc_html__( 'Our smart Captcha extension stop bots from spamming your downloads.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/spam-protection.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Monetize your downloads', 'download-monitor' ), esc_html__( 'Ability to sell your downloads straight from your WordPress website.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/monetize-your-downloads.png' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Instant notifications', 'download-monitor' ), esc_html__( 'Receive instant email notifications whenever someone downloads your content.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/instant-notifications.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Easy duplication', 'download-monitor' ), esc_html__( 'Duplicate downloads including all data and versions with a single click', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/easy-duplication.png' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Page Addon', 'download-monitor' ), esc_html__( 'Make use of a shortcode to turn a page into a fully featured download listing page.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/page-addon.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Downloading Page', 'download-monitor' ), esc_html__( 'Forces your downloads to be served from a separate page.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/downloading-page.png', true, '#7364ff' ); ?>
						<?php $welcome->display_extension( esc_html__( 'Enforce download limits', 'download-monitor' ), esc_html__( 'Create advanced access rules and IP restrictions to control who can access downloads, how many times can files be downloaded by each user or when do files expire.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/enforce-download-limits.png', true, '#7364ff' ); ?>
						<?php $welcome->layout_end(); ?>

							<div class="testimonials">
								<div class="block clear">
								<?php $welcome->display_heading( esc_html__( 'Happy users of Download Monitor', 'download-monitor' ) ); ?>
								
								<?php $welcome->display_testimonial( esc_html__( 'Do not spend any time considering other plugins that may offer the same bells and whistles. Not only is this full of fantastic functionality, the support behind the plugin is superior to anything you will get from any other developer.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/carlos-espinosa.jpeg', 'Carlos Espinosa' ); ?>
								<?php $welcome->display_testimonial( esc_html__( 'Download Monitor rocks! It lets me easily implement customized/themed lists of downloads and offers useful statistics and access logs for my downloads.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/Sebastian-Herrmann.jpeg', 'Sebastian Herrmann' ); ?>
								</div>
							</div><!-- testimonials -->

							<div class="button-wrap clear">
								<div class="left">
								<?php $welcome->display_button( esc_html__( 'Start Adding Downloads', 'download-monitor' ), esc_url( admin_url( 'edit.php?post_type=dlm_download' ) ), true, '#7364ff' ); ?>
								</div>
								<div class="right">
								<?php $welcome->display_button( esc_html__( 'Upgrade Now', 'download-monitor' ), 'https://www.download-monitor.com/pricing/?utm_source=welcome_banner&utm_medium=upgradenow&utm_campaign=welcome_banner', true, '#E76F51' ); ?>
								</div>
							</div>
						</div>
					</div><!-- hero -->
				</div><!-- container -->
			</div><!-- wpchill welcome -->
			<?php
	}

}