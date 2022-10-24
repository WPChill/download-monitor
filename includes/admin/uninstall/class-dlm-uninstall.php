<?php

class DLM_Uninstall {

	/**
	 * Holds the class object.
	 *
	 * @since 4.4.5
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * @since 4.4.5
	 * DLM_Uninstall constructor.
	 *
	 */
	function __construct() {

		// Deactivation
		add_filter( 'plugin_action_links_' . plugin_basename( DLM_PLUGIN_FILE ), array(
				$this,
				'filter_action_links'
		) );

		add_action( 'admin_footer-plugins.php', array( $this, 'add_uninstall_form' ), 16 );
		add_action( 'wp_ajax_dlm_uninstall_plugin', array( $this, 'dlm_uninstall_plugin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'uninstall_scripts' ) );
	}

	/**
	 * Enqueue uninstall scripts
	 *
	 * @since 4.4.5
	 */
	public function uninstall_scripts() {

		$plugin_url = download_monitor()->get_plugin_url();

		$current_screen = get_current_screen();
		if ( in_array( $current_screen->base, array( 'plugins', 'plugins-network' ) ) ) {
			wp_enqueue_style( 'dlm-uninstall', $plugin_url . '/assets/css/dlm-uninstall.min.css', array(), DLM_VERSION );
			wp_enqueue_script( 'dlm-uninstall', $plugin_url . '/assets/js/dlm-uninstall.js', array( 'jquery' ), DLM_VERSION, true );
			wp_localize_script( 'dlm-uninstall', 'wpDLMUninstall', array(
					'redirect_url' => admin_url( '/plugins.php' ),
					'nonce'        => wp_create_nonce( 'dlm_uninstall_plugin' )
			) );
		}
	}

	/**
	 *  Set uninstall link
	 *
	 * @param $links
	 *
	 * @return array
	 *
	 * @since 4.4.5
	 */
	public function filter_action_links( $links ) {

		$links = array_merge( $links, array(
				'<a onclick="javascript:event.preventDefault();" id="dlm-uninstall-link"  class="uninstall-dlm dlm-red-text" href="#">' . esc_html__( 'Uninstall', 'download-monitor' ) . '</a>',
		) );

		return $links;
	}

	/**
	 * Form text strings
	 * These can be filtered
	 *
	 * @since 4.4.5
	 */
	public function add_uninstall_form() {

		// Get our strings for the form
		$form = $this->get_form_info();

		?>
		<div class="dlm-uninstall-form-bg">
		</div>
		<div class="dlm-uninstall-form-wrapper">
            <span class="dlm-uninstall-form" id="dlm-uninstall-form">
                <div class="dlm-uninstall-form-head">
                    <h3><strong><?php echo esc_html( $form['heading'] ); ?></strong></h3>
                    <i class="close-uninstall-form">X</i>
                </div>
        <div class="dlm-uninstall-form-body"><p><?php echo wp_kses_post( $form['body'] ); ?></p>

        <?php
		if ( is_array( $form['options'] ) ) {
			?>
			<div class="dlm-uninstall-options">
                <?php
				foreach ( $form['options'] as $key => $option ) {

					$before_input = '';
					$after_input  = '';
					if ( 'delete_all' == $key ) {
						$before_input = '<strong class="dlm-red-text">';
						$after_input  = '</strong>';
					}

					echo ' <p><input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"> <label for="' . esc_attr( $key ) . '">' . wp_kses_post( $before_input ) . esc_attr( $option['label'] ) . wp_kses_post( $after_input ) . '</label><p class="description">' . esc_html( $option['description'] ) . '</p><br>';
				}
				?>
            </div><!-- .dlm-uninstall-options -->
		<?php } ?>

        </div><!-- .dlm-uninstall-form-body -->
        <p class="deactivating-spinner"><span
					class="spinner"></span><?php echo esc_html__( 'Cleaning...', 'download-monitor' ); ?></p>
        <div class="uninstall">
            <p>
                <a id="dlm-uninstall-submit-form" class="button button-primary"
				   href="#"><?php echo esc_html__( 'Uninstall', 'download-monitor' ); ?></a>
            </p>
        </div>
            </span>
		</div>
		<?php
	}

	/**
	 * Form text strings
	 *
	 * These are non-filterable and used as fallback in case filtered strings aren't set correctly
	 *
	 * @since 4.4.5
	 */
	public function get_form_info() {
		$form            = array();
		$form['heading'] = esc_html__( 'Sorry to see you go', 'download-monitor' );
		$form['body']    = '<strong class="dlm-red-text">' . esc_html__( ' Caution!! This action CANNOT be undone', 'download-monitor' ) . '</strong>';
		$form['options'] = apply_filters( 'dlm_uninstall_options', array(
				'delete_all'        => array(
						'label'       => esc_html__( 'Delete all data', 'download-monitor' ),
						'description' => esc_html__( 'Select this to delete all data Download Monitor plugin and it\'s add-ons have set in your database.', 'download-monitor' )
				),
				'delete_options'    => array(
						'label'       => esc_html__( 'Delete Download Monitor\'s options', 'download-monitor' ),
						'description' => esc_html__( 'Delete options set by Download Monitor plugin and it\'s add-ons  to options table in the database.', 'download-monitor' )
				),
				'delete_transients' => array(
						'label'       => esc_html__( 'Delete Download Monitor set transients', 'download-monitor' ),
						'description' => esc_html__( 'Delete transients set by Download Monitor plugin and it\'s add-ons  to options table in the database.', 'download-monitor' )
				),
				'delete_cpt'        => array(
						'label'       => esc_html__( 'Delete dlm_download custom post type', 'download-monitor' ),
						'description' => esc_html__( 'Delete custom post types set by Download Monitor plugin and it\'s add-ons in the database.', 'download-monitor' )
				),
				'delete_set_tables' => array(
						'label'       => esc_html__( 'Delete set tables  ', 'download-monitor' ),
						'description' => esc_html__( 'Delete tables set by plugin (  Ex.: Logs table )', 'download-monitor' )
				)
		) );

		return $form;
	}

	/**
	 * @since 2.2.4
	 * DLM Uninstall procedure
	 */
	public function dlm_uninstall_plugin() {

		global $wpdb;
		check_ajax_referer( 'dlm_uninstall_plugin', 'security' );

		// we can't unslash an array
		$uninstall_option = isset( $_POST['options'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['options'] ) ) : false;

		// Delete options
		if ( '1' == $uninstall_option['delete_options'] ) {

			/**
			 * Remove all options that have our dlm_ prefix
			 */
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'dlm_%';" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '%_dlm_%';" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '%download-monitor%';" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '%download_monitor%';" );

			// filter for options to be added by Download Monitor's add-ons
			$options_array = apply_filters( 'dlm_uninstall_db_options', array() );

			foreach ( $options_array as $db_option ) {
				delete_option( $db_option );
			}

		}

		// Delete transients
		if ( '1' == $uninstall_option['delete_transients'] ) {

			/**
			 * Remove all DLM transients that have our dlm_ prefix
			 */
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_timeout_dlm_%';" );
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_dlm_%';" );

			// filter for transients to be added by Download Monitor's add-ons
			$transients_array = apply_filters( 'dlm_uninstall_transients', array() );

			foreach ( $transients_array as $db_transient ) {
				delete_transient( $db_transient );
			}

		}

		// Delete custom post type
		if ( '1' == $uninstall_option['delete_cpt'] ) {

			$post_types = apply_filters( 'dlm_uninstall_post_types', array( 'dlm_download', 'dlm_download_version', 'dlm_product' ) );

			$dlm_cpts = get_posts( array( 'post_type' => $post_types, 'posts_per_page' => - 1, 'fields' => 'ids' ) );

			$terms = get_terms( 'dlm_download_category', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			if ( ! empty( $terms ) ) {

				$where_terms = $wpdb->prepare(
					sprintf(
						"{$wpdb->terms}.term_id IN (%s)",
						implode( ', ', array_fill( 0, count( $terms ), '%d' ) )
					),
					$terms
				);

				$sql_terms = $wpdb->prepare( "DELETE FROM {$wpdb->terms} WHERE {$where_terms}" );

				$where_taxonomy = $wpdb->prepare(
					sprintf(
						"{$wpdb->term_taxonomy}.term_id IN (%s)",
						implode( ', ', array_fill( 0, count( $terms ), '%d' ) )
					),
					$terms
				);

				$sql_taxonomy = $wpdb->prepare( "DELETE FROM {$wpdb->term_taxonomy} WHERE {$where_taxonomy}" );
				
				$wpdb->query( $sql_terms );
				$wpdb->query( $sql_taxonomy );
			}

			if ( is_array( $dlm_cpts ) && ! empty( $dlm_cpts ) ) {

				$where = $wpdb->prepare(
					sprintf(
						"{$wpdb->posts}.ID IN (%s)",
						implode( ', ', array_fill( 0, count( $dlm_cpts ), '%d' ) )
					),
					$dlm_cpts
				);

				$sql = $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE {$where}" );

				$where_meta = $wpdb->prepare(
					sprintf(
						"{$wpdb->postmeta}.post_id IN (%s)",
						implode( ', ', array_fill( 0, count( $dlm_cpts ), '%d' ) )
					),
					$dlm_cpts
				);
				$sql_meta = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE {$where_meta}" );

				$wpdb->query( $sql );
				$wpdb->query( $sql_meta );
			}
		}

		// Delete tables set by the plugin
		if ( '1' == $uninstall_option['delete_set_tables'] ) {

			$dlm_tables = apply_filters(
				'dlm_uninstall_db_tables',
				array(
					$wpdb->prefix . 'download_log',
					$wpdb->prefix . 'dlm_session',
					$wpdb->prefix . 'dlm_order_customer',
					$wpdb->prefix . 'dlm_order_item',
					$wpdb->prefix . 'dlm_order_transaction',
					$wpdb->prefix . 'dlm_order',
					$wpdb->prefix . 'dlm_reports_log',
					$wpdb->prefix . 'dlm_downloads',
				)
			);

			if ( ! empty( $dlm_tables ) ) {

				foreach ( $dlm_tables as $table ) {

					$sql_dlm_table = $wpdb->prepare( "DROP TABLE IF EXISTS $table" );
					$wpdb->query( $sql_dlm_table );
				}
			}
		}

		do_action( 'dlm_uninstall' );

		deactivate_plugins( DLM_PLUGIN_FILE );
		wp_die();
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Uninstall object.
	 *
	 * @since 4.4.5
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Uninstall ) ) {
			self::$instance = new DLM_Uninstall();
		}

		return self::$instance;

	}

}

DLM_Uninstall::get_instance();