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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
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

		// Download Information
		add_meta_box( 'download-monitor-information', __( 'Download Information', 'download-monitor' ), array(
			$this,
			'download_information'
		), 'dlm_download', 'side', 'high' );

		// Download Options
		add_meta_box( 'download-monitor-options', __( 'Download Options', 'download-monitor' ), array(
			$this,
			'download_options'
		), 'dlm_download', 'side', 'high' );

		// Versions / Files
		add_meta_box( 'download-monitor-file', __( 'Downloadable Files/Versions', 'download-monitor' ), array(
			$this,
			'download_files'
		), 'dlm_download', 'normal', 'high' );

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
		global $post;

		$download = new DLM_Download( $post->ID );

		echo '<div class="dlm_information_panel">';

		do_action( 'dlm_information_start', $download->id );
		?>
		<p>
			<label for="dlm-info-id"><?php _e( 'ID', 'download-monitor' ); ?>
				<input type="text" id="dlm-info-id" value="<?php echo $download->id; ?>" readonly />
			</label>
		</p>
		<p>
			<label for="dlm-info-url"><?php _e( 'URL', 'download-monitor' ); ?>
				<input type="text" id="dlm-info-url" value="<?php echo $download->get_the_download_link(); ?>" readonly />
			</label>
		</p>
		<p>
			<label for="dlm-info-shortcode"><?php _e( 'Shortcode', 'download-monitor' ); ?>
				<input type="text" id="dlm-info-shortcode" value='[download id="<?php echo $download->id; ?>"]' readonly />
			</label>
		</p>
		<?php
		do_action( 'dlm_information_end', $download->id );

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
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="dlm_options_panel">';

		do_action( 'dlm_options_start', $thepostid );

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_featured" id="_featured" ' . checked( get_post_meta( $thepostid, '_featured', true ), 'yes', false ) . ' />
			<label for="_featured">' . __( 'Featured download', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . __( 'Mark this download as featured. Used by shortcodes and widgets.', 'download-monitor' ) . '</span>
		</p>';

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_members_only" id="_members_only" ' . checked( get_post_meta( $thepostid, '_members_only', true ), 'yes', false ) . ' />
			<label for="_members_only">' . __( 'Members only', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . __( 'Only logged in users will be able to access the file via a download link if this is enabled.', 'download-monitor' ) . '</span>
		</p>';

		echo '<p class="form-field form-field-checkbox">
			<input type="checkbox" name="_redirect_only" id="_redirect_only" ' . checked( get_post_meta( $thepostid, '_redirect_only', true ), 'yes', false ) . ' />
			<label for="_redirect_only">' . __( 'Redirect to file', 'download-monitor' ) . '</label>
			<span class="dlm-description">' . __( 'Don\'t force download. If the <code>dlm_uploads</code> folder is protected you may need to move your file.', 'download-monitor' ) . '</span>
		</p>';

		do_action( 'dlm_options_end', $thepostid );

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

		wp_nonce_field( 'save_meta_data', 'dlm_nonce' );
		?>
		<div class="download_monitor_files dlm-metaboxes-wrapper">

			<input type="hidden" name="dlm_post_id" id="dlm-post-id" value="<?php echo $post->ID; ?>" />
			<input type="hidden" name="dlm_post_id" id="dlm-plugin-url" value="<?php echo WP_DLM::get_plugin_url(); ?>" />
			<input type="hidden" name="dlm_post_id" id="dlm-ajax-nonce-add-file" value="<?php echo wp_create_nonce( "add-file" ); ?>" />
			<input type="hidden" name="dlm_post_id" id="dlm-ajax-nonce-remove-file" value="<?php echo wp_create_nonce( "remove-file" ); ?>" />

			<?php do_action( 'dlm_download_monitor_files_writepanel_start' ); ?>

			<p class="toolbar">
				<a href="#" class="button plus add_file"><?php _e( 'Add file', 'download-monitor' ); ?></a>
				<a href="#" class="close_all"><?php _e( 'Close all', 'download-monitor' ); ?></a>
				<a href="#" class="expand_all"><?php _e( 'Expand all', 'download-monitor' ); ?></a>
			</p>

			<div class="dlm-metaboxes downloadable_files">
				<?php
				$i     = - 1;
				$files = get_posts( 'post_parent=' . $post->ID . '&post_type=dlm_download_version&orderby=menu_order&order=ASC&post_status=any&numberposts=-1' );

				if ( $files ) {
					foreach ( $files as $file ) {

						$i ++;
						$file_id             = $file->ID;
						$file_version        = ( $file_version = get_post_meta( $file->ID, '_version', true ) ) ? $file_version : '';
						$file_post_date      = $file->post_date;
						$file_download_count = absint( get_post_meta( $file->ID, '_download_count', true ) );
						$file_urls           = get_post_meta( $file->ID, '_files', true );

						if ( is_string( $file_urls ) ) {
							$file_urls = array_filter( (array) json_decode( $file_urls ) );
						} elseif ( is_array( $file_urls ) ) {
							$file_urls = array_filter( $file_urls );
						} else {
							$file_urls = array();
						}

						include( 'html-downloadable-file-version.php' );
					}
				}
				?>
			</div>

			<?php do_action( 'dlm_download_monitor_files_writepanel_end' ); ?>

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
			'quicktags'     => array( 'buttons' => 'em,strong,link' ),
			'textarea_name' => 'excerpt',
			'quicktags'     => true,
			'tinymce'       => true,
			'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:200px; width:100%;}</style>'
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
		global $wpdb;

		// Update options
		$_featured      = ( isset( $_POST['_featured'] ) ) ? 'yes' : 'no';
		$_members_only  = ( isset( $_POST['_members_only'] ) ) ? 'yes' : 'no';
		$_redirect_only = ( isset( $_POST['_redirect_only'] ) ) ? 'yes' : 'no';

		update_post_meta( $post_id, '_featured', $_featured );
		update_post_meta( $post_id, '_members_only', $_members_only );
		update_post_meta( $post_id, '_redirect_only', $_redirect_only );

		$total_download_count = 0;

		// Process files
		if ( isset( $_POST['downloadable_file_id'] ) ) {

			$downloadable_file_id             = $_POST['downloadable_file_id'];
			$downloadable_file_menu_order     = $_POST['downloadable_file_menu_order'];
			$downloadable_file_version        = $_POST['downloadable_file_version'];
			$downloadable_file_urls           = $_POST['downloadable_file_urls'];
			$downloadable_file_date           = $_POST['downloadable_file_date'];
			$downloadable_file_date_hour      = $_POST['downloadable_file_date_hour'];
			$downloadable_file_date_minute    = $_POST['downloadable_file_date_minute'];
			$downloadable_file_download_count = $_POST['downloadable_file_download_count'];

			for ( $i = 0; $i <= max( array_keys( $downloadable_file_id ) ); $i ++ ) {

				if ( ! isset( $downloadable_file_id[ $i ] ) ) {
					continue;
				}

				$file_id             = absint( $downloadable_file_id[ $i ] );
				$file_menu_order     = absint( $downloadable_file_menu_order[ $i ] );
				$file_version        = strtolower( sanitize_text_field( $downloadable_file_version[ $i ] ) );
				$file_date_hour      = absint( $downloadable_file_date_hour[ $i ] );
				$file_date_minute    = absint( $downloadable_file_date_minute[ $i ] );
				$file_date           = sanitize_text_field( $downloadable_file_date[ $i ] );
				$file_download_count = sanitize_text_field( $downloadable_file_download_count[ $i ] );
				$files               = array_filter( array_map( 'trim', explode( "\n", $downloadable_file_urls[ $i ] ) ) );

				if ( ! $file_id ) {
					continue;
				}

				// Generate a useful post title
				$file_post_title = 'Download #' . $post_id . ' File Version';

				// Generate date
				if ( empty( $file_date ) ) {
					$date = current_time( 'timestamp' );
				} else {
					$date = strtotime( $file_date . ' ' . $file_date_hour . ':' . $file_date_minute . ':00' );
				}

				// Update
				$wpdb->update( $wpdb->posts, array(
					'post_status'   => 'publish',
					'post_title'    => $file_post_title,
					'menu_order'    => $file_menu_order,
					'post_date'     => date( 'Y-m-d H:i:s', $date ),
					'post_date_gmt' => date( 'Y-m-d H:i:s', $date ),
				), array( 'ID' => $file_id ) );

				// File Manager
				$file_manager = new DLM_File_Manager();

				// Update post meta
				update_post_meta( $file_id, '_version', $file_version );
				update_post_meta( $file_id, '_files', $file_manager->json_encode_files( $files ) );

				$filesize       = - 1;
				$main_file_path = current( $files );

				if ( $main_file_path ) {
					$filesize = $file_manager->get_file_size( $main_file_path );
					$hashes   = $file_manager->get_file_hashes( $main_file_path );
					update_post_meta( $file_id, '_filesize', $filesize );
					update_post_meta( $file_id, '_md5', $hashes['md5'] );
					update_post_meta( $file_id, '_sha1', $hashes['sha1'] );
					update_post_meta( $file_id, '_crc32', $hashes['crc32'] );
				} else {
					update_post_meta( $file_id, '_filesize', $filesize );
					update_post_meta( $file_id, '_md5', '' );
					update_post_meta( $file_id, '_sha1', '' );
					update_post_meta( $file_id, '_crc32', '' );
				}

				if ( $file_download_count !== '' ) {
					update_post_meta( $file_id, '_download_count', absint( $file_download_count ) );
					$total_download_count += absint( $file_download_count );
				} else {
					$total_download_count += absint( get_post_meta( $file_id, '_download_count', true ) );
				}

				do_action( 'dlm_save_downloadable_file', $file_id, $i );
			}
		}

		// Sync download_count
		update_post_meta( $post_id, '_download_count', $total_download_count );

		// Delete transients
		delete_transient( 'dlm_file_version_ids_' . $post_id );

		do_action( 'dlm_save_metabox', $post_id, $post );
	}
}
