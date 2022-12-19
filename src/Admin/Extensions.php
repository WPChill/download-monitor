<?php
/**
 * Extensions Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use \WPChill\DownloadMonitor\Util;

/**
 * DLM_Admin_Extensions Class
 */
class DLM_Admin_Extensions {

	/**
	 * Holds the class object.
	 *
	 * @since 4.4.5
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Json response
	 *
	 * @var
	 *
	 * @since 4.4.5
	 */
	private $response;

	/**
	 * DLM's extensions
	 *
	 * @var array
	 *
	 * @since 4.4.5
	 */
	private $extensions = array();

	/**
	 * DLM's extensions tabs
	 *
	 * @var array
	 *
	 * @since 4.4.5
	 */
	private $tabs = array();

	/**
	 * DLM's installed extensions
	 *
	 * @var array
	 *
	 * @since 4.4.5
	 */
	private $installed_extensions = array();

	/**
	 * DLM Licensed extensions
	 *
	 * @var array
	 * @since 4.7.4
	 */
	private $licensed_extensions = array();

	/**
	 * Json
	 *
	 * @var mixed|string
	 *
	 * @since 4.4.5
	 */
	private $json;

	/**
	 * Our Products
	 *
	 * @var
	 *
	 * @since 4.4.5
	 */
	private $products;


	public function __construct() {

		// Add the extensions menu links
		add_filter( 'dlm_admin_menu_links', array( $this, 'extensions_pages' ), 30 );

		// Remove not needed menu link from appearing in dashboard
		add_filter( 'submenu_file', array( $this, 'remove_submenu_item' ) );

		// Load our required data
		add_action( 'admin_init', array( $this, 'load_data' ), 15 );

		add_filter( 'dlm_add_edit_tabs', array( $this, 'dlm_cpt_tabs' ) );

		add_filter( 'dlm_settings', array( $this, 'remove_pro_badge' ), 99 );


	}

	/**
	 * Add the installed extensions tab to DLM CPT
	 *
	 * @param $tabs
	 *
	 * @since 4.4.5
	 */
	public function dlm_cpt_tabs( $tabs ) {

		if ( count( $this->installed_extensions ) > 0 ) {

			$tabs['dlm-installed-extensions'] = array(
					'name'     => esc_html__( 'Installed Extensions', 'download-monitor' ),
					'url'      => admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' ),
					'target'   => '',
					'priority' => '20',
			);
		}

		return $tabs;
	}


