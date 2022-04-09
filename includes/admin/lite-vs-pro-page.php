<?php
// Set the PROs
$pro_arguments = array(
	'twitter_lock'            => array(
		'title'       => esc_html__( 'Twitter lock', 'download-monitor' ),
		'description' => esc_html__( 'The Twitter Lock extension for Download Monitor allows you to require users to tweet your pre-defined text before they gain access to a download.', 'download-monitor' ),
	),
	'captcha'                 => array(
		'title'       => esc_html__( 'Captcha', 'download-monitor' ),
		'description' => esc_html__( 'The Captcha extension for Download Monitor allows you to require users to complete a Google reCAPTCHA before they gain access to a download.', 'download-monitor' ),
	),
	'buttons'                 => array(
		'title'       => esc_html__( 'Buttons', 'download-monitor' ),
		'description' => esc_html__( 'Create beautiful, fully customizable download buttons with our Buttons extension. No coding or file editing required!', 'download-monitor' ),
	),
	'terms_and_conditions'    => array(
		'title'       => esc_html__( 'Terms and conditions', 'download-monitor' ),
		'description' => esc_html__( 'Require your users to accept your terms and conditions before they can download your files.', 'download-monitor' ),
	),
	'email_ notification'     => array(
		'title'       => esc_html__( 'Email Notification', 'download-monitor' ),
		'description' => esc_html__( 'The Email Notification extension for Download Monitor sends you an email whenever one of your files is downloaded.', 'download-monitor' ),
	),
	'csv_importer'            => array(
		'title'       => esc_html__( 'CSV Importer', 'download-monitor' ),
		'description' => esc_html__( 'Mass import up to thousands of Downloads into Download Monitor with the CSV Importer.', 'download-monitor' ),
	),
	'csv_exporter'            => array(
		'title'       => esc_html__( 'CSV Exporter', 'download-monitor' ),
		'description' => esc_html__( 'Export all your downloads including categories, tags and file versions to a CSV file with a single click!', 'download-monitor' ),
	),
	'page_addon'              => array(
		'title'       => esc_html__( 'Page Addon', 'download-monitor' ),
		'description' => esc_html__( 'Add a self contained [download_page] shortcode to your site to list downloads, categories, tags, and show info pages about each of your resources.', 'download-monitor' ),
	),
	'downloading_page'        => array(
		'title'       => esc_html__( 'Downloading Page', 'download-monitor' ),
		'description' => esc_html__( 'The Downloading Page extension for Download Monitor forces your downloads to be served from a separate page.', 'download-monitor' ),
	),
	'amazon_s3'               => array(
		'title'       => esc_html__( 'Amazon S3', 'download-monitor' ),
		'description' => esc_html__( 'Link to files hosted on Amazon s3 so that you can serve secure, expiring download links.', 'download-monitor' ),
	),
	'google_drive'            => array(
		'title'       => esc_html__( 'Google Drive', 'download-monitor' ),
		'description' => esc_html__( 'Lets you use the files hosted on your Google Drive as Download Monitor files.', 'download-monitor' ),
	),
	'advanced_access_manager' => array(
		'title'       => esc_html__( 'Advanced Access Manager', 'download-monitor' ),
		'description' => esc_html__( 'The Advanced Access Manager extension allows you to create advanced download limitations per download and on a global level.', 'download-monitor' ),
	),
	'nina_forms_lock'         => array(
		'title'       => esc_html__( 'Ninja Forms Lock', 'download-monitor' ),
		'description' => esc_html__( 'The Ninja Forms extension for Download Monitor allows you to require users to fill in a Ninja Forms form before they gain access to a download.', 'download-monitor' ),
	),
	'mailchimp_lock'          => array(
		'title'       => esc_html__( 'MailChimp Lock', 'download-monitor' ),
		'description' => esc_html__( 'The MailChimp Lock for Download Monitor allows you to require users to be subscribed to your MailChimp list before they gain access to a download.', 'download-monitor' ),
	),
	'gravity_forms_lock'      => array(
		'title'       => esc_html__( 'GravityForms Lock', 'download-monitor' ),
		'description' => esc_html__( 'The Gravity Forms extension for Download Monitor allows you to require users to fill out a Gravity Forms form before they gain access to a download.', 'download-monitor' ),
	),
	'email_lock'              => array(
		'title'       => esc_html__( 'Email Lock', 'download-monitor' ),
		'description' => esc_html__( 'The Email Lock extension for Download Monitor allows you to require users to fill in their email address before they gain access to a download.', 'download-monitor' ),
	),
);
?>
<div class="wrap rsvp-lite-vs-premium">
	<hr class="wp-header-end" />
	<div class="free-vs-premium">
		<!--  Table header -->
		<div class="wpchill-plans-table table-header">
			<div class="wpchill-pricing-package wpchill-empty">
				<!--This is an empty div so that we can have an empty corner-->
			</div>
			<div class="wpchill-pricing-package wpchill-title wpchill-modula-grid-gallery-business">
				<p class="wpchill-name"><strong>PRO</strong></p>
			</div>
			<div class="wpchill-pricing-package wpchill-title wpchill-modula-lite">
				<p class="wpchill-name"><strong>LITE</strong></p>
			</div>
		</div>
		<!--  Table content -->

        <?php
        foreach( $pro_arguments as $pro ) {
            ?>
            <div class="wpchill-plans-table">
			<div class="wpchill-pricing-package feature-name">
				<h3><?php echo esc_html( $pro['title']); ?></h3>
				<p class="tab-header-description modula-tooltip-content">
					<?php echo esc_html( $pro['description'] ); ?>
				</p>
			</div>
			<div class="wpchill-pricing-package">
				<span class="dashicons dashicons-saved"></span>
			</div>
			<div class="wpchill-pricing-package">
				<span class="dashicons dashicons-no-alt"></span>
			</div>
		</div>
            <?php
        }
        ?>
		<!-- Support -->
		<div class="wpchill-plans-table">
			<div class="wpchill-pricing-package feature-name">
				<h3><?php esc_html_e( 'Support', 'download-monitor' ); ?></h3>
			</div>
			<div class="wpchill-pricing-package">Priority</div>
			<div class="wpchill-pricing-package"><a href="https://wordpress.org/support/plugin/download-monitor/"
					target="_blank">wp.org</a>
			</div>
		</div>
		<!--  Table footer -->
		<div class="wpchill-plans-table tabled-footer">
			<div class="wpchill-pricing-package wpchill-empty">
				<!--This is an empty div so that we can have an empty corner-->
			</div>
			<div class="wpchill-pricing-package wpchill-title wpchill-modula-grid-gallery-business">

				<a href="https://www.download-monitor.com/pricing/?utm_source=download-monitor&utm_medium=lite-vs-pro&utm_campaign=upsell" target="_blank"
					class="button button-primary button-hero "><span class="dashicons dashicons-cart"></span>
					<?php esc_html_e( 'Upgrade now!', 'download-monitor' ); ?> </a>

			</div>
			<div class="wpchill-pricing-package wpchill-title wpchill-modula-lite">


			</div>
		</div>
	</div>
</div>