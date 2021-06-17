<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

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

	/**
	 * DLM_Upsells constructor.
	 *
	 * @since 4.4.5
	 */
	public function __construct() {

		add_action( 'dlm_tab_content_general', array( $this, 'general_tab_upsell' ), 15 );

		add_action( 'dlm_tab_content_access', array( $this, 'access_tab_upsell' ), 15 );

		add_action( 'dlm_tab_content_logging', array( $this, 'logging_tab_upsell' ), 15 );

		add_action( 'dlm_tab_content_pages', array( $this, 'pages_tab_upsell' ), 15 );

		add_action( 'dlm_tab_content_misc', array( $this, 'misc_tab_upsell' ), 15 );

		add_action( 'dlm_tab_content_endpoints', array( $this, 'endpoint_tab_upsell' ), 15 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );

		$this->set_extensions();

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

	/**
	 * Generate the all-purpose upsell box
	 *
	 * @param       $title
	 * @param       $description
	 * @param       $tab
	 * @param array $features
	 *
	 * @return string
	 *
	 * @since 4.4.5
	 */
	public function generate_upsell_box( $title, $description, $tab, $features = array() ) {

		$upsell_box = '<div class="wpchill-upsell">';
		$upsell_box .= '<h2>' . esc_html( $title ) . '</h2>';

		if ( ! empty( $features ) ) {

			$upsell_box .= '<ul class="wpchill-upsell-features">';

			foreach ( $features as $feature ) {

				$upsell_box .= '<li>';
				if ( isset( $feature['tooltip'] ) && '' != $feature['tooltip'] ) {
					$upsell_box .= '<div class="wpchill-tooltip"><span>[?]</span>';
					$upsell_box .= '<div class="wpchill-tooltip-content">' . esc_html( $feature['tooltip'] ) . '</div>';
					$upsell_box .= '</div>';
					$upsell_box .= "<p>" . esc_html( $feature['feature'] ) . "</p>";
				} else {
					$upsell_box .= '<span class="wpchill-check dashicons dashicons-yes"></span>' . esc_html( $feature['feature'] );
				}

				$upsell_box .= '</li>';

			}
			$upsell_box .= '</ul>';
		}

		$campaign = $tab;

		$upsell_box .= '<p class="wpchill-upsell-description">' . esc_html( $description ) . '</p>';
		$upsell_box .= '<p>';

		$buttons = '<a target="_blank" href="https://download-monitor.com/extensions/?utm_source=upsell&utm_medium=' . $tab . '_tab_upsell-tab&utm_campaign=' . $campaign . '" class="button-primary button">' . esc_html__( 'Get Extension!', 'download-monitor' ) . '</a>';

		$upsell_box .= apply_filters( 'dlm_upsell_buttons', $buttons, $tab );

		$upsell_box .= '</p>';
		$upsell_box .= '</div>';

		return $upsell_box;
	}

	/**
	 * Settings General tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function general_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-download-duplicator' ) ) {

			echo $this->generate_upsell_box( 'Duplicate your downloads', 'You’re one click away from duplicating downloads, including their data, versions, and files.', 'general' );
		}

		if ( ! $this->check_extension( 'dlm-email-notification' ) ) {

			echo $this->generate_upsell_box( 'Email notifications', 'Create an email alert to be notified each time one of your files has been downloaded.', 'general' );
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

			echo $this->generate_upsell_box( 'Advanced access manager', 'Limit access to your downloads by setting advanced access rules and restrictions with this extension.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-twitter-lock' ) ) {

			echo $this->generate_upsell_box( 'Twitter lock', 'Allow your users to tweet a pre-defined text before accessing a download.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-email-lock' ) ) {

			echo $this->generate_upsell_box( 'Email lock', 'Require your users’ email addresses to send newsletters and create a list of your customers.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-gravity-forms' ) ) {

			echo $this->generate_upsell_box( 'Gravity forms extension', 'Ask users to fill in a form created on Gravity Forms before they start downloading your files.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-ninja-forms' ) ) {

			echo $this->generate_upsell_box( 'Ninja forms extension', 'Use the Ninja Forms extension to add forms easily to your download files.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-mailchimp' ) ) {

			echo $this->generate_upsell_box( 'Ninja forms extension', 'Use the Ninja Forms extension to add forms easily to your download files.', 'access' );
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
			echo $this->generate_upsell_box( 'Captcha', 'Stop bots from spamming your downloads and ask users to complete Google reCAPTCHA.', 'logging' );
		}

	}


	/**
	 * Settings Logging tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function pages_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-terms-conditions' ) ) {

			echo $this->generate_upsell_box( 'Terms & Conditions', 'Easily require your visitors to agree to your terms and conditions before downloading files.', 'pages' );
		}

		if ( ! $this->check_extension( 'dlm-page-addon' ) ) {

			echo $this->generate_upsell_box( 'Page Addon', 'List all downloads, categories, tags, and showcase info pages of each resource with a self-contained [download_page] shortcode!', 'pages' );
		}

	}

	/**
	 * Settings Misc tab upsell
	 *
	 *
	 * @since 4.4.5
	 */
	public function misc_tab_upsell() {

		if ( ! $this->check_extension( 'dlm-buttons' ) ) {

			echo $this->generate_upsell_box( 'Buttons', 'The Buttons extension allows you to customize your download buttons as you please in order to improve the user experience. Create stunning buttons without needing any coding skills!', 'misc' );
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

			echo $this->generate_upsell_box( 'Importer', 'Easily import your downloads, including their categories, tags, and files.
', 'endpoint' );
		}

		if ( ! $this->check_extension( 'dlm-csv-exporter' ) ) {

			echo $this->generate_upsell_box( 'Exporter', 'With a single click, you can quickly export your downloads and their tags, categories, and file versions to a CSV file.
', 'endpoint' );
		}

	}

	/**
	 * Add upsell metaboxes
	 *
	 * @since 4.4.5
	 */
	public function add_meta_boxes() {

		if ( ! $this->check_extension( 'dlm-download-page' ) ) {

			add_meta_box(
					'dlm-download-page-upsell',
					esc_html__( 'Downloading page', 'download-monitor' ),
					array( $this, 'output_download_page_upsell' ),
					'dlm_download',
					'side',
					'high'
			);
		}

		if ( ! $this->check_extension( 'dlm-amazon-s3' ) ) {

			add_meta_box(
					'dlm-amazon-s3-upsell',
					esc_html__( 'Amazon S3', 'download-monitor' ),
					array( $this, 'output_amazon_s3_upsell' ),
					'dlm_download',
					'normal',
			);
		}

		if ( ! $this->check_extension( 'dlm-google-drive' ) ) {
			add_meta_box(
					'dlm-google-drive-upsell',
					esc_html__( 'Google drive', 'download-monitor' ),
					array( $this, 'output_google_drive_upsell' ),
					'dlm_download',
					'normal',
			);
		}
	}

	/**
	 * Output the DLM Downloading Page extension upsell
	 *
	 * @since 4.4.5
	 */
	public function output_download_page_upsell() {
		?>
		<div class="wpchill-upsells-wrapper">
			<div class="wpchill-upsell wpchill-upsell-item">
				<p class="wpchill-upsell-description"><?php esc_html_e( 'Customize the downloading page by adding banners, ads, and anything you like.', 'download-monitor' ) ?></p>
				<p>
					<a target="_blank"
					   style="margin-top:10px;"
					   href="https://download-monitor.com/extensions/?utm_source=upsell&utm_medium=sorting-metabox&utm_campaign=wpchill-sorting"
					   class="button-primary button"><?php esc_html_e( 'Get Extension!', 'download-monitor' ) ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the DLM Amazon S3 extension upsell
	 *
	 * @since 4.4.5
	 */
	public function output_amazon_s3_upsell() {
		?>
		<div class="wpchill-upsells-wrapper">
			<div class="wpchill-upsell wpchill-upsell-item">
				<p class="wpchill-upsell-description"><?php esc_html_e( 'Use Amazon S3 links for Download Monitor files to run secure, expiring download links.', 'download-monitor' ) ?></p>
				<p>
					<a target="_blank"
					   style="margin-top:10px;"
					   href="https://download-monitor.com/extensions/?utm_source=upsell&utm_medium=sorting-metabox&utm_campaign=wpchill-sorting"
					   class="button-primary button"><?php esc_html_e( 'Get Extension!', 'download-monitor' ) ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the DLM Google Drive extension upsell
	 *
	 * @since 4.4.5
	 */
	public function output_google_drive_upsell() {
		?>
		<div class="wpchill-upsells-wrapper">
			<div class="wpchill-upsell wpchill-upsell-item">
				<p class="wpchill-upsell-description"><?php esc_html_e( 'With this extension, you can integrate your files from Google Drive into Download Monitor.', 'download-monitor' ) ?></p>
				<p>
					<a target="_blank"
					   style="margin-top:10px;"
					   href="https://download-monitor.com/extensions/?utm_source=upsell&utm_medium=sorting-metabox&utm_campaign=wpchill-sorting"
					   class="button-primary button"><?php esc_html_e( 'Get Extension!', 'download-monitor' ) ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Set the existing extensions
	 *
	 * @since 4.4.5
	 */
	public function set_extensions() {

		$this->extensions = apply_filters( 'dlm_extensions', array() );
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

		if ( empty( $this->extensions ) || ! in_array( $extension, $this->extensions ) ) {
			return false;
		}

		return true;
	}
}

DLM_Upsells::get_instance();