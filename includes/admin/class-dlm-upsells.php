<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
use WPChill\DownloadMonitor\Util;

/**
 * Class DLM_Upsells
 *
 * @since 4.4.5
 */
class DLM_Upsells {

	/**
	 * Holds the class object.
	 *
	 * @since 4.4.5
	 *
	 * @var object
	 */
	public static $instance;

	public $extensions = array();

	private $upsell_tabs = array();

	private $offer = array();

	/**
	 * Holds the active license status.
	 *
	 * @since 4.9.4
	 *
	 * @var bool
	 */
	private $active_license = false;

	/**
	 * DLM_Upsells constructor.
	 *
	 * @since 4.4.5
	 */
	public function __construct() {

		if ( $this->check_license_validity() ) {
			return;
		}
		// Add modal upsells through sub menu. Place here to run everywhere, not just on DLM pages.
		add_action( 'admin_menu', array( $this, 'add_upsell_modals' ), 13 );

		// Add Lite VS Pro page
		add_filter( 'dlm_admin_menu_links', array( $this, 'add_lite_vs_pro_page' ), 120 );
		add_action( 'admin_print_footer_scripts', array( $this, 'inline_script_for_redirection' ) );

		if ( ! DLM_Admin_Helper::is_dlm_admin_page() ) {
			return;
		}

		add_action( 'init', array( $this, 'upsells_init' ) );

		// Upgrade to PRO plugin action link
		add_filter( 'plugin_action_links_' . DLM_FILE, array( $this, 'filter_action_links' ), 60 );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Upsells object.
	 *
	 * @since 4.4.5
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Upsells ) ) {
			self::$instance = new DLM_Upsells();
		}

		return self::$instance;
	}

	public function upsells_init(){
		$this->set_offer();

		$this->set_hooks();

		$this->set_tabs();

		$this->set_upsell_actions();
	}

	private function set_offer() {
		$month       = date( 'm' );
		$this->offer = array(
			'class'  => '',
			'column' => '',
			'label'  => __( 'Get Premium', 'download-monitor' ),
		);
		// if ( 11 == $month ) {
		// 	$this->offer = array(
		// 		'class'       => 'wpchill-bf-upsell',
		// 		'column'      => 'bf-upsell-columns',
		// 		'label'       => __( '40% OFF for Black Friday', 'download-monitor' ),
		// 		'description' => '40% OFF on new purchases, early renewals or upgrades.',
		// 	);
		// }
		// if ( 12 == $month ) {
		// 	$this->offer = array(
		// 		'class'  => 'wpchill-xmas-upsell',
		// 		'column' => 'xmas-upsell-columns',
		// 		'label'  => __( '25% OFF for Christmas', 'download-monitor' ),
		// 	);
		// }
	}

	/**
	 * Set our hooks
	 *
	 * @since 4.4.5
	 */
	public function set_hooks() {

		add_action( 'dlm_tab_upsell_content_general', array( $this, 'general_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_access', array( $this, 'access_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_logging', array( $this, 'logging_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_email_notification', array( $this, 'emails_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_pages', array( $this, 'pages_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_license', array( $this, 'license_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_misc', array( $this, 'misc_tab_upsell' ), 15 );

		add_action( 'dlm_tab_upsell_content_endpoints', array( $this, 'endpoint_tab_upsell' ), 15 );

		add_filter( 'dlm_download_metaboxes', array( $this, 'add_meta_boxes' ), 30 );

		add_filter( 'dlm_settings', array( $this, 'pro_tab_upsells' ), 99, 1 );

		add_action( 'dlm_insights_header', array( $this, 'export_insights_header_upsell' ) );

		add_action( 'dlm_reports_general_info', array( $this, 'insights_upsell' ), 99, 2 );

		add_action( 'dlm_reports_user_reports', array( $this, 'insights_upsell' ), 99, 2 );

		add_action( 'dlm_insights_header', array( $this, 'insights_datepicker_upsell' ) );

		add_action( 'dlm_tab_upsell_content_pages', array( $this, 'pages_tab_upsell' ), 15 );
	}


	/**
	 * Generate the all-purpose upsell box
	 *
	 * @param        $title
	 * @param        $description
	 * @param        $tab
	 * @param        $extension
	 * @param null   $utm_source
	 * @param array  $features
	 * @param string $utm_source
	 * @param string $icon
	 *
	 * @return string
	 *
	 * @since 4.4.5
	 */
	public function generate_upsell_box( $title, $description, $tab, $extension, $features = array(), $utm_source = null, $icon = false ) {

		echo '<div class="wpchill-upsell ' . esc_attr( $this->offer['class'] ) . '">';
		if ( $icon ) {
			echo '<img src="' . esc_url( DLM_URL . 'assets/images/upsells/' . $icon ) . '">';
		}

		if ( ! empty( $title ) ) {
			echo '<h2>' . esc_html( $title ) . '</h2>';
		}

		if ( ! empty( $features ) ) {
			echo '<ul class="wpchill-upsell-features">';

			foreach ( $features as $feature ) {
				echo '<li>';
				if ( isset( $feature['tooltip'] ) && '' != $feature['tooltip'] ) {
					echo '<div class="wpchill-tooltip"><span>[?]</span>';
					echo '<div class="wpchill-tooltip-content">' . esc_html( $feature['tooltip'] ) . '</div>';
					echo '</div>';
					echo '<p>' . esc_html( $feature['feature'] ) . '</p>';
				} else {
					echo esc_html( $feature['feature'] );
				}

				echo '</li>';
			}
			echo '</ul>';
		}
		if ( ! empty( $description ) ) {
			echo '<p class="wpchill-upsell-description">' . esc_html( $description ) . '</p>';
		}

		echo '<a target="_blank" href="https://www.download-monitor.com/pricing/?utm_source=' . ( ! empty( $extension ) ? esc_html( $extension ) . '_metabox' : '' ) . '&utm_medium=lite-vs-pro&utm_campaign=' . ( ! empty( $extension ) ? esc_html( str_replace( ' ', '_', $extension ) ) : '' ) . '"><div class="dlm-available-with-pro"><span class="dashicons dashicons-lock"></span><span>' . esc_html__( 'AVAILABLE WITH PREMIUM', 'download-monitor' ) . '</span></div></a>';
		echo '<div class="wpchill-upsell-buttons-wrap">';
		echo '<a target="_blank" href="https://download-monitor.com/free-vs-pro/?utm_source=dlm-lite&utm_medium=link&utm_campaign=upsell&utm_term=lite-vs-pro" class="button">' . esc_html__( 'Free vs Premium', 'download-monitor' ) . '</a> ';
		echo '<a target="_blank" href="https://www.download-monitor.com/pricing/?utm_source=' . ( ! empty( $extension ) ? esc_html( $extension ) . '_metabox' : '' ) . '&utm_medium=lite-vs-pro&utm_campaign=' . ( ! empty( $extension ) ? esc_html( str_replace( ' ', '_', $extension ) ) : '' ) . '" class="button-primary button">' . esc_html( $this->offer['label'] ) . '</a>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Add upsell metaboxes
	 *
	 * @since 4.4.5
	 */
	public function add_meta_boxes( $meta_boxes ) {

		if ( ! $this->check_extension( 'dlm-downloading-page' ) ) {
			$meta_boxes[] = array(
				'id'       => 'dlm-download-page-upsell',
				'title'    => esc_html__( 'Downloading page', 'download-monitor' ),
				'callback' => array( $this, 'output_download_page_upsell' ),
				'screen'   => 'dlm_download',
				'context'  => 'side',
				'priority' => 30,
			);
		}

		if ( ! $this->check_extension( 'dlm-buttons' ) ) {
			$meta_boxes[] = array(
				'id'       => 'dlm-buttons-upsell',
				'title'    => esc_html__( 'Buttons', 'download-monitor' ),
				'callback' => array( $this, 'output_buttons_upsell' ),
				'screen'   => 'dlm_download',
				'context'  => 'side',
				'priority' => 40,
			);
		}

		if ( ! $this->check_extension( 'dlm-amazons-s3' ) || ! $this->check_extension( 'dlm-google-drive' ) ) {
			$meta_boxes[] = array(
				'id'       => 'dlm-external-hosting',
				'title'    => esc_html__( 'External Hosting', 'download-monitor' ),
				'callback' => array( $this, 'output_external_hosting_upsell' ),
				'screen'   => 'dlm_download',
				'context'  => 'normal',
				'priority' => 10,
			);
		}

		return $meta_boxes;
	}

	/**
	 * Set the existing extensions
	 *
	 * @since 4.4.5
	 */
	private function set_extensions() {

		$dlm_Extensions = DLM_Admin_Extensions::get_instance();

		$extensions = $dlm_Extensions->get_extensions();

		foreach ( $extensions as $extension ) {
			$this->extensions[] = $extension->product_id;
		}
	}

	/**
	 * Get existing extensions
	 *
	 * @since 4.9.9
	 */
	private function get_extensions() {
		if ( empty( $this->extensions ) ) {
			$this->set_extensions();
		}

		return $this->extensions;
	}

	/**
	 * Check if extension exists
	 *
	 * @param $extension
	 *
	 * @return bool
	 *
	 * @since 4.4.5
	 */
	public function check_extension( $extension ) {
		$extensions = $this->get_extensions();
		if ( empty( $extensions ) || ! in_array( $extension, $extensions ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Set DLM's upsell tabs
	 *
	 * @since 4.4.5
	 */
	public function set_tabs() {
		// Define our upsell tabs
		// First is the tab and then are the sections
		$this->upsell_tabs = apply_filters(
			'dlm_upsell_tabs',
			array(
				'lead_generation'  => array(
					'title'    => esc_html__( 'Content Locking', 'download-monitor' ),
					'upsell'   => true,
					'sections' => array(
						'ninja_forms'   => array(
							'title'    => __( 'Ninja Forms', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'gravity_forms' => array(
							'title'    => __( 'Gravity Forms', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
							'upsell'   => true,
							'badge'    => true,
						),
						'email_lock'    => array(
							'title'    => __( 'Email lock', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'cf7_lock'      => array(
							'title'    => __( 'Contact Form 7', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'wpforms_lock'  => array(
							'title'    => __( 'WP Forms', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
					),
				),
				'external_hosting' => array(
					'title'    => esc_html__( 'External hosting', 'download-monitor' ),
					'sections' => array(
						'amazon_s3'    => array(
							'title'    => __( 'Amazon S3', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'google_drive' => array(
							'title'    => __( 'Google Drive', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
					),
				),
				'advanced'         => array(
					'title'    => esc_html__( 'Advanced', 'download-monitor' ),
					'sections' => array(
						'page_addon'       => array(
							'title'    => __( 'Page Addon', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'downloading_page' => array(
							'title'    => __( 'Downloading Page', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
						'captcha'          => array(
							'title'    => __( 'Captcha', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
					),
				),
				'integration'      => array(
					'title'    => esc_html__( 'Integration', 'download-monitor' ),
					'sections' => array(
						'captcha' => array(
							'title'    => __( 'Captcha', 'download-monitor' ),
							'sections' => array(),
							// Need to put sections here for backwards compatibility
						),
					),
				),
			)
		);
	}

	/**
	 * Add PRO Tabs upsells
	 *
	 * @param $settings
	 *
	 * @return mixed
	 *
	 * @since 4.4.5
	 */
	public function pro_tab_upsells( $settings ) {

		foreach ( $this->upsell_tabs as $key => $tab ) {
			if ( ! isset( $settings[ $key ] ) ) {
				if ( ! isset( $settings[ $key ]['title'] ) ) {
					$settings[ $key ]['title'] = $tab['title'];
				}

				foreach ( $tab['sections'] as $section_key => $section ) {
					if ( ! isset( $settings[ $key ]['sections'][ $section_key ] ) ) {
						$settings[ $key ]['sections'][ $section_key ]           = $section;
						$settings[ $key ]['sections'][ $section_key ]['upsell'] = true;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Add Upsell tabs content
	 *
	 * @since 4.4.5
	 */
	public function set_upsell_actions() {

		foreach ( $this->upsell_tabs as $key => $tab ) {
			if ( method_exists( 'DLM_Upsells', 'upsell_tab_content_' . $key ) ) {
				add_action( 'dlm_tab_upsell_content_' . $key, array( $this, 'upsell_tab_content_' . $key ), 30, 1 );
			}

			foreach ( $tab['sections'] as $sub_key => $section ) {
				if ( method_exists( 'DLM_Upsells', 'upsell_tab_section_content_' . $sub_key ) ) {
					add_action( 'dlm_tab_upsell_section_content_' . $sub_key, array( $this, 'upsell_tab_section_content_' . $sub_key ), 30, 1 );
				}
			}
		}
	}

	/**
	 * Settings General tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function general_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-email-notification' ) ) {
			$this->generate_upsell_box(
				__( 'Email notifications', 'download-monitor' ),
				__( 'Create an email alert to be notified each time one of your files has been downloaded.', 'download-monitor' ),
				'general',
				'email-notification',
				false,
				false,
				'email_notification.png'
			);
		}
	}

	/**
	 * Settings Access tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function access_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-advanced-access-manager' ) ) {
			$this->generate_upsell_box(
				__( 'Advanced access manager', 'download-monitor' ),
				__( 'Limit access to your downloads by setting advanced access rules and restrictions with this extension.', 'download-monitor' ),
				'access',
				'advanced-access-manager'
			);
		}

		if ( ! $this->check_extension( 'dlm-email-lock' ) ) {
			$this->generate_upsell_box(
				__( 'Email Lock', 'download-monitor' ),
				__( 'Require your users’ email addresses to send newsletters and create a list of your customers.', 'download-monitor' ),
				'access',
				'email-lock'
			);
		}

		if ( ! $this->check_extension( 'dlm-gravity-forms' ) ) {
			$this->generate_upsell_box(
				__( 'Gravity Forms Lock', 'download-monitor' ),
				__( 'Ask users to fill in a form created on Gravity Forms before they start downloading your files.', 'download-monitor' ),
				'access',
				'gravity-forms'
			);
		}

		if ( ! $this->check_extension( 'dlm-ninja-forms' ) ) {
			$this->generate_upsell_box(
				__( 'Ninja Forms Lock', 'download-monitor' ),
				__( 'Use the Ninja Forms - content locking extension to add forms easily to your download files.', 'download-monitor' ),
				'access',
				'ninja-forms'
			);
		}

		if ( ! $this->check_extension( 'dlm-mailchimp-lock' ) ) {
			$this->generate_upsell_box(
				__( 'Mailchimp extension', 'download-monitor' ),
				__( 'Create a MailChimp list and ask users to subscribe to it before accessing a downloadable file.', 'download-monitor' ),
				'access',
				'mailchimp-lock'
			);
		}

		if ( ! $this->check_extension( 'dlm-cf7-lock' ) ) {
			$this->generate_upsell_box(
				__( 'Contact Form 7', 'download-monitor' ),
				__( 'Require your users’ email addresses to send newsletters and create a list of your customers.', 'download-monitor' ),
				'access',
				'cf7-lock'
			);
		}

		if ( ! $this->check_extension( 'dlm-wpforms-lock' ) ) {
			$this->generate_upsell_box(
				__( 'WP Forms', 'download-monitor' ),
				__( 'Require your users’ email addresses to send newsletters and create a list of your customers.', 'download-monitor' ),
				'access',
				'wpforms-lock'
			);
		}
	}

	/**
	 * Settings Logging tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function logging_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-captcha' ) ) {
			$this->generate_upsell_box(
				__( 'Captcha', 'download-monitor' ),
				__( 'Stop bots from spamming your downloads and ask users to complete Google reCAPTCHA.', 'download-monitor' ),
				'logging',
				'captcha'
			);
		}
	}

	/**
	 * Settings Emails tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function emails_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-email-notification' ) ) {
			$this->generate_upsell_box(
				__( 'Email notifications', 'download-monitor' ),
				__( 'The Email Notification extension for Download Monitor sends you an email whenever one of your files is downloaded', 'download-monitor' ),
				'email_notifications',
				'email-notifications'
			);
		}
	}

	/**
	 * Settings Logging tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function pages_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-page-addon' ) ) {
			$this->generate_upsell_box(
				__( 'Page Addon', 'download-monitor' ),
				__( 'List all downloads, categories, tags, and showcase info pages of each resource with a self-contained [download_page] shortcode!', 'download-monitor' ),
				'pages',
				'page-addon'
			);
		}
	}

	/**
	 * Settings Misc tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function output_buttons_upsell() {

		if ( ! $this->check_extension( 'dlm-buttons' ) ) {
			$this->generate_upsell_box(
				__( 'Buttons', 'download-monitor' ),
				__( 'The Buttons extension allows you to customize your download buttons as you please in order to improve the user experience. Create stunning buttons without needing any coding skills!', 'download-monitor' ),
				'cpt',
				'buttons'
			);
		}
	}

	/**
	 * Settings Misc tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function endpoint_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-csv-impoter' ) ) {
			$this->generate_upsell_box(
				__( 'Importer', 'download-monitor' ),
				__( 'Easily import your downloads, including their categories, tags, and files.', 'download-monitor' ),
				'endpoint',
				'csv-impoter'
			);
		}

		if ( ! $this->check_extension( 'dlm-csv-exporter' ) ) {
			$this->generate_upsell_box(
				__( 'Exporter', 'download-monitor' ),
				__( 'With a single click, you can quickly export your downloads and their tags, categories, and file versions to a CSV file.', 'download-monitor' ),
				'endpoint',
				'csv-exporter'
			);
		}
	}

	/**
	 * Output the DLM Downloading Page extension upsell
	 *
	 * @since 4.4.5
	 */
	public function output_download_page_upsell() {

		if ( ! $this->check_extension( 'dlm-downloading-page' ) ) {
			$this->generate_upsell_box(
				'',
				__( 'Customize the downloading page by adding banners, ads, and anything you like.', 'download-monitor' ),
				'downloading_page',
				'downloading-page'
			);
		}
	}

	/**
	 * Upsell for Gravity Forms sub-tab
	 *
	 * @since 4.5.3
	 */
	public function upsell_tab_section_content_gravity_forms() {

		if ( ! $this->check_extension( 'dlm-gravity-forms' ) ) {
			$this->generate_upsell_box(
				__( 'Gravity Forms Lock', 'download-monitor' ),
				__( 'The Gravity Forms - content locking extension for Download Monitor allows you to require users to fill out a Gravity Forms form before they gain access to a download.', 'download-monitor' ),
				'gravity_forms',
				'gravity-forms'
			);
		}
	}

	/**
	 * Upsell for Ninja Forms sub-tab
	 *
	 * @since 4.5.3
	 */
	public function upsell_tab_section_content_ninja_forms() {

		if ( ! $this->check_extension( 'dlm-ninja-forms' ) ) {
			$this->generate_upsell_box(
				__( 'Ninja Forms Lock', 'download-monitor' ),
				__( 'The Ninja Forms - content locking extension for Download Monitor allows you to require users to fill in a Ninja Forms form before they gain access to a download.', 'download-monitor' ),
				'ninja_forms',
				'ninja-forms'
			);
		}
	}

	/**
	 * Upsell for Email Lock sub-tab
	 *
	 * @since 4.5.3
	 */
	public function upsell_tab_section_content_email_lock() {

		if ( ! $this->check_extension( 'dlm-email-lock' ) ) {
			$this->generate_upsell_box(
				__( 'Email Lock', 'download-monitor' ),
				__( 'The Email Lock extension for Download Monitor allows you to require users to fill in their email address before they gain access to a download.', 'download-monitor' ),
				'email_lock',
				'email-lock'
			);
		}
	}

	/**
	 * Upsell for Amazon S3 setting sub-tab
	 *
	 * @since 4.5.3
	 */
	public function upsell_tab_section_content_amazon_s3() {

		if ( ! $this->check_extension( 'dlm-amazon-s3' ) ) {
			$this->generate_upsell_box(
				__( 'Amazon S3', 'download-monitor' ),
				__( 'Link to files hosted on Amazon s3 so that you can serve secure, expiring download links.', 'download-monitor' ),
				'amazon_s3',
				'amazon-s3'
			);
		}
	}

	/**
	 * Upsell for Google Drive setting sub-tab
	 *
	 * @since 4.5.3
	 */
	public function upsell_tab_section_content_google_drive() {

		if ( ! $this->check_extension( 'dlm-google-drive' ) ) {
			$this->generate_upsell_box(
				__( 'Google Drive', 'download-monitor' ),
				__( 'With this extension, you can integrate your files from Google Drive into Download Monitor.', 'download-monitor' ),
				'google_drive',
				'google-drive'
			);
		}
	}


	/**
	 * Upsell for Page Addon setting tab
	 *
	 * @since 4.4.5
	 */
	public function upsell_tab_content_advanced() {
		if ( ! $this->check_extension( 'dlm-page-addon' ) ) {
			$this->generate_upsell_box(
				__( 'Page addon extension', 'download-monitor' ),
				__( 'Add a self contained [download_page] shortcode to your site to list downloads, categories, tags, and show info pages about each of your resources.', 'download-monitor' ),
				'page_addon',
				'page-addon'
			);
		}

		if ( ! $this->check_extension( 'dlm-downloading-page' ) ) {
			$this->generate_upsell_box(
				__( 'Downloading page extension', 'download-monitor' ),
				__( 'The Downloading Page extension for Download Monitor forces your downloads to be served from a separate page.', 'download-monitor' ),
				'downloading_page',
				'downloading-page'
			);
		}
	}

	/**
	 * Upsell for Captcha setting tab
	 *
	 * @since 4.4.5
	 */
	public function upsell_tab_content_captcha() {

		if ( ! $this->check_extension( 'dlm=captcha' ) ) {
			$this->generate_upsell_box(
				__( 'Captcha extension', 'download-monitor' ),
				__( 'The Captcha extension for Download Monitor allows you to require users to complete a Google reCAPTCHA before they gain access to a download.', 'download-monitor' ),
				'captcha',
				'captcha'
			);
		}
	}

	/**
	 * Output the Downloadable Files locations in the Downloadable files metabox
	 *
	 * @param $download
	 *
	 * @since 4.4.5
	 */
	public function output_external_hosting_upsell() {
		echo '<div class="upsells-columns ' . esc_attr( $this->offer['column'] ) . '">';

		if ( ! $this->check_extension( 'dlm-amazon-s3' ) ) {
			echo '<div class="upsells-column"><span class="dashicons dashicons-amazon"></span>';
			echo '<h3>' . esc_html__( 'Amazon S3', 'download-monitor' ) . '</h3>';
			$this->generate_upsell_box(
				'',
				__( 'Use Amazon S3 links for Download Monitor files to run secure, expiring download links.', 'download-monitor' ),
				'amazon_s3',
				'amazon-s3'
			);
			echo '</div>';
		}

		if ( ! $this->check_extension( 'dlm-google-drive' ) ) {
			echo '<div class="upsells-column"><span class="dashicons dashicons-google"></span>';
			echo '<h3>' . esc_html__( 'Google Drive', 'download-monitor' ) . '</h3>';
			$this->generate_upsell_box(
				'',
				__( 'With this extension, you can integrate your files from Google Drive into Download Monitor.', 'download-monitor' ),
				'google_drive',
				'google-drive'
			);
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Upsell for Integration tab
	 *
	 * @since 4.4.5
	 */
	public function upsell_tab_content_integration() {

		if ( ! $this->check_extension( 'dlm-captcha' ) ) {
			$this->generate_upsell_box(
				__( 'Captcha', 'download-monitor' ),
				__( 'Stop bots from spamming your downloads and ask users to complete Google reCAPTCHA.', 'download-monitor' ),
				'logging',
				'captcha'
			);
		}
	}

	/**
	 * Add lite vs pro page in menu
	 *
	 * @param [type] $links
	 *
	 * @return void
	 */
	public function add_lite_vs_pro_page( $links ) {

		// Settings page.
		$links[] = array(
			'page_title' => __( 'LITE vs Premium', 'download-monitor' ),
			'menu_title' => __( 'LITE vs Premium', 'download-monitor' ),
			'capability' => 'manage_options',
			'menu_slug'  => '#dlm-lite-vs-pro',
			'function'   => array( $this, 'lits_vs_pro_page' ),
			'priority'   => 160,
		);

		return $links;
	}

	/**
	 * The LITE vs PRO page
	 *
	 * @return void
	 */
	public function lits_vs_pro_page() {
		return;
	}

	/**
	 * Add the Upgrade to PRO plugin action link
	 *
	 * @param array $links Plugin action links.
	 *
	 * @return array
	 *
	 * @since 4.5.7
	 */
	public function filter_action_links( $links ) {

		$dlm_extensions       = DLM_Admin_Extensions::get_instance();
		$extensions           = $dlm_extensions->get_available_extensions();
		$licensed_extensions  = $dlm_extensions->get_licensed_extensions();
		$installed_extensions = $dlm_extensions->get_installed_extensions();

		if ( 0 < count( $extensions ) ) {
			if ( 0 !== count( $licensed_extensions ) && 0 < count( $installed_extensions ) ) { // If there are any licensed extensions ( active ) we show the Upgrade button, not the upgrade to PRO button.
				$upgrade = array( '<a target="_blank" style="color: orange;font-weight: bold;" href="https://www.download-monitor.com/pricing/?utm_source=download-monitor&utm_medium=plugins-page&utm_campaign=upsell">' . esc_html__( 'Upgrade!', 'download-monitor' ) . '</a>' );
			} else { // Show the upgrade to PRO button if no extensions are licensed.
				$upgrade = array( '<a target="_blank" style="color: orange;font-weight: bold;" href="https://www.download-monitor.com/pricing/?utm_source=download-monitor&utm_medium=plugins-page&utm_campaign=upsell">' . esc_html__( 'Upgrade to Premium!', 'download-monitor' ) . '</a>' );
			}

			return array_merge( $upgrade, $links );
		}

		return $links;
	}

	/**
	 * Export upsell
	 *
	 * @return void
	 * @since 4.8.6
	 */
	public function export_insights_header_upsell() {
		if ( $this->check_extension( 'dlm-csv-exporter' ) ) {
			return;
		}

		$export_upsell_url = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-csv-exporter';
		?>
		<div class="dlm-csv-export-wrapper">
			<div class="dlm-reports-header-export-button">
				<button class="button button-primary"
						disabled="disabled"><?php echo esc_html__( 'Export', 'download-monitor' ); ?> <a
						href="<?php echo esc_url( $export_upsell_url ); ?>"
						target="_blank"
						class="dlm-upsell-badge">PAID</a></button>
			</div>
			<div class="dlm-csv-export-wrapper__export_settings">
				<div id="dlm-export-settings-upsell" class="button button-secondary" disabled="disabled"><span
						class="dashicons dashicons-admin-generic"></span></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Reports upsells
	 *
	 * @param $tab
	 * @param $key
	 *
	 * @return void
	 * @since 4.8.6
	 */
	public function insights_upsell( $tab, $key ) {

		if ( $this->check_extension( 'dlm-enhanced-metrics' ) ) {
			return;
		}

		$list = array();
		if ( 'general_info' == $key ) {
			$list = array(
				array(
					'tooltip' => '',
					'feature' => __( 'Compare dates and view chart to see how you’ve done', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show number of completed downloads per download', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show number of redirected downloads per download', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show number of failed downloads per download', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show % of downloads from the total downloads number', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show number of completed downloads by logged in users', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show number of completed downloads by logged out users', 'download-monitor' ),
				),
			);
		} elseif ( 'user_reports' == $key ) {
			$list = array(
				array(
					'tooltip' => '',
					'feature' => __( 'See active users and their download information', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show the location from where in the site the user downloaded', 'download-monitor' ),
				),
				array(
					'tooltip' => '',
					'feature' => __( 'Show the download\'s category', 'download-monitor' ),
				),
			);
		}

		echo '<div class="wpchill-upsells-wrapper">';

		$this->generate_upsell_box(
			__( 'Enhanced Metrics', 'download-monitor' ),
			'',
			'enhanced-metrics',
			'enhanced-metrics',
			$list
		);

		echo '</div>';
	}

	/**
	 * Add the datepicker comparer
	 *
	 * @return void
	 * @since 4.8.6
	 */
	public function insights_datepicker_upsell() {

		if ( $this->check_extension( 'dlm-enhanced-metrics' ) ) {
			return;
		}

		$to_date = new DateTime( current_time( 'mysql' ) );
		$to_date->setTime( 0, 0, 0 );
		$to   = $to_date->format( 'Y-m-d' );
		$from = $to_date->modify( '-1 month' )->format( 'Y-m-d' );

		$end   = new DateTime( $to );
		$start = new DateTime( $from );

		$enhanced_m_upsell_url = 'https://www.download-monitor.com/pricing/?utm_source=reports_page&utm_medium=lite-vs-pro&utm_campaign=dlm-enhanced_metrics';
		?>
		<div class="dlm-reports-header-date-selector disabled">
			<label><?php echo esc_html__( 'Select date to compare', 'download-monitor' ); ?></label>
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span
				class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' - ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
			<span class="dlm-arrow"></span>
			<a href="<?php echo esc_url( $enhanced_m_upsell_url ); ?>" target="_blank" class="dlm-upsell-badge">PAID</a>
		</div>
		<?php
	}

	/**
	 * Check the license validity
	 *
	 * @return bool
	 * @since 4.9.4
	 */
	private function check_license_validity() {
		// Return if we're doing ajax
		if ( wp_doing_ajax() ) {
			return true;
		}

		$return = false;
		// First let's check the master license
		$master_license = get_option( 'dlm_master_license', false );
		if ( ! empty( $master_license ) ) {
			$data = json_decode( $master_license, true );
			// If the license is active, we return true
			if ( ! empty( $data ) && 'active' === $data['status'] ) {
				$return = true;
			}
		}
		// Let's check the extensions licenses
		// Retrieve all the extensions
		if ( class_exists( 'Util\ExtensionLoader' ) ) {
			require_once dirname( DLM_PLUGIN_FILE ) . 'src/Util/ExtensionLoader.php';
		}
		$loader   = new Util\ExtensionLoader();
		$response = $loader->fetch();
		// If we have an error, we return false
		if ( is_array( $response ) && isset( $response['success'] ) && ! $response['success'] ) {
			// Remove other upsells also by returning true
			if ( $return ) {
				add_filter( 'dlm_remove_upsells', '__return_true' );
				// If the master license is active, and we have an error, we return true
				return true;
			}

			return false;
		}
		$response = json_decode( $response, true );
		// Cycle through the extensions
		if ( ! empty( $response ) && ! empty( $response['extensions'] ) ) {
			foreach ( $response['extensions'] as $extension ) {
				// Skip if we don't have a product id
				if ( ! isset( $extension['product_id'] ) ) {
					continue;
				}
				// Retrieve data from the DB.
				$ext_data = get_option( $extension['product_id'] . '-license', false );
				// Check if the function exists, if not, we include it
				if ( ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				// If we have a license, we check if it's active and return true
				if ( ! empty( $ext_data ) && 'active' === $ext_data['status'] && is_plugin_active( $extension['product_id'] . '/' . $extension['product_id'] . '.php' ) ) {
					$return = true;
					break;
				}
			}
		}
		// Remove other upsells also by returning true
		if ( $return ) {
			add_filter( 'dlm_remove_upsells', '__return_true' );
		}
		// Set class variable. Can be used in other functions so that we don't have to check again.
		$this->active_license = $return;
		// Return the value
		return $return;
	}

	/**
	 * Settings Logging tab upsell
	 *
	 *
	 * @since 5.0.0
	 */
	public function license_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-pro' ) ) {
			$this->generate_upsell_box(
				__( 'DLM PRO', 'download-monitor' ),
				__( 'Manage license activation and deactivation, and install extensions seamlessly on-the-go.', 'download-monitor' ),
				'license',
				'dlm-pro'
			);
		}
	}

	/**
	 * Upsell for Contact Form 7 Lock sub-tab
	 *
	 * @since 5.0.13
	 */
	public function upsell_tab_section_content_cf7_lock() {
		if ( ! $this->check_extension( 'dlm-cf7-lock' ) ) {
			$this->generate_upsell_box(
				__( 'Contact Form 7', 'download-monitor' ),
				__( 'The Contact Form 7 Lock extension for Download Monitor allows you to require users to fill out a Contact Form 7 form before they gain access to a download.', 'download-monitor' ),
				'email_lock',
				'email-lock'
			);
		}
	}

	/**
	 * Upsell for WP Forms Lock sub-tab
	 *
	 * @since 5.0.13
	 */
	public function upsell_tab_section_content_wpforms_lock() {
		if ( ! $this->check_extension( 'dlm-wpforms-lock' ) ) {
			$this->generate_upsell_box(
				__( 'WP Forms', 'download-monitor' ),
				__( 'The WPForms Lock extension for Download Monitor allows you to require users to fill out a WPForms form before they gain access to a download.', 'download-monitor' ),
				'email_lock',
				'email-lock'
			);
		}
	}

	/**
	 * Upsell page/modals
	 *
	 * @since 5.0.13
	 */
	public function add_upsell_modals() {
		$upsells = DLm_Upsells::get_modal_upsells();
		if ( ! empty( $upsells ) ) {
			// Cycle through the upsells and add them as submenus
			foreach ( $upsells as $key => $upsell ) {
				add_submenu_page( 'edit.php?post_type=dlm_download', $upsell, $upsell, 'manage_options', $key . '_upsell_modal', '' );
			}
		}
	}

	/**
	 * Modal upsells
	 *
	 * @since 5.0.13
	 */
	public static function get_modal_upsells() {
		$upsells = array(
			'dlm_aam'     => __( 'Global Rules', 'download-monitor' ),
			'dlm_buttons' => __( 'Buttons', 'download-monitor' ),
		);

		return $upsells;
	}

	public function inline_script_for_redirection() {
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const link = document.querySelector('a[href*="edit.php?post_type=dlm_download&page=#dlm-lite-vs-pro"]');
				if (link) {
					link.addEventListener('click', function(event) {
						event.preventDefault();
						
						window.open(
						'https://download-monitor.com/free-vs-pro/?utm_source=dlm-lite&utm_medium=link&utm_campaign=upsell&utm_term=lite-vs-pro',
						'_blank'
					);
					});
				}
			});
		</script>
		<?php
	}
}