	/**
	 * Loads required data and sets tabs
	 *
	 * @since 4.4.5
	 */
	public function load_data() {

		$loader     = new Util\ExtensionLoader();
		$this->json = $loader->fetch();

		$this->products = DLM_Product_Manager::get()->get_products();

		// Set the extensions
		$this->set_response();

		$this->set_tabs();

		$this->set_licensed_extensions();

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Admin_Extensions object.
	 * @since 4.4.5
	 *
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Admin_Extensions ) ) {
			self::$instance = new DLM_Admin_Extensions();
		}

		return self::$instance;

	}

	/**
	 * Add extensions menu links
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function extensions_pages( $links ) {

		$links[] = array(
				'page_title' => __( 'Download Monitor Extensions', 'download-monitor' ),
				'menu_title' => '<span style="color:#419CCB;font-weight:bold;">' . __( 'Extensions', 'download-monitor' ) . '</span>',
				'capability' => 'manage_options',
				'menu_slug'  => 'dlm-extensions',
				'function'   => array( $this, 'available_extensions' ),
				'priority'   => 50,
		);

		$links[] = array(
				'page_title' => __( 'Download Monitor Installed Extensions', 'download-monitor' ),
				'menu_title' => __( 'Installed Extensions', 'download-monitor' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'dlm-installed-extensions',
				'function'   => array( $this, 'installed_extensions_page' ),
				'priority'   => 65,
		);

		return $links;
	}

	/**
	 * Remove the submenus we don't want to show
	 *
	 * @param $submenu_file
	 *
	 * @return mixed
	 *
	 * @since 4.4.5
	 */
	public function remove_submenu_item( $submenu_file ) {

		remove_submenu_page( 'edit.php?post_type=dlm_download', 'dlm-installed-extensions' );

		return $submenu_file;
	}


	/**
	 * Set DLM's extensions
	 *
	 * @since 4.4.5
	 */
	public function set_response() {

		$this->response = json_decode( $this->json );

		if ( ! isset( $this->response ) ) {
			return;
		}

		// Get all extensions
		$this->extensions = $this->response->extensions;

		// Loop through extensions
		foreach ( $this->extensions as $extension_key => $extension ) {
			if ( isset( $this->products[ $extension->product_id ] ) ) {
				$this->installed_extensions[] = $extension;
				unset( $this->extensions[ $extension_key ] );
			}
		}

	}


	/**
	 * Output DLM's extensions page
	 *
	 * @since 4.4.5
	 */
	public function available_extensions() {

		// Allow user to reload extensions
		if ( isset( $_GET['dlm-force-recheck'] ) ) {
			delete_transient( 'dlm_extension_json' );
		}

		// WPChill Welcome Class
		require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

		if ( ! class_exists( 'WPChill_Welcome' ) ) {
			return;
		}

		$welcome = WPChill_Welcome::get_instance();
		wp_enqueue_style( array( 'dlm-welcome-style' ) );

		?>
		<div class="wrap dlm_extensions_wrap">
			<div class="icon32 icon32-posts-dlm_download" id="icon-edit"><br/></div>
			<h1>
				<?php echo esc_html__( 'Download Monitor Extensions', 'download-monitor' ); ?>
			</h1>
			<?php

			if ( false !== $this->json ) {

				// Display message if it's there
				if ( isset( $this->response->message ) && '' !== $this->response->message ) {
					echo '<div id="message" class="updated">' . esc_html( $this->response->message ) . '</div>';
				}

				// Extensions

				echo '<p>' . sprintf( esc_html__( 'Extend Download Monitor with its powerful free and paid extensions. %sClick here to browse all extensions%s', 'download-monitor' ), '<a href="https://www.download-monitor.com/extensions/?utm_source=plugin&utm_medium=link&utm_campaign=extensions-top" target="_blank">', '</a>' ) . '</p>';

				$active_tab = 'dlm-extensions';

				if ( isset( $_GET['page'] ) && isset( $tabs[ $_GET['page'] ] ) ) {
					$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
				}

				?>
				<h2 class="nav-tab-wrapper">
					<?php DLM_Admin_Helper::dlm_tab_navigation( $this->tabs, $active_tab ); ?>
				</h2>
				<a href="<?php echo esc_url( add_query_arg( 'dlm-force-recheck', '1', admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ) ) ); ?>"
				   class="button dlm-reload-button">
					<?php esc_html_e( 'Reload Extensions', 'download-monitor' ); ?>
				</a>
				<?php

				// Available Extensions
				if ( count( $this->extensions ) > 0 ) {

					echo '<div id="available-extensions" class="settings_panel">';
					echo '<div class="dlm_extensions">';
					?>
					<div id="wpchill-welcome">
						<div class="features">
							<div class="block">
								<?php $welcome->layout_start( 3, 'feature-list clear' ); ?>
								<!-- Let's display the extensions.  -->
								<?php
								foreach ( $this->extensions as $extension ) {
									$welcome->display_extension( $extension->name, wp_kses_post( $extension->desc ), $extension->image, true, '#F08232', $extension->name );
								}
								?><!-- end extensions display -->
								<?php $welcome->layout_end(); ?>
							</div><!-- .block -->
						</div><!-- .features -->
					</div><!-- #wpchill-welcome -->
					<?php
					echo '</div>';
					echo '</div>';

				} else if ( count( $this->installed_extensions ) > 0 ) {
					echo '<p>' . esc_html__( 'Wow, looks like you installed all our extensions. Thanks, you rock!', 'download-monitor' ) . '</p>';
				}

			} else {
				echo '<p>' . esc_html__( 'Couldn\'t load extensions, please try again later.', 'download-monitor' ) . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Output installed extensions page
	 *
	 * @since 4.4.5
	 */
	public function installed_extensions_page() {

		// Allow user to reload extensions
		if ( isset( $_GET['dlm-force-recheck'] ) ) {
			delete_transient( 'dlm_extension_json' );
		}
		
		wp_enqueue_style( array( 'dlm-welcome-style' ) );
		?>
		<div class="wrap dlm_extensions_wrap">
		<div class="icon32 icon32-posts-dlm_download" id="icon-edit"><br/></div>
		<h1>
			<?php esc_html_e( 'Download Monitor Installed Extensions', 'download-monitor' ); ?>

		</h1>
		<?php

		$active_tab = 'dlm-installed-extensions';

		if ( isset( $_GET['page'] ) && isset( $this->tabs[ $_GET['page'] ] ) ) {
			$active_tab = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		echo '<h2 class="nav-tab-wrapper">';

		DLM_Admin_Helper::dlm_tab_navigation( $this->tabs, $active_tab );

		echo '</h2>';
		?>
			<a href="<?php echo esc_url( add_query_arg( 'dlm-force-recheck', '1', admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ) ) ); ?>"
				class="button dlm-reload-button">
				<?php esc_html_e( 'Reload Extensions', 'download-monitor' ); ?>
			</a>
		<?php
		// Installed Extensions
		if ( count( $this->installed_extensions ) > 0 ) {
			// WPChill Welcome Class.
			require_once plugin_dir_path( DLM_PLUGIN_FILE ) . '/includes/submodules/banner/class-wpchill-welcome.php';

			if ( ! class_exists( 'WPChill_Welcome' ) ) {
				return;
			}

			$welcome = WPChill_Welcome::get_instance();
			echo '<div id="installed-extensions" class="settings_panel">';

			echo '<div class="dlm_extensions">';
			?>
			<div id="wpchill-welcome">
						<div class="features">
							<div class="block">
								<?php $welcome->layout_start( 3, 'feature-list clear' ); ?>
								<!-- Let's display the extensions.  -->
								<?php
										foreach ( $this->installed_extensions as $extension ) {
											// Get the product
											$license = $this->products[ $extension->product_id ]->get_license();
											echo '<div class="feature-block dlm_extension">';
											echo '<div class="feature-block__header">';
											if ( '' != $extension->image ) {
												echo '<img src="' . esc_attr( $extension->image ) . '">';
											}
											echo '<h5>' . esc_html( $extension->name ) . '</h5>';
											echo '</div>';
											echo '<p>' . wp_kses_post( $extension->desc  ) . '</p>';
											echo '<div class="extension_license">';
											echo '<p class="license-status' . ( ( $license->is_active() ) ? ' active' : '' ) . '">' . esc_html( strtoupper( $license->get_status() ) ) . '</p>';
											echo '<input type="hidden" id="dlm-ajax-nonce" value="' . esc_attr( wp_create_nonce( 'dlm-ajax-nonce' ) ) . '" />';
											echo '<input type="hidden" id="status" value="' . esc_attr( $license->get_status() ) . '" />';
											echo '<input type="hidden" id="product_id" value="' . esc_attr( $extension->product_id ) . '" />';
											echo '<input type="text" name="key" id="key" value="' . esc_attr( $license->get_key() ) . '" placeholder="License Key"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />';
											echo '<input type="text" name="email" id="email" value="' . esc_attr( $license->get_email() ) . '" placeholder="License Email"' . ( ( $license->is_active() ) ? ' disabled="disabled"' : '' ) . ' />';
											echo '<a href="javascript:;" class="button button-primary">' . ( ( $license->is_active() ) ? 'Deactivate' : 'Activate' ) . '</a>';
											echo '</div>';
											echo '</div>';
										}
								?><!-- end extensions display -->
								<?php $welcome->layout_end(); ?>
							</div><!-- .block -->
						</div><!-- .features -->
					</div><!-- #wpchill-welcome -->
			<?php


			echo '</div>';
			echo '</div>';

		}

		echo '</div>';
	}

	/**
	 * Set DLM's extensions tabs
	 *
	 * @since 4.4.5
	 */
	public function set_tabs() {

		$tabs = array(
				'dlm_downloads'   => array(
						'name'     => esc_html__( 'Downloads', 'download-monitor' ),
						'url'      => admin_url( 'edit.php?post_type=dlm_download' ),
						'target'   => '',
						'priority' => '1',
				),
				'dlm-extensions' => array(
						'name'     => esc_html__( 'Extensions', 'download-monitor' ),
						'url'      => admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ),
						'target'   => '',
						'priority' => '10',
				),
				'suggest_feature' => array(
						'name'     => esc_html__( 'Suggest a feature', 'download-monitor' ),
						'url'      => 'https://forms.gle/3igARBBzrbp6M8Fc7',
						'icon'     => 'dashicons dashicons-external',
						'target'   => '_blank',
						'priority' => '90',
				)
		);

		if ( count( $this->installed_extensions ) > 0 ) {

			$tabs['dlm-installed-extensions'] = array(
					'name'     => esc_html__( 'Installed Extensions', 'download-monitor' ),
					'url'      => admin_url( 'edit.php?post_type=dlm_download&page=dlm-installed-extensions' ),
					'target'   => '',
					'priority' => '20',
			);
		}

		/**
		 * Hook for Extension tabs
		 */
		$this->tabs = apply_filters( 'dlm_settings_tabs', $tabs );

		// Sort tabs based on priority.
		uasort( $this->tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );
	}

	/**
	 * Get DLM's extensions tabs
	 *
	 * @return array
	 *
	 * @since 4.4.5
	 */
	public function get_tabs() {

		return $this->tabs;
	}

	/**
	 * Get extensions
	 *
	 * @return array
	 *
	 * @since 4.4.5
	 */
	public function get_extensions(){
		return $this->installed_extensions;
	}

	/**
	 * Get the available extensions
	 * 
	 * @since 4.5.8
	 */
	public function get_available_extensions() {
		return $this->extensions;
	}

	/**
	 * Removes pro badge if the section has any extension installed
	 *
	 * @return array
	 *
	 * @since 4.4.14
	 */
	public function remove_pro_badge( $settings ){

		foreach($settings as $key => $setting){
			if( !empty( $setting['sections'] ) && isset( $setting['badge'] ) ){
				$settings[$key]['badge'] = false;
			}
		}
		return $settings;
	}

	/**
	 * Get the installed extensions
	 *
	 * @return array
	 */
	public function get_installed_extensions() {
		return $this->installed_extensions;
	}

	/**
	 * Set the licensed extensions
	 *
	 * @return void
	 * @since 4.7.4
	 */
	public function set_licensed_extensions() {
		global $wpdb;
		// Let's retrieve extensions that have a license key.
		$extensions = $wpdb->get_results( $wpdb->prepare( "SELECT `option_name`, `option_value` FROM {$wpdb->prefix}options WHERE `option_name` LIKE %s AND `option_name` LIKE %s;", $wpdb->esc_like( 'dlm-' ) . '%', '%' . $wpdb->esc_like( '-license' ) ), ARRAY_A );

		foreach ( $extensions as $extension ) {
			$extension_name = str_replace( '-license', '', $extension['option_name'] );
			$value          = unserialize( $extension['option_value'] );
			// Extension must have an active status in order to be regitered.
			if ( isset( $value['status'] ) && 'active' === $value['status'] ) {
				$this->licensed_extensions[] = $extension_name;
			}
		}
	}

	/**
	 * Get the licensed extensions
	 *
	 * @return array
	 * @since 4.7.4
	 */
	public function get_licensed_extensions() {
		return $this->licensed_extensions;
	}
}
