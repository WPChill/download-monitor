<?php

class WPChill_About_Us {

	private $plugin_cpt;
	private $plugin_term;

	public function __construct( $plugin_cpt, $plugin_term = '' ) {

		$this->plugin_cpt = $plugin_cpt;
		$this->plugin_term = $plugin_term;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

		add_filter( 'admin_menu', array( $this, 'add_menu_item' ), 99, 1 );

		// Clear all notices
		add_action( 'in_admin_header', array( $this, 'clear_admin_notices' ), 99 );

		new WPChill_Rest_Api();
	}


	/**
	 * Adds dashboard to addon's admin menu
	 *
	 * @return void
	 *
	 * @since 2.7.5
	 */
	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=' . $this->plugin_cpt,
			esc_html__( 'About Us', 'download-monitor' ),
			esc_html__( 'About Us', 'download-monitor' ),
			'manage_options',
			'wpchill-about-us',
			array(
				$this,
				'dashboard_view',
			),
			999
		);
	}

	public function clear_admin_notices() {

		if ( $this->is_about_us() ) {
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'admin_notices' );
		}
	}

	public function render_content() {
		?>
		<div class="wpchill_container">
			<h1 class="wpchill_title"><?php esc_html_e( 'About WPChill', 'download-monitor' ); ?></h1>
			<img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'icons/wpchill-logo.jpg' ); ?>" alt="WPChill Logo" class="wpchill_logo">
			<p class="wpchill_tagline"><?php esc_html_e( 'Reliable WordPress Solutions Tailored for You', 'download-monitor' ); ?></p>
			<p class="wpchill_description"><?php esc_html_e( 'At WPChill, our commitment goes beyond just creating WordPress solutions—we\'re dedicated to delivering user-friendly products that help people save time, money, and effort. Every product we offer is built with care, shaped by our experience, and backed by our promise to support our users every step of the way. When you choose WPChill, you\'re not just purchasing a product; you\'re gaining a reliable partner.', 'download-monitor' ); ?></p>
			
			<table class="wpchill_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'download-monitor' ); ?></th>
						<th><?php esc_html_e( 'Description', 'download-monitor' ); ?></th>
						<th><?php esc_html_e( 'Website', 'download-monitor' ); ?></th>
						<th><?php esc_html_e( 'Try plugin', 'download-monitor' ); ?></th>
					</tr> 
				</thead>
				<tbody>
					<?php $this->render_rows(); ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_rows() {

		$addons = array(
			array(
				'name'        => 'Modula',
				'slug'        => 'modula',
				'path'        => 'modula-best-grid-gallery/Modula.php',
				'description' => 'Easily create stunning, customizable photo galleries and albums with Modula’s powerful features.',
				'url'         => 'https://wp-modula.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Download Monitor',
				'slug'        => 'download-monitor',
				'path'        => 'download-monitor/download-monitor.php',
				'description' => 'Manage, track, and control file downloads on your WordPress site with ease.',
				'url'         => 'https://download-monitor.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Strong Testimonials',
				'slug'        => 'strong-testimonials',
				'path'        => 'strong-testimonials/strong-testimonials.php',
				'description' => 'Collect, manage, and showcase customer reviews beautifully with this flexible testimonial plugin.',
				'url'         => 'https://strongtestimonials.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Kali Forms',
				'slug'        => 'kali-forms',
				'path'        => 'kali-forms/kali-forms.php',
				'description' => 'Build powerful and user-friendly forms quickly with Kali Forms’ intuitive drag-and-drop builder.',
				'url'         => 'https://kaliforms.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Passster',
				'slug'        => 'content-protector',
				'path'        => 'content-protector/content-protector.php',
				'description' => 'Increase website and content protection with easy-to-use features like password, CAPTCHA, and user role restrictions.',
				'url'         => 'https://passster.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Filr',
				'slug'        => 'filr-protection',
				'path'        => 'filr-protection/filr-protection.php',
				'description' => 'Easily build and manage a document library with secure file sharing and advanced access controls.',
				'url'         => 'https://wpdocumentlibrary.com//?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'ImageSEO',
				'slug'        => 'imageseo',
				'path'        => 'imageseo/imageseo.php',
				'description' => 'Optimize images automatically for better SEO and accessibility with AI-powered metadata and alt text generation.',
				'url'         => 'https://imageseo.io/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'RSVP and Event Management',
				'slug'        => 'rsvp',
				'path'        => 'rsvp/wp-rsvp.php',
				'description' => 'Easily create and manage RSVPs, events, and guest lists with this event management solution.',
				'url'         => 'https://rsvpproplugin.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
			array(
				'name'        => 'Htaccess File Editor',
				'slug'        => 'htaccess-file-editor',
				'path'        => 'htaccess-file-editor/htaccess-file-editor.php',
				'description' => 'Safely edit your .htaccess file directly from WordPress to improve site performance and security.',
				'url'         => 'https://wpchill.com/?utm_source=' . $this->plugin_cpt . '&utm_medium=link&utm_campaign=about-us&utm_term=' . $this->plugin_term . '+website+link',
			),
		);

		foreach ( $addons as $addon ) {
			?>
			<tr>
			<td><?php echo isset( $addon['name'] ) ? esc_html( $addon['name'] ) : ''; ?></td>
			<td><?php echo isset( $addon['description'] ) ? wp_kses_post( $addon['description'] ) : ''; ?></td>
			<td><a target="_BLANK" href="<?php echo isset( $addon['url'] ) ? esc_html( $addon['url'] ) : '#'; ?>"><?php esc_html_e( 'Details', 'download-monitor' ); ?></a></td>
			<td>
			<?php
			$addon_status = $this->is_addon_installed( $addon['path'] );
			if ( 'false' !== $addon_status ) :

				?>
					<button class="button wpchill_install_partener_addon"
							data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
							data-action="<?php echo( 'install' === $addon_status ? 'install' : 'activate' ); ?>"
							data-plugin="<?php echo esc_attr( $addon['path'] ); ?>">
					<?php 'install' === $addon_status ? esc_html_e( 'Install', 'download-monitor' ) : esc_html_e( 'Activate', 'download-monitor' ); ?>
					</button>
				<?php else : ?>
					<button class="button"
							disabled="disabled"><?php esc_html_e( 'Active', 'download-monitor' ); ?> </button>
				<?php endif; ?>
			</td>
			</tr>
			<?php
		}
	}

	/**
	 * Checks if WP.org addon is installed and/or active
	 * @return string
	 * @since 2.11.12
	 */

	private function is_addon_installed( $plugin_path ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		if ( empty( $all_plugins[ $plugin_path ] ) ) {
			return 'install';
		} elseif ( ! is_plugin_active( $plugin_path ) ) {
				return 'activate';
		} else {
			return 'false';
		}
	}

	/**
	 * Remove Add New link from menu item
	 *
	 * @param $submenu_file
	 *
	 * @return mixed
	 *
	 * @since 2.11.12
	 */
	public function dashboard_view() {

		echo '<div id="wpchill_about_us_container">';
		$this->render_content();
		echo '</div>';
	}

	/**
	 * Check if about us page
	 *
	 * @param $return
	 *
	 * @return bool|mixed
	 * @since 2.11.12
	 */

	private function is_about_us() {

		if ( isset( $_GET['page'] ) && 'wpchill-about-us' === $_GET['page'] ) {
			return true;
		}

		return false;
	}


	public function enqueue_scripts() {

		// only load assets on about us page
		if ( ! $this->is_about_us() ) {
			return;
		}

		wp_enqueue_style( 'wpchill-about-us-style', plugin_dir_url( __FILE__ ) . 'assets/css/about-us.css', array(), '1.0.0' );
		wp_enqueue_script(
			'modula-about-us-script',
			plugin_dir_url( __FILE__ ) . 'assets/js/about-us.js',
			array(
				'wp-api-fetch',
				'updates',
			),
			'1.0.0',
			true
		);
	}

	public static function activate_plugin( $plugin_slug ) {

		if ( ! $plugin_slug ) {
			return array(
				'success' => false,
				'message' => 'Plugin slug is missing.',
			);
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$result = activate_plugin( $plugin_slug );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => 'Plugin activated.',
		);
	}
}

