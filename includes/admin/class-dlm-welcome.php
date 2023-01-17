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
		add_filter( 'dlm_page_header', array( $this, 'welcome_header' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'welcome_scripts' ) );
		add_action( 'admin_footer', array( $this, 'welcome_style' ), 15 );
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
	 *  Register admin Wellcome style
	 *
	 * @since 4.7.72
	 */
	public function welcome_style() {
		if ( isset( $_GET['post_type'] ) && 'dlm_download' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			wp_register_style( 'dlm-welcome-style', plugins_url( '/assets/css/welcome.css', DLM_PLUGIN_FILE ), null, DLM_VERSION );
		}
	}

	/**
	 *  Display About page
	 *
	 * @since 4.5.9
	 */
	public function about_page() {

		// WPChill Welcome Class.
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

		if ( ! class_exists( 'WPChill_Welcome' ) ) {
			return;
		}

		$welcome = WPChill_Welcome::get_instance();

		wp_enqueue_style( 'dlm-welcome-style' );

		/** @var \DLM_Settings_Helper $settings */
		$settings = download_monitor()->service( 'settings' );
		/**
		 * Check if no access page is already set in settings
		 */
		$page_no_access = $settings->get_option( 'no_access_page' );
		/**
		 * Check if no access page is already set in settings
		 */
		$page_cart = $settings->get_option( 'page_cart' );
		/**
		 * Check if no access page is already set in settings
		 */
		$page_checkout = $settings->get_option( 'page_checkout' );

		?>
		<div id="wpchill-welcome">
			<div class="hero">
				<div class="block welcome-header">
					<?php $welcome->layout_start( 2, 'feature-list clear' ); ?>
					<div class="block-row">
						<div class="dlm-logo">
							<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA1IiBoZWlnaHQ9IjEwNSIgdmlld0JveD0iMCAwIDEwNSAxMDUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik01Mi41IDAuMDAwNTk5Njc0QzM4LjU3NTYgMC4wMDA1OTk2NzQgMjUuMjIxOSA1LjUzMjAzIDE1LjM3NzYgMTUuMzc4MUM1LjUzMTQ2IDI1LjIyMjkgMCAzOC41NzY2IDAgNTIuNTAwM0MwIDY2LjQyNCA1LjUzMTQ2IDc5Ljc3ODMgMTUuMzc3NiA4OS42MjI1QzI1LjIyMjUgOTkuNDY4NiAzOC41NzYyIDEwNSA1Mi41IDEwNUM2Ni40MjM4IDEwNSA3OS43NzgxIDk5LjQ2ODYgODkuNjIyNCA4OS42MjI1Qzk5LjQ2ODUgNzkuNzc3NyAxMDUgNjYuNDI0IDEwNSA1Mi41MDAzQzEwNSA0My4yODQ1IDEwMi41NzQgMzQuMjMwOCA5Ny45NjY0IDI2LjI1MDJDOTMuMzU4NyAxOC4yNjk1IDg2LjczMDQgMTEuNjQxNiA3OC43NDk3IDcuMDMzNTRDNzAuNzY5IDIuNDI1ODEgNjEuNzE1MiAwIDUyLjQ5OTQgMEw1Mi41IDAuMDAwNTk5Njc0Wk00MC40Nzc3IDM4LjI3MThMNDcuMjQ5OSA0NS4wOTY5VjI2LjI0OTZINTcuNzUwMVY0NS4wOTY5TDY0LjUyMjMgMzguMzI0Nkw3MS45MjUyIDQ1LjcyNzVMNTIuNSA2NS4xNTI2TDMzLjAyMiA0NS42NzQ3TDQwLjQ3NzcgMzguMjcxOFpNNzguNzQ5MSA3OC43NTExSDI2LjI0ODVWNjguMjUxSDc4Ljc0OTFWNzguNzUxMVoiIGZpbGw9InVybCgjcGFpbnQwX2xpbmVhcl8zN184NSkiLz4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0icGFpbnQwX2xpbmVhcl8zN184NSIgeDE9Ii0zNy41MjkzIiB5MT0iMS4wOTMzNGUtMDYiIHgyPSI5NS45NzY2IiB5Mj0iMTA3Ljg3MSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPgo8c3RvcCBvZmZzZXQ9IjAuMTEwMTEzIiBzdG9wLWNvbG9yPSIjNURERUZCIi8+CjxzdG9wIG9mZnNldD0iMC40NDM1NjgiIHN0b3AtY29sb3I9IiM0MTlCQ0EiLz4KPHN0b3Agb2Zmc2V0PSIwLjYzNjEyMiIgc3RvcC1jb2xvcj0iIzAwOENENSIvPgo8c3RvcCBvZmZzZXQ9IjAuODU1OTk3IiBzdG9wLWNvbG9yPSIjMDI1RUEwIi8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzAyNTM4RCIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo="
							     alt="<?php esc_attr_e( 'Download Monitor Logo', 'download-monitor' ); ?>">
						</div>
						<?php $welcome->display_heading( esc_html__( 'Welcome to Download Monitor', 'download-monitor' ),' left' ); ?>
						<?php $welcome->display_subheading( esc_html__( 'You\'re just a few steps away from adding, displaying and tracking your first download on your website with the easiest to use WordPress download plugin.', 'download-monitor' ), 'left' ); ?>
						<div class="block padding-horizontal-15">
							<?php $welcome->display_button( esc_html__( 'Create Your First Download', 'download-monitor' ), esc_url( admin_url( 'post-new.php?post_type=dlm_download' ) ), 'button-primary button-hero', false, '#2271b1' ); ?>&nbsp;&nbsp;&nbsp;&nbsp;
							<?php $welcome->display_button( esc_html__( 'Read our getting started guide', 'download-monitor' ), 'https://www.download-monitor.com/kb/add-your-first-download/', 'button-secondary button-hero', false, 'transparent', '#fff', '#fff' ); ?>
						</div>
					</div>
					<div class="block-row">
						<img src="<?php echo esc_url( DLM_URL ); ?>includes/submodules/banner/assets/img/welcome_header_laptop.png" alt="<?php esc_attr_e( 'Watch how to', 'download-monitor' ); ?>">
					</div>
					<?php $welcome->layout_end(); ?>
				</div>
			</div><!-- hero -->
			<div class="features">
				<div class="block">
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->display_heading( esc_html__( 'Features & Add-ons', 'download-monitor' ), 'left' ); ?>
					<?php $welcome->horizontal_delimiter(); ?>
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->layout_start( 3, 'feature-list clear' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Gated content', 'download-monitor' ), esc_html__( 'Use our Email Lock or Gravity/Ninja Forms extensions to lock downloads and gather leads. Alternatively, use Twitter Lock to require tweets in exchange for access to digital products.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/gated-content.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Enforce download limits', 'download-monitor' ), esc_html__( 'Create advanced access rules and IP restrictions to control who can access downloads, how many times can files be downloaded by each user or when do files expire.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/enforce-download-limits.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Host files externally', 'download-monitor' ), esc_html__( 'Easily link files from Amazon S3 and Google Drive to your website.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/link-downloads-from-cloud.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Track your content', 'download-monitor' ), esc_html__( 'Gain access to detailed reports to see how your downloads are behaving.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/track-your-content.png' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Content grouping', 'download-monitor' ), esc_html__( 'Easily assign categories, tags or other meta to your downloads.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/content-grouping.png' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Customisable endpoints', 'download-monitor' ), esc_html__( 'For showing appealing download links and engaging buttons.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/customisable-endpoints.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Spam protection', 'download-monitor' ), esc_html__( 'Our smart Captcha extension stops bots from finding, accessing and/or downloading your files without authorization', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/spam-protection.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Monetize your downloads', 'download-monitor' ), esc_html__( 'Ability to sell your downloads straight from your WordPress website.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/monetize-your-downloads.png' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Instant notifications', 'download-monitor' ), esc_html__( 'Receive instant email notifications whenever someone downloads your content.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/instant-notifications.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Page Addon', 'download-monitor' ), esc_html__( 'Make use of a shortcode to turn a page into a fully featured download listing page.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/page-addon.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Downloading Page', 'download-monitor' ), esc_html__( 'Forces your downloads to be served from a separate page.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/downloading-page.png', true, '#F08232' ); ?>
					<?php $welcome->display_extension( esc_html__( 'Easy data importing/exporting', 'download-monitor' ), esc_html__( ' Import/export all download data including categories, tags and all file versions to and from a CSV file.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/features/data-importing-exporting.png', true, '#F08232' ); ?>
					<?php $welcome->layout_end(); ?>
					<?php $welcome->display_empty_space(); ?>
					<div class="wpchill-text-center">
						<div class="button-wrap-single clear">
							<div >
								<?php $welcome->display_button( esc_html__( 'Upgrade Now', 'download-monitor' ), 'https://www.download-monitor.com/pricing/?utm_source=welcome_banner&utm_medium=upgradenow&utm_campaign=welcome_banner&utm_content=first_button', 'button-primary button-hero', false, '#E76F51' ); ?>
							</div>
						</div>
					</div>
				</div>
				<div class="block text-left pages-creation">
					<?php $welcome->display_heading( esc_html__( 'Getting started checklist', 'download-monitor' ), 'left' ); ?>
					<?php $welcome->horizontal_delimiter(); ?>
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->display_subheading( esc_html__( 'In order to function properly, Download Monitor needs to create some pages in your WordPress website.', 'download-monitor' ), 'left' ); ?>
					<?php $welcome->display_subheading( esc_html__( 'We can create these pages for you here. If you click the \'Create Page\' button we will create that page and add the required shortcode to it. We\'ll also make sure the newly created page is set in your settings page.', 'download-monitor' ), 'left' ); ?>
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->layout_start( '2', 'white-bg padding-vertical-15' ); ?>
					<div class="block text-left">
						<p style="margin:0 auto;margin-top:5px;"><strong>
								<span class="dashicons <?php echo 0 != $page_no_access ? ' dashicons-yes': ' dashicons-no'; ?>" style="<?php echo 0 != $page_no_access ? 'color:#23A870;opacity:1;': 'color:gray;opacity:0.5;'; ?>"></span><?php echo esc_html__( 'No Access', 'download-monitor' ); ?></strong></p>
						<p class="description"><?php echo esc_html__( 'The page your visitors see when they are not allowed to download a file.', 'download-monitor' ); ?></p>
					</div>
					<div class="block text-right padding-15">
						<?php

						if ( $page_no_access != 0 ) :
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-page-exists"><?php echo esc_html__( 'Page Created', 'download-monitor' ); ?></a>
						<?php
						else:
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-create-page"
							   data-page="no-access"><?php echo esc_html__( 'Create Page', 'download-monitor' ); ?></a>
						<?php
						endif;
						?>
					</div>
					<?php $welcome->layout_end(); ?>
					<?php $welcome->layout_start( '2', 'white-bg padding-vertical-15' ); ?>
					<div class="block text-left">
						<p style="margin:0 auto;margin-top:5px;"><strong><span class="dashicons  <?php echo 0 != $page_cart ? ' dashicons-yes': ' dashicons-no'; ?>" style="<?php echo 0 != $page_cart ? 'color:#23A870;opacity:1;': 'color:gray;opacity:0.5;'; ?>"></span><?php echo esc_html__( 'Cart', 'download-monitor' ); ?></strong></p>
						<p class="description"><?php echo esc_html__( 'Your shop cart page if you decide to sell downloads.', 'download-monitor' ); ?></p>
					</div>
					<div class="block text-right padding-15">
						<?php
						if ( $page_cart != 0 ) :
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-page-exists"><?php echo esc_html__( 'Page Created', 'download-monitor' ); ?></a>
						<?php
						else:
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-create-page"
							   data-page="cart"><?php echo esc_html__( 'Create Page', 'download-monitor' ); ?></a>
						<?php
						endif;
						?>
					</div>
					<?php $welcome->layout_end(); ?>
					<?php $welcome->layout_start( '2', 'white-bg padding-vertical-15' ); ?>
					<div class="block text-left">
						<p style="margin:0 auto;margin-top:5px;"><strong><span class="dashicons  <?php echo 0 != $page_checkout ? ' dashicons-yes': ' dashicons-no'; ?>" style="<?php echo 0 != $page_checkout ? 'color:#23A870;opacity:1;': 'color:gray;opacity:0.5;'; ?>"></span><?php echo esc_html__( 'Checkout', 'download-monitor' ); ?></strong></p>
						<p class="description"><?php echo esc_html__( 'Your shop checkout page if you decide to sell downloads.', 'download-monitor' ); ?></p>
					</div>
					<div class="block text-right padding-15">
						<?php

						if ( 0 !== $page_checkout ) :
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-page-exists"><?php echo esc_html__( 'Page Created', 'download-monitor' ); ?></a>
						<?php
						else:
							?>
							<a href="javascript:;"
							   class="button button-primary button-hero dlm-create-page"
							   data-page="checkout"><?php echo esc_html__( 'Create Page', 'download-monitor' ); ?></a>
						<?php
						endif;
						?>
					</div>
					<?php $welcome->layout_end(); ?>
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->layout_start( '4', 'pages-creation__buttons' ); ?>
					<div class="block"></div>
					<div class="block text-right">
						<?php $welcome->display_button( esc_html__( 'Create Your First Download', 'download-monitor' ), esc_url( admin_url( 'post-new.php?post_type=dlm_download' ) ), 'button-primary', false, '#2271b1' ); ?>
					</div>
					<div class="block guide-button">
						<?php $welcome->display_button( esc_html__( 'Read our getting started guide', 'download-monitor' ), 'https://www.download-monitor.com/kb/add-your-first-download/', 'button-secondary', false, 'transparent', '#2271b1', '#2271b1' ); ?>
					</div>
					<div class="block"></div>
					<?php $welcome->layout_end(); ?>
				</div>
				<?php $welcome->display_empty_space(); ?>
				<?php $welcome->display_empty_space(); ?>
				<div class="block">
					<?php $welcome->display_heading( esc_html__( 'Happy users of Download Monitor', 'download-monitor' ), 'left' ); ?>
					<?php $welcome->horizontal_delimiter(); ?>
					<div class="testimonials">
						<div class="block-row">
							<?php $welcome->display_testimonial( esc_html__( 'Do not spend any time considering other plugins that may offer the same bells and whistles. Not only is this full of fantastic functionality, the support behind the plugin is superior to anything you will get from any other developer.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/carlos-espinosa.jpeg', 'Carlos Espinosa' ); ?>
							<?php $welcome->display_testimonial( esc_html__( 'Download Monitor rocks! It lets me easily implement customized/themed lists of downloads and offers useful statistics and access logs for my downloads.', 'download-monitor' ), esc_url( DLM_URL ) . 'assets/images/Sebastian-Herrmann.jpeg', 'Sebastian Herrmann' ); ?>
						</div>
					</div><!-- testimonials -->
					<?php $welcome->display_empty_space(); ?>
					<?php $welcome->display_empty_space(); ?>
					<div class="pages-creation">
						<?php $welcome->layout_start( '4', 'pages-creation__buttons' ); ?>
						<div class="block"></div>
						<div class="block text-right">
							<?php $welcome->display_button( esc_html__( 'Create Your First Download', 'download-monitor' ), esc_url( admin_url( 'edit.php?post_type=dlm_download' ) ), 'button-primary', false, '#2271b1' ); ?>
						</div>
						<div class="block">
							<?php $welcome->display_button( esc_html__( 'Upgrade Now', 'download-monitor' ), 'https://www.download-monitor.com/pricing/?utm_source=welcome_banner&utm_medium=upgradenow&utm_campaign=welcome_banner&utm_content=second_button', 'button-secondary', false, '#E76F51' ); ?>
						</div>
						<div class="block"></div>
						<?php $welcome->layout_end(); ?>
					</div>
				</div>
			</div><!-- features -->
		</div><!-- wpchill welcome -->
		<?php
	}

	/**
	 * Don't display the header on the Welcome banner
	 *
	 * @param bool $return Return value.
	 *
	 * @return bool|false
	 * @since 4.7.4
	 */
	public function welcome_header( $return ) {

		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();

			if ( 'dlm_download' === $current_screen->post_type && 'dlm_download_page_download-monitor-about-page' === $current_screen->base ) {
				$return = false;
			}
		}

		return $return;
	}

	/**
	 * Enqueue welcome page scripts
	 *
	 * @return void
	 * @since 4.7.4
	 */
	public function welcome_scripts() {
		if ( function_exists( 'get_current_screen' ) ) {

			$current_screen = get_current_screen();
			if ( 'dlm_download' === $current_screen->post_type && 'dlm_download_page_download-monitor-about-page' === $current_screen->base ) {
				wp_enqueue_script(
					'dlm_onboarding',
					plugins_url( '/assets/js/onboarding' . ( ( ! SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', download_monitor()->get_plugin_file() ),
					array( 'jquery' ),
					DLM_VERSION
				);

				wp_localize_script( 'dlm_onboarding', 'dlm_onboarding', array(
					'ajax_url_create_page' => \DLM_Ajax_Manager::get_ajax_url( 'create_page' ),
					'lbl_creating'         => __( 'Creating', 'download-monitor' ) . '...',
					'lbl_created'          => __( 'Page Created', 'download-monitor' ),
					'lbl_create_page'      => __( 'Create Page', 'download-monitor' ),
				) );
			}
		}
	}

}
