<?php

class DLM_Admin_Fields_Field_InstallPlugin extends DLM_Admin_Fields_Field {

	/** @var string */
	private $slug;

	/** @var string */
	private $plugin_path;

	/** @var string */
	private $label;

	/**
	 * DLM_Admin_Fields_Field constructor.
	 *
	 * @param String $name
	 * @param String $link
	 * @param String $label
	 *
	 * @since 4.6.0
	 */
	public function __construct( $name, $link, $label ) {

		if ( ! is_array( explode( '/', $link ) ) ) {
			return;
		}

		$this->plugin_path = $link;
		$slug              = explode( '/', $link );
		$this->slug        = $slug[0];
		$this->label       = $label;
		parent::__construct( $name, '', '' );
	}

	/**
	 * Check for plugin installed
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	private function check_if_installed() {

		if ( is_file( WP_PLUGIN_DIR . '/' . $this->plugin_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if plugin is active
	 *
	 * @return void
	 *
	 * @since 4.6.0
	 */
	private function check_if_active() {

		if ( is_plugin_active( $this->plugin_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieve action link attributes
	 * The return array is composed of the following : disabled , action and slug. We need to pass the array like this in order to maintain PHP 5.6 compatibility.
	 *
	 * @return array
	 *
	 * @since 4.6.0
	 */
	private function get_attributes() {

		if ( empty( $this->slug ) ) {

			return array(
				'disabled',
				'',
				'',
			);
		}

		if ( ! $this->check_if_installed() ) {
			return array(
				'',
				'install',
				$this->slug,
			);
		}

		if ( ! $this->check_if_active() ) {
			return array(
				'',
				'activate',
				$this->slug,
			);
		}

		return array(
			'disabled',
			'',
			$this->slug,
		);
	}

	/**
	 * Renders field
	 *
	 * The Button is quite an odd 'field'. It's basically just an a tag .
	 *
	 * @since 4.6.0
	 */
	public function render() {
		list( $disabled, $action, $slug ) = $this->get_attributes();

		$activate_url = add_query_arg(
			array(
				'action'        => 'activate',
				'plugin'        => rawurlencode( $this->plugin_path ),
				'plugin_status' => 'all',
				'paged'         => '1',
				'_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $this->plugin_path ),
			),
			admin_url( 'plugins.php' )
		);
		?>
		<a class="button button-primary dlm-install-plugin-link" <?php echo ( 'disabled' === $disabled ) ? 'disabled' : ''; ?> data-action="<?php echo esc_attr( $action ); ?>" data-activation_url="<?php echo esc_url( $activate_url ); ?>" href="#" data-slug="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html__( 'Install' ); ?></a><span class="dlm-install-plugin-actions"><?php echo ( '' === $action ) ? esc_html__( 'Plugin already installed and activated', 'download-monitor' ) : ''; ?></span>
		<?php
	}
}
