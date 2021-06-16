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

			echo $this->generate_upsell_box( 'Duplicate your downloads', 'Duplicate downloads including all data and versions with a single click', 'general' );
		}

		if ( ! $this->check_extension( 'dlm-email-notification' ) ) {

			echo $this->generate_upsell_box( 'Email notifications', 'The Email Notification extension for Download Monitor sends you an email whenever one of your files is downloaded.', 'general' );
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

			echo $this->generate_upsell_box( 'Advanced access manager', 'The Advanced Access Manager extensions allows you to create more advanced download limitations.
', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-twitter-lock' ) ) {

			echo $this->generate_upsell_box( 'Twitter lock', 'The Twitter Lock extension for Download Monitor allows you to require users to tweet your pre-defined text before they gain access to a download.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-email-lock' ) ) {

			echo $this->generate_upsell_box( 'Email lock', 'The Email Lock extension for Download Monitor allows you to require users to fill in their email address before they gain access to a download.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-gravity-forms' ) ) {

			echo $this->generate_upsell_box( 'Gravity forms extension', 'The Gravity Forms extension for Download Monitor allows you to require users to fill out a Gravity Forms form before they gain access to a download.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-ninja-forms' ) ) {

			echo $this->generate_upsell_box( 'Ninja forms extension', 'The Ninja Forms extension for Download Monitor allows you to require users to fill out a Ninja Forms form before they gain access to a download.', 'access' );
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
			echo $this->generate_upsell_box( 'Captcha', 'The Captcha extension for Download Monitor allows you to require users to complete a reCAPTCHA before they gain access to a download.', 'access' );
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

			echo $this->generate_upsell_box( 'Terms & Conditions', 'The Terms and Conditions extension for Download Monitor requires your users to accept your terms and conditions before they can download your files.', 'access' );
		}

		if ( ! $this->check_extension( 'dlm-page-addon' ) ) {

			echo $this->generate_upsell_box( 'Page Addon', 'Add a self contained [download_page] shortcode to your site to list downloads, categories, tags, and show info pages about each of your resources.', 'access' );
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
				<p class="wpchill-upsell-description"><?php esc_html_e( 'The Downloading Page extension for Download Monitor forces your downloads to be served from a seperate page. This page can be for example be used to show ads or display a \'thank you\' message.', 'download-monitor' ) ?></p>
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
				<p class="wpchill-upsell-description"><?php esc_html_e( 'Link to files hosted on Amazon S3 so that you can serve secure, expiring download links.', 'download-monitor' ) ?></p>
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
				<p class="wpchill-upsell-description"><?php esc_html_e( 'The Google Drive extension for Download Monitor lets you use the files hosted on your Google Drive as Download Monitor files.', 'download-monitor' ) ?></p>
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

		if ( empty( $this->extensions) || ! in_array( $extension, $this->extensions ) ) {
			return false;
		}

		return true;
	}
}

DLM_Upsells::get_instance();