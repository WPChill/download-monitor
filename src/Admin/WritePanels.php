<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin class.
 */
class DLM_Admin_Writepanels {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 15 );
		add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'dlm_save_meta_boxes', array( $this, 'save_meta_boxes' ), 1, 2 );
	}

	/**
	 * add_meta_boxes function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_meta_boxes() {

		// We remove the Publish metabox and add to our queue
		remove_meta_box( 'submitdiv', 'dlm_download', 'side' );

		$meta_boxes = apply_filters( 'dlm_download_metaboxes', array(
						array(
								'id'       => 'submitdiv',
								'title'    => __( 'Publish' ),
								'callback' => 'post_submit_meta_box',
								'screen'   => 'dlm_download',
								'context'  => 'side',
								'priority' => 1
						),
						array(
								'id'       => 'download-monitor-information',
								'title'    => __( 'Download Information', 'download-monitor' ),
								'callback' => array(
										$this,
										'download_information'
								),
								'screen'   => 'dlm_download',
								'context'  => 'side',
								'priority' => 5
						),
						array(
								'id'       => 'download-monitor-options',
								'title'    => __( 'Download Options', 'download-monitor' ),
								'callback' => array(
										$this,
										'download_options'
								),
								'screen'   => 'dlm_download',
								'context'  => 'side',
								'priority' => 10
						),
						array(
								'id'       => 'download-monitor-file',
								'title'    => __( 'Downloadable Files/Versions', 'download-monitor' ),
								'callback' => array(
										$this,
										'download_files'
								),
								'screen'   => 'dlm_download',
								'context'  => 'normal',
								'priority' => 20
						),
				)
		);

		uasort( $meta_boxes, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );

		foreach($meta_boxes as $metabox){
			// Priority is left out as we prioritise based on our sorting function
			add_meta_box( $metabox['id'], $metabox['title'], $metabox['callback'], $metabox['screen'], $metabox['context'],'high' );
		}

		// Excerpt
		if ( function_exists( 'wp_editor' ) ) {
			remove_meta_box( 'postexcerpt', 'dlm_download', 'normal' );
			add_meta_box( 'postexcerpt', __( 'Short Description', 'download-monitor' ), array(
				$this,
				'short_description'
			), 'dlm_download', 'normal', 'high' );
		}
	}

	/**
	 * download_information function.
	 *
	 * @access public
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function download_information( $post ) {

		echo '<div class="dlm_information_panel">';

		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );

			do_action( 'dlm_information_start', $download->get_id(), $download );
			?>
            <p>
                <label for="dlm-info-id"><?php echo esc_html__( 'ID', 'download-monitor' ); ?>
                    <input type="text" id="dlm-info-id" value="<?php echo esc_attr( $download->get_id() ); ?>" readonly
                           onfocus="this.select()"/>
                </label>
            </p>
            <p>
                <label for="dlm-info-url"><?php echo esc_html__( 'URL', 'download-monitor' ); ?>
                    <input type="text" id="dlm-info-url" value="<?php echo esc_url( $download->get_the_download_link() ); ?>"
                           readonly onfocus="this.select()"/>
                </label>
            </p>
            <p>
                <label for="dlm-info-shortcode"><?php echo esc_html__( 'Shortcode', 'download-monitor' ); ?>
                    <input type="text" id="dlm-info-shortcode"
                           value='[download id="<?php echo esc_url( $download->get_id() ); ?>"]' readonly onfocus="this.select()"/>
                </label>
            </p>
			<?php
			do_action( 'dlm_information_end', esc_url( $download->get_id() ), $download );
		} catch ( Exception $e ) {
			echo "<p>" . esc_html__( "No download information for new downloads.", 'download-monitor' ) . "</p>";
		}

		echo '</div>';
	}

	/**
	 * download_options function.
	 *
	 * @access public
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function download_options( $post ) {

		try {
			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post->ID );
		} catch ( Exception $e ) {
			$download = new DLM_Download();
		}

		echo '<div class="dlm_options_panel">';

		do_action( 'dlm_options_start', $download->get_id(), $download );

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_featured" id="_featured" ' . checked( true, $download->is_featured(), false ) . ' />
			<label for="_featured">' . esc_html__( 'Featured download', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . esc_html__( 'Mark this download as featured. Used by shortcodes and widgets.', 'download-monitor' ) . '</span>
		</p>';

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_members_only" id="_members_only" ' . checked( true, $download->is_members_only(), false ) . ' />
			<label for="_members_only">' . esc_html__( 'Members only', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . esc_html__( 'Only logged in users will be able to access the file via a download link if this is enabled.', 'download-monitor' ) . '</span>
		</p>';

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_redirect_only" id="_redirect_only" ' . checked( true, $download->is_redirect_only(), false ) . ' />
			<label for="_redirect_only">' . esc_html__( 'Redirect to file', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . esc_html__( 'Don\'t force download. If the <code>dlm_uploads</code> folder is protected you may need to move your file.', 'download-monitor' ) . '</span>
		</p>';

		do_action( 'dlm_options_end', $download->get_id(), $download );

		echo '</div>';
	}

	/**
	 * download_files function.
	 *
	 * @access public
	 * @return void
	 */
	public function download_files() {
		global $post;

		/** @var DLM_Download $download */
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( array(
			'p'           => absint( $post->ID ),
			'post_status' => array( 'any', 'trash' )
		), 1 );

		if ( count( $downloads ) > 0 ) {
			$download = $downloads[0];
		} else {
			$download = new DLM_Download();
		}

		wp_nonce_field( 'save_meta_data', 'dlm_nonce' );
		?>
        <div class="download_monitor_files dlm-metaboxes-wrapper">

            <input type="hidden" name="dlm_post_id" id="dlm-post-id" value="<?php echo esc_attr( $post->ID ); ?>"/>
            <input type="hidden" name="dlm_post_id" id="dlm-plugin-url"
                   value="<?php echo esc_attr( download_monitor()->get_plugin_url() ); ?>"/>
            <input type="hidden" name="dlm_post_id" id="dlm-ajax-nonce-add-file"
                   value="<?php echo wp_create_nonce( "add-file" ); ?>"/>
            <input type="hidden" name="dlm_post_id" id="dlm-ajax-nonce-remove-file"
                   value="<?php echo wp_create_nonce( "remove-file" ); ?>"/>

			<?php do_action( 'dlm_download_monitor_files_writepanel_start', $download ); ?>

            <p class="toolbar">
                <a href="#" class="button plus add_file"><?php echo esc_html__( 'Add file', 'download-monitor' ); ?></a>
                <a href="#" class="close_all"><?php echo esc_html__( 'Close all', 'download-monitor' ); ?></a>
                <a href="#" class="expand_all"><?php echo esc_html__( 'Expand all', 'download-monitor' ); ?></a>
            </p>

            <div class="dlm-metaboxes downloadable_files">
				<?php
				$i        = - 1;
				$versions = $download->get_versions();

				if ( $versions ) {

					/** @var DLM_Download_Version $version */
					foreach ( $versions as $version ) {

						$i ++;

						download_monitor()->service( 'view_manager' )->display( 'meta-box/version', array(
							'version_increment'   => $i,
							'file_id'             => $version->get_id(),
							'file_version'        => $version->get_version(),
							'file_post_date'      => $version->get_date(),
							'file_download_count' => $version->get_download_count(),
							'file_urls'           => $version->get_mirrors(),
							'version'             => $version,
						) );

					}
				}
				?>
            </div>

			<?php do_action( 'dlm_download_monitor_files_writepanel_end', $download ); ?>

        </div>
		<?php
	}

	/**
	 * short_description function.
	 *
	 * @access public
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function short_description( $post ) {
		$settings = array(
			'textarea_name' => 'excerpt',
			'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:200px; width:100%;}</style>',
			'teeny'         => true,
			'dfw'           => false,
			'tinymce'       => true,
			'quicktags'     => false,
			'wpautop'             => false,
			'media_buttons'       => false,
		);

		wp_editor( htmlspecialchars_decode( $post->post_excerpt ), 'excerpt', $settings );
	}

	/**
	 * save_post function.
	 *
	 * @access public
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		if ( empty( $_POST['dlm_nonce'] ) || ! wp_verify_nonce( $_POST['dlm_nonce'], 'save_meta_data' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type != 'dlm_download' ) {
			return;
		}

		// unset nonce because it's only valid of 1 post
		unset( $_POST['dlm_nonce'] );

		do_action( 'dlm_save_meta_boxes', $post_id, $post );
	}

	/**
	 * save function.
	 *
	 * @access public
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {

		/**
		 * Fetch old download object
		 * There are certain props we don't need to manually persist here because WP does this automatically for us.
		 * These props are:
		 * - Download Title
		 * - Download Status
		 * - Download Author
		 * - Download Description & Excerpt
		 * - Download Categories
		 * - Download Tags
		 *
		 */
		/** @var DLM_Download $download */
		try {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post_id );
		} catch ( Exception $e ) {
			// download not found, no point in continuing
			return;
		}

		// set the 'Download Options'
		$download->set_featured( ( isset( $_POST['_featured'] ) ) );
		$download->set_members_only( ( isset( $_POST['_members_only'] ) ) );
		$download->set_redirect_only( ( isset( $_POST['_redirect_only'] ) ) );

		$total_download_count = 0;

		// Process files
		if ( isset( $_POST['downloadable_file_id'] ) ) {

			// gather post data
			$downloadable_file_id             = sanitize_text_field( $_POST['downloadable_file_id'] );
			$downloadable_file_menu_order     = sanitize_text_field( $_POST['downloadable_file_menu_order'] );
			$downloadable_file_version        = sanitize_text_field( $_POST['downloadable_file_version'] );
			$downloadable_file_urls           = esc_url_raw( $_POST['downloadable_file_urls'] );
			$downloadable_file_date           = sanitize_text_field( $_POST['downloadable_file_date'] );
			$downloadable_file_date_hour      = sanitize_text_field( $_POST['downloadable_file_date_hour'] );
			$downloadable_file_date_minute    = sanitize_text_field( $_POST['downloadable_file_date_minute'] );
			$downloadable_file_download_count = sanitize_text_field( $_POST['downloadable_file_download_count'] );

			// loop
			for ( $i = 0; $i <= max( array_keys( $downloadable_file_id ) ); $i ++ ) {

				// file id must be set in post data
				if ( ! isset( $downloadable_file_id[ $i ] ) ) {
					continue;
				}

				// sanatize post data
				$file_id             = absint( $downloadable_file_id[ $i ] );
				$file_menu_order     = absint( $downloadable_file_menu_order[ $i ] );
				$file_version        = strtolower( sanitize_text_field( $downloadable_file_version[ $i ] ) );
				$file_date_hour      = absint( $downloadable_file_date_hour[ $i ] );
				$file_date_minute    = absint( $downloadable_file_date_minute[ $i ] );
				$file_date           = sanitize_text_field( $downloadable_file_date[ $i ] );
				$file_download_count = sanitize_text_field( $downloadable_file_download_count[ $i ] );
				$files               = array_filter( array_map( 'trim', explode( "\n", $downloadable_file_urls[ $i ] ) ) );

				// only continue if there's a file_id
				if ( ! $file_id ) {
					continue;
				}

				// format correct file date
				if ( empty( $file_date ) ) {
					$file_date_obj = new DateTime( current_time( 'mysql' ) );
				} else {
					$file_date_obj = new DateTime( $file_date . ' ' . $file_date_hour . ':' . $file_date_minute . ':00' );
				}

				try {
					// create version object
					/** @var DLM_Download_Version $version */
					$version = download_monitor()->service( 'version_repository' )->retrieve_single( $file_id );

					// set post data in version object
					$version->set_author( get_current_user_id() );
					$version->set_menu_order( $file_menu_order );
					$version->set_version( $file_version );
					$version->set_date( $file_date_obj );
					$version->set_mirrors( $files );

					// only set download count if is posted
					if ( '' !== $file_download_count ) {
						$version->set_download_count( $file_download_count );
					}

					// persist version
					download_monitor()->service( 'version_repository' )->persist( $version );

					// add version download count to total download count
					$total_download_count += absint( $version->get_download_count() );
				} catch ( Exception $e ) {

				}

				// do dlm_save_downloadable_file action
				do_action( 'dlm_save_downloadable_file', $file_id, $i );
			}
		}

		// sync download_count
		$download->set_download_count( $total_download_count );

		// persist download
		download_monitor()->service( 'download_repository' )->persist( $download );

		// do dlm_save_metabox action
		do_action( 'dlm_save_metabox', $post_id, $post, $download );
	}
}
