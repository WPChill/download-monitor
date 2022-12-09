<?php
/**
 * Download Monitor - Media Library, class that handles the funcitonality from Media Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Media_Library {

	/**
	 * Holds the class object.
	 *
	 * @since 4.7.2
	 *
	 * @var object
	 *//**/
	public static $instance;

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Media_Library object.
	 * @since 4.7.2
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Media_Library ) ) {
			self::$instance = new DLM_Media_Library();
		}

		return self::$instance;

	}

	/**
	 * Class constructor
	 */
	private function __construct() {
		// filter attachment thumbnails in media library for files in dlm_uploads
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'filter_thumbnails_protected_files_grid' ), 10, 1 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'filter_thumbnails_protected_files_list' ), 10, 1 );
		// Do not make sub-sizes for images uploaded in dlm_uploads
		add_filter( 'file_is_displayable_image', array( $this, 'no_image_subsizes' ), 15, 2 );
		add_filter( 'ajax_query_attachments_args', array( $this, 'no_media_library_display' ), 15 );
		// Add a Media Library filter to list view so that we can filter out dlm_uploads
		add_action( 'restrict_manage_posts', array( $this, 'add_dlm_uploads_filter' ), 15, 2 );
		// Set query vars for dlm_uploads filter
		add_action( 'pre_get_posts', array( $this, 'media_library_filter' ), 15 );
		// Add DLM Uploads file as a mime type
		add_filter( 'post_mime_types', array( $this, 'add_mime_types' ), 15, 1 );
		// Actions done to Media Library files in order to create Downloads and protect files
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_protect_button' ), 999, 2 );
		add_action( 'wp_ajax_dlm_protect_file', array( $this, 'protect_file' ), 15 );
		add_action( 'wp_ajax_dlm_unprotect_file', array( $this, 'unprotect_file' ), 15 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'add_visual_indicator' ), 10, 2 );
		add_filter( 'manage_upload_columns', array( $this, 'dlm_ml_column' ), 15, 1 );
		add_action( 'manage_media_custom_column', array( $this, 'manage_dlm_ml_column' ), 0, 2 );
		add_filter( 'bulk_actions-upload', array( $this, 'dlm_ml_bulk_actions' ), 15 );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'dlm_ml_handle_bulk' ), 15, 3 );
		add_filter( 'admin_init', array( $this, 'dlm_ml_do_bulk' ), 15 );
		// End Actions to Media Library files
	}

	/**
	 * filter attachment thumbnails in media library grid view for files in dlm_uploads
	 *
	 * @param array $response
	 *
	 * @return array
	 */
	public function filter_thumbnails_protected_files_grid( $response ) {
		if ( apply_filters( 'dlm_filter_thumbnails_protected_files', true ) ) {
			$upload_dir = wp_upload_dir();

			if ( strpos( $response['url'], $upload_dir['baseurl'] . '/dlm_uploads' ) !== false ) {
				if ( ! empty( $response['sizes'] ) ) {
					$dlm_protected_thumb = download_monitor()->get_plugin_url() . '/assets/images/protected-file-thumbnail.png';
					foreach ( $response['sizes'] as $rs_key => $rs_val ) {
						$rs_val['url']                = $dlm_protected_thumb;
						$response['sizes'][ $rs_key ] = $rs_val;
					}
				}
			}
		}

		return $response;
	}

	/**
	 * filter attachment thumbnails in media library list view for files in dlm_uploads
	 *
	 * @param bool|array $image
	 *
	 * @return bool|array
	 */
	public function filter_thumbnails_protected_files_list( $image ) {
		if ( apply_filters( 'dlm_filter_thumbnails_protected_files', true ) ) {
			if ( $image ) {

				$upload_dir = wp_upload_dir();

				if ( strpos( $image[0], $upload_dir['baseurl'] . '/dlm_uploads' ) !== false ) {
					$image[0] = $dlm_protected_thumb = download_monitor()->get_plugin_url() . '/assets/images/protected-file-thumbnail.png';
					$image[1] = 60;
					$image[2] = 60;
				}
			}

		}

		return $image;
	}

	/**
	 * Don't display or create sub-sizes for DLM uploads
	 *
	 * @param $result
	 * @param $path
	 *
	 * @return false|mixed
	 * @since 4.6.0
	 */
	public function no_image_subsizes( $result, $path ) {

		$upload_dir = wp_upload_dir();

		if ( strpos( $path, $upload_dir['basedir'] . '/dlm_uploads' ) !== false ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Don't display DLM Uploads in Media Library
	 *
	 * @param array $query Query parameters.
	 *
	 * @return array
	 * @since 4.6.0
	 */
	public function no_media_library_display( $query ) {

		// Check for the added temporary mime_type so that we can filter the Media Library contents
		// and show only the files that are in dlm_uploads ( aka protected )
		if ( isset( $query['post_mime_type'] ) && 'dlm_uploads_files' === $query['post_mime_type'] ) {
			unset( $query['post_mime_type'] );
			$query['meta_key']     = '_wp_attached_file';
			$query['meta_query'][] = array(
				'key'     => '_wp_attached_file',
				'compare' => 'LIKE',
				'value'   => 'dlm_uploads'
			);
		}

		return $query;
	}

	/**
	 * Add Media Library filters for DLM Downloads
	 *
	 * @param $screen
	 * @param $which
	 *
	 * @return void
	 * @since 4.6.4
	 */
	public function add_dlm_uploads_filter( $screen, $which ) {
		// Add a filter to the Media Library page so that we can filter regular uploads and Download Monitor's uploads
		if ( $screen === 'attachment' ) {
			$views = apply_filters( 'dlm_media_views', array(
				'uploads_folder'     => __( 'All files', 'download-monitor' ),
				'dlm_uploads_folder' => __( 'Download Monitor', 'download-monitor' )
			) );

			$applied_filter = isset( $_GET['dlm_upload_folder_type'] ) ? sanitize_text_field( wp_unslash( $_GET['dlm_upload_folder_type'] ) ) : 'all';
			?>
			<select name="dlm_upload_folder_type">
				<?php
				foreach ( $views as $key => $view ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $applied_filter ) . '>' . esc_html( $view ) . '</option>';
				}
				?>
			</select>
			<?php
		}
	}

	/**
	 * Filter the media library query to wether show DLM uploads or not
	 *
	 * @param $query
	 *
	 * @return void
	 * @since 4.6.4
	 */
	public function media_library_filter( $query ) {

		if ( ! is_admin() || false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin/upload.php' ) ) {
			return;
		}

		// If users views all media then we don't need to do anything
		if ( ! isset( $_GET['dlm_upload_folder_type'] ) || 'dlm_uploads_folder' !== sanitize_text_field( wp_unslash( $_GET['dlm_upload_folder_type'] ) ) ) {
			return;
		}

		// If user views the DLM Uploads folder then we need to show DLM uploads only.
		// Set the meta query for the corresponding request.
		$query->set( 'meta_key', '_wp_attached_file' );
		$query->set( 'meta_query', array(
			'key'     => '_wp_attached_file',
			'compare' => 'LIKE',
			'value'   => 'dlm_uploads'
		) );
	}

	/**
	 * Add temporary dlm_uploads_files mime type to help us filter the media library
	 *
	 * @param $mimes
	 *
	 * @return mixed
	 * @since 4.6.4
	 */
	public function add_mime_types( $mimes ) {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return $mimes;
		}

		$screen = get_current_screen();
		// If we are not on the Media Library page or editing the Download then we don't need to add the mime types.
		if ( null === $screen || ! is_admin() || ( 'upload' !== $screen->base && 'attachment' !== $screen->post_type && 'dlm_download' !== $screen->post_type ) ) {
			return $mimes;
		}

		// Create temp mime_type that will only be available on Media Library page and edit Download page.
		// We need this to proper filter the Media Library contents and show only DLM uploads or regular uploads.
		$mimes['dlm_uploads_files'] = array(
			'Download Monitor Files',
			'Manage DLM Files',
			array(
				'dlm_uploads',
				'else',
				'singular' => 'DLM File',
				'plural'   => 'DLM Files',
				'content'  => null,
				'domain'   => null
			)
		);

		return $mimes;
	}

	/**
	 * Add a Protect Download button in the Attachment details view
	 *
	 * @param $fields
	 * @param $post
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function add_protect_button( $fields, $post ) {
		// Let's check if this is not already set.
		if ( ! isset( $fields['dlm_protect_file'] ) ) {

			$button_text = __( 'Protect', 'download-monitor' );
			$action      = 'protect_file';
			$text        = esc_html__( 'Creates a Download based on this file and moves the file to Download Monitor\'s protected folder. Also replaces the attachment\'s URL with the download link.', 'download-monitor' );
			$disabled    = false;
			if ( '1' === get_post_meta( $post->ID, 'dlm_protected_file', true ) ) {
				$button_text = __( 'Unprotect', 'download-monitor' );
				$action      = 'unprotect_file';
				$text        = esc_html__( 'Moves the file from Download Monitor\'s protected directory to the uploads directory. Also places back the original URL for this attachment.', 'download-monitor' );
			} elseif ( false !== strpos( $post->guid, 'dlm_uploads' ) ) {
				$button_text = __( 'Default file', 'download-monitor' );
				$action      = '';
				$text        = esc_html__( 'No action is needed.', 'download-monitor' );
				$disabled    = true;
			}

			$html = '<button id="dlm-protect-file" class="button button-primary" data-action="' . esc_attr( $action ) . '" data-post_id="' . absint( $post->ID ) . '" data-nonce="' . wp_create_nonce( 'dlm_protect_file' ) . '" data-user_id="' . get_current_user_id() . '" data-file="' . esc_url( wp_get_attachment_url( $post->ID ) ) . '" ' . ( $disabled ? 'disabled="true"' : '' ) . '>' . esc_html( $button_text ) . '</button><p class="description">' . esc_html( $text ) . '</p>';

			// Add our button
			$fields['dlm_protect_file'] = array(
				'label' => __( 'DLM protect file', 'download-monitor' ),
				'input' => 'html',
				'html'  => $html,

			);
		}

		return $fields;
	}

	/**
	 * Function used to create new Downloads directly from the Media Library
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function protect_file() {
		// Check if nonce is transmitted
		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( 'No nonce' );
		}
		// Check if nonce is correct
		check_ajax_referer( 'dlm_protect_file', '_ajax_nonce' );
		// Get the data so we can create the download
		$file = $_POST;
		// Move the file
		download_monitor()->service( 'file_manager' )->move_file_to_dlm_uploads( $file['attachment_id'] );
		// Create the download or update existing one
		$current_url = $this->create_download( $file );
		// Send the response
		$data = array(
			'url'  => $current_url,
			'text' => esc_html__( 'File protected. Download created', 'download-monitor' )
		);
		wp_send_json_success( $data );
	}

	/**
	 * Function used to unprotect Media Library file
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function unprotect_file() {
		// Check if nonce is transmitted
		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( 'No nonce' );
		}
		// Check if nonce is correct
		check_ajax_referer( 'dlm_protect_file', '_ajax_nonce' );
		// Get the data so we can create the download
		$file = $_POST;
		// For the moment we don't know the version id or if it exists
		$version_id = false;
		// Now make the move to Download Monitor's protected folder dlm_uploads
		download_monitor()->service( 'file_manager' )->move_file_back( $file['attachment_id'] );
		// Get the currently protected download so that we can update its files
		$known_download = get_post_meta( $file['attachment_id'], 'dlm_download', true );
		if ( ! empty( $known_download ) ) {
			$version_id = json_decode( $known_download, true )['version_id'];
		}
		// Delete set metas when the file was protected.
		delete_post_meta( $file['attachment_id'], 'dlm_protected_file' );
		// Get current URL so we can update the Version files.
		$current_url = wp_get_attachment_url( $file['attachment_id'] );
		// Get secure path and update the file path in the Download
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( $current_url, 'relative' );

		if ( $version_id ) {
			// Update the Version meta.
			update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
		}

		// Send the response
		$data = array(
			'url'  => $current_url,
			'text' => esc_html__( 'File unprotected.', 'download-monitor' )
		);

		wp_send_json_success( $data );
	}

	/**
	 * Create new Download and its version
	 *
	 * @param $file
	 *
	 * @return string URL of the new Download
	 * @since 4.7.2
	 */
	public function create_download( $file ) {

		// Get new path
		list( $file_path, $remote_file, $restriction ) = download_monitor()->service( 'file_manager' )->get_secure_path( wp_get_attachment_url( $file['attachment_id'] ), 'relative' );

		// Check if the file has been previously protected
		$known_download = get_post_meta( $file['attachment_id'], 'dlm_download', true );
		// If not, protect and add the corresponding meta, Download & Version
		if ( empty( $known_download ) ) {
			$title          = get_the_title( $file['attachment_id'] );
			$download_title = ! empty( $title ) ? $title : DLM_Utils::basename( $file['file'] );
			// Create the Download object.
			$download = array(
				'post_title'   => $download_title,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => absint( $file['user_id'] ),
				'post_type'    => 'dlm_download'
			);
			// Insert the Download. We need its ID to create the Download Version.
			$download_id = wp_insert_post( $download );
			// Create the Version object
			$version = array(
				'post_title'   => 'Download #' . $download_title . 'File Version',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => absint( $file['user_id'] ),
				'post_type'    => 'dlm_download_version',
				'post_parent'  => $download_id
			);
			// Insert the Version.
			$version_id = wp_insert_post( $version );
			// Update the Version meta.
			update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
			// Set a meta option to know what Download is using this file and what Version.
			$attachment_meta = json_encode(
				array(
					'download_id' => $download_id,
					'version_id'  => $version_id
				)
			);
			update_post_meta( $file['attachment_id'], 'dlm_download', $attachment_meta );
		} else { // Use the current Download and Version
			$download_id = json_decode( $known_download, true )['download_id'];
			$version_id  = json_decode( $known_download, true )['version_id'];
		}

		// Update the Version meta.
		update_post_meta( $version_id, '_files', download_monitor()->service( 'file_manager' )->json_encode_files( $file_path ) );
		update_post_meta( $version_id, '_version', '' );
		$transient_name   = 'dlm_file_version_ids_' . $download_id;
		$transient_name_2 = 'dlm_file_version_ids_' . $version_id;
		// Set a meta option to know that this file is protected by Download Monitor.
		update_post_meta( $file['attachment_id'], 'dlm_protected_file', '1' );
		// Update the file's URL with the Download Monitor's URL.
		// First we need to retrieve the newly created Download
		try {
			/** @var DLM_Download $download */
			$download   = download_monitor()->service( 'download_repository' )->retrieve_single( absint( $download_id ) );
			$attachment = array(
				'ID' => $file['attachment_id'],
			);
			wp_update_post( $attachment );
			// Delete transient as it won't be able to find the versions if not.
			delete_transient( $transient_name );
			delete_transient( $transient_name_2 );

			$url = $download->get_the_download_link();
			// Set version also to the URL as the user might add another version to that Download that could download another file
			if ( $version_id ) {
				$url = add_query_arg( 'v', $version_id, $url );
			}

			return $url;

		} catch ( Exception $exception ) {
			// no download found, don't do anything.
		}

		return false;
	}

	/**
	 * Displays a visual indicator for Media Library files that are protected by DLM
	 *
	 * @param $response
	 * @param $attachment
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function add_visual_indicator( $response, $attachment ) {

		if ( '1' === get_post_meta( $attachment->ID, 'dlm_protected_file', true ) ) {
			$response['dlmCustomClass'] = 'dlm-ml-protected-file';
		} elseif ( false !== strpos( wp_get_attachment_url( $attachment->ID ), 'dlm_uploads' ) ) {
			$response['dlmCustomClass'] = 'dlm-ml-file';
		}

		return $response;
	}

	/**
	 * Add a new column to the Media Library
	 *
	 * @param $columns
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function dlm_ml_column( $columns ) {
		$columns['dlm_protection'] = __( 'Download Monitor', 'download-monitor' );

		return $columns;
	}

	/**
	 * Manage the new column in the Media Library
	 *
	 * @param $column_name
	 * @param $id
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function manage_dlm_ml_column( $column_name, $id ) {

		if ( $column_name == 'dlm_protection' ) {
			$url = wp_get_attachment_url( $id );
			if ( '1' === get_post_meta( $id, 'dlm_protected_file', true ) ) {
				?>
				<img
					src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI4IDE0QzI4IDYuMjY4MDEgMjEuNzMyIDAgMTQgMEM2LjI2ODAxIDAgMCA2LjI2ODAxIDAgMTRDMCAyMS43MzIgNi4yNjgwMSAyOCAxNCAyOEMyMS43MzIgMjggMjggMjEuNzMyIDI4IDE0WiIgZmlsbD0idXJsKCNwYWludDBfbGluZWFyXzM2XzM5KSIvPgo8cGF0aCBkPSJNMTcuNjE1NCAxMi41NjI1SDE3LjM3NVY5LjUxNTYyQzE3LjM3NSA4LjU4MzIyIDE2Ljk5NTEgNy42ODkwMSAxNi4zMTg5IDcuMDI5N0MxNS42NDI3IDYuMzcwNCAxNC43MjU1IDYgMTMuNzY5MiA2QzEyLjgxMjkgNiAxMS44OTU4IDYuMzcwNCAxMS4yMTk2IDcuMDI5N0MxMC41NDM0IDcuNjg5MDEgMTAuMTYzNSA4LjU4MzIyIDEwLjE2MzUgOS41MTU2MlYxMi41NjI1SDkuOTIzMDhDOS40MTMwNSAxMi41NjI1IDguOTIzOSAxMi43NiA4LjU2MzI2IDEzLjExMTdDOC4yMDI2MSAxMy40NjMzIDggMTMuOTQwMiA4IDE0LjQzNzVWMTkuMTI1QzggMTkuNjIyMyA4LjIwMjYxIDIwLjA5OTIgOC41NjMyNiAyMC40NTA4QzguOTIzOSAyMC44MDI1IDkuNDEzMDUgMjEgOS45MjMwOCAyMUgxNy42MTU0QzE4LjEyNTQgMjEgMTguNjE0NiAyMC44MDI1IDE4Ljk3NTIgMjAuNDUwOEMxOS4zMzU5IDIwLjA5OTIgMTkuNTM4NSAxOS42MjIzIDE5LjUzODUgMTkuMTI1VjE0LjQzNzVDMTkuNTM4NSAxMy45NDAyIDE5LjMzNTkgMTMuNDYzMyAxOC45NzUyIDEzLjExMTdDMTguNjE0NiAxMi43NiAxOC4xMjU0IDEyLjU2MjUgMTcuNjE1NCAxMi41NjI1VjEyLjU2MjVaTTExLjEyNSA5LjUxNTYyQzExLjEyNSA4LjgzMTg2IDExLjQwMzYgOC4xNzYxMSAxMS44OTk1IDcuNjkyNjJDMTIuMzk1NCA3LjIwOTEyIDEzLjA2NzkgNi45Mzc1IDEzLjc2OTIgNi45Mzc1QzE0LjQ3MDUgNi45Mzc1IDE1LjE0MzEgNy4yMDkxMiAxNS42MzkgNy42OTI2MkMxNi4xMzQ5IDguMTc2MTEgMTYuNDEzNSA4LjgzMTg2IDE2LjQxMzUgOS41MTU2MlYxMi41NjI1SDExLjEyNVY5LjUxNTYyWk0xNC4yNSAxNy45NTMxQzE0LjI1IDE4LjA3NzQgMTQuMTk5MyAxOC4xOTY3IDE0LjEwOTIgMTguMjg0NkMxNC4wMTkgMTguMzcyNSAxMy44OTY3IDE4LjQyMTkgMTMuNzY5MiAxOC40MjE5QzEzLjY0MTcgMTguNDIxOSAxMy41MTk0IDE4LjM3MjUgMTMuNDI5MyAxOC4yODQ2QzEzLjMzOTEgMTguMTk2NyAxMy4yODg1IDE4LjA3NzQgMTMuMjg4NSAxNy45NTMxVjE1LjYwOTRDMTMuMjg4NSAxNS40ODUxIDEzLjMzOTEgMTUuMzY1OCAxMy40MjkzIDE1LjI3NzlDMTMuNTE5NCAxNS4xOSAxMy42NDE3IDE1LjE0MDYgMTMuNzY5MiAxNS4xNDA2QzEzLjg5NjcgMTUuMTQwNiAxNC4wMTkgMTUuMTkgMTQuMTA5MiAxNS4yNzc5QzE0LjE5OTMgMTUuMzY1OCAxNC4yNSAxNS40ODUxIDE0LjI1IDE1LjYwOTRWMTcuOTUzMVoiIGZpbGw9IndoaXRlIi8+CjxkZWZzPgo8bGluZWFyR3JhZGllbnQgaWQ9InBhaW50MF9saW5lYXJfMzZfMzkiIHgxPSItNy41NDY4NyIgeTE9Ii00LjM3NSIgeDI9IjI1LjU5MzciIHkyPSIyOC43NjU2IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CjxzdG9wIG9mZnNldD0iMC4xMTAxMTMiIHN0b3AtY29sb3I9IiM1RERFRkIiLz4KPHN0b3Agb2Zmc2V0PSIwLjQ0MzU2OCIgc3RvcC1jb2xvcj0iIzQxOUJDQSIvPgo8c3RvcCBvZmZzZXQ9IjAuNjM2MTIyIiBzdG9wLWNvbG9yPSIjMDA4Q0Q1Ii8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzAyNUVBMCIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo=" title="<?php esc_attr_e( 'Download Monitor protected file', 'download-monitor' ); ?>">
				<?php
			} elseif ( false !== strpos( $url, 'dlm_uploads' ) ) {
				?>
				<img
					src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgiIGhlaWdodD0iMjgiIHZpZXdCb3g9IjAgMCAyOCAyOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI4IDE0QzI4IDYuMjY4MDEgMjEuNzMyIDAgMTQgMEM2LjI2ODAxIDAgMCA2LjI2ODAxIDAgMTRDMCAyMS43MzIgNi4yNjgwMSAyOCAxNCAyOEMyMS43MzIgMjggMjggMjEuNzMyIDI4IDE0WiIgZmlsbD0idXJsKCNwYWludDBfbGluZWFyXzM2XzM5KSIvPgo8cGF0aCBkPSJNMTcuNjE1NCAxMi41NjI1SDE3LjM3NVY5LjUxNTYyQzE3LjM3NSA4LjU4MzIyIDE2Ljk5NTEgNy42ODkwMSAxNi4zMTg5IDcuMDI5N0MxNS42NDI3IDYuMzcwNCAxNC43MjU1IDYgMTMuNzY5MiA2QzEyLjgxMjkgNiAxMS44OTU4IDYuMzcwNCAxMS4yMTk2IDcuMDI5N0MxMC41NDM0IDcuNjg5MDEgMTAuMTYzNSA4LjU4MzIyIDEwLjE2MzUgOS41MTU2MlYxMi41NjI1SDkuOTIzMDhDOS40MTMwNSAxMi41NjI1IDguOTIzOSAxMi43NiA4LjU2MzI2IDEzLjExMTdDOC4yMDI2MSAxMy40NjMzIDggMTMuOTQwMiA4IDE0LjQzNzVWMTkuMTI1QzggMTkuNjIyMyA4LjIwMjYxIDIwLjA5OTIgOC41NjMyNiAyMC40NTA4QzguOTIzOSAyMC44MDI1IDkuNDEzMDUgMjEgOS45MjMwOCAyMUgxNy42MTU0QzE4LjEyNTQgMjEgMTguNjE0NiAyMC44MDI1IDE4Ljk3NTIgMjAuNDUwOEMxOS4zMzU5IDIwLjA5OTIgMTkuNTM4NSAxOS42MjIzIDE5LjUzODUgMTkuMTI1VjE0LjQzNzVDMTkuNTM4NSAxMy45NDAyIDE5LjMzNTkgMTMuNDYzMyAxOC45NzUyIDEzLjExMTdDMTguNjE0NiAxMi43NiAxOC4xMjU0IDEyLjU2MjUgMTcuNjE1NCAxMi41NjI1VjEyLjU2MjVaTTExLjEyNSA5LjUxNTYyQzExLjEyNSA4LjgzMTg2IDExLjQwMzYgOC4xNzYxMSAxMS44OTk1IDcuNjkyNjJDMTIuMzk1NCA3LjIwOTEyIDEzLjA2NzkgNi45Mzc1IDEzLjc2OTIgNi45Mzc1QzE0LjQ3MDUgNi45Mzc1IDE1LjE0MzEgNy4yMDkxMiAxNS42MzkgNy42OTI2MkMxNi4xMzQ5IDguMTc2MTEgMTYuNDEzNSA4LjgzMTg2IDE2LjQxMzUgOS41MTU2MlYxMi41NjI1SDExLjEyNVY5LjUxNTYyWk0xNC4yNSAxNy45NTMxQzE0LjI1IDE4LjA3NzQgMTQuMTk5MyAxOC4xOTY3IDE0LjEwOTIgMTguMjg0NkMxNC4wMTkgMTguMzcyNSAxMy44OTY3IDE4LjQyMTkgMTMuNzY5MiAxOC40MjE5QzEzLjY0MTcgMTguNDIxOSAxMy41MTk0IDE4LjM3MjUgMTMuNDI5MyAxOC4yODQ2QzEzLjMzOTEgMTguMTk2NyAxMy4yODg1IDE4LjA3NzQgMTMuMjg4NSAxNy45NTMxVjE1LjYwOTRDMTMuMjg4NSAxNS40ODUxIDEzLjMzOTEgMTUuMzY1OCAxMy40MjkzIDE1LjI3NzlDMTMuNTE5NCAxNS4xOSAxMy42NDE3IDE1LjE0MDYgMTMuNzY5MiAxNS4xNDA2QzEzLjg5NjcgMTUuMTQwNiAxNC4wMTkgMTUuMTkgMTQuMTA5MiAxNS4yNzc5QzE0LjE5OTMgMTUuMzY1OCAxNC4yNSAxNS40ODUxIDE0LjI1IDE1LjYwOTRWMTcuOTUzMVoiIGZpbGw9IndoaXRlIi8+CjxkZWZzPgo8bGluZWFyR3JhZGllbnQgaWQ9InBhaW50MF9saW5lYXJfMzZfMzkiIHgxPSItNy41NDY4NyIgeTE9Ii00LjM3NSIgeDI9IjI1LjU5MzciIHkyPSIyOC43NjU2IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CjxzdG9wIG9mZnNldD0iMC4xMTAxMTMiIHN0b3AtY29sb3I9IiM1RERFRkIiLz4KPHN0b3Agb2Zmc2V0PSIwLjQ0MzU2OCIgc3RvcC1jb2xvcj0iIzQxOUJDQSIvPgo8c3RvcCBvZmZzZXQ9IjAuNjM2MTIyIiBzdG9wLWNvbG9yPSIjMDA4Q0Q1Ii8+CjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzAyNUVBMCIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjwvc3ZnPgo=" title="<?php esc_attr_e( 'Download Monitor file', 'download-monitor' ); ?>">
				<?php
			} else { ?>
				<span class="dashicons dashicons-no"
				      style="color:red"></span><?php echo esc_html__( 'Un-Protected', 'download-monitor' ) ?>

			<?php }

		}
	}

	/**
	 * Add bulk actions to Media Library table
	 *
	 * @param $bulk_actions
	 *
	 * @return mixed
	 * @since 4.7.2
	 */
	public function dlm_ml_bulk_actions( $bulk_actions ) {
		$bulk_actions['dlm_protect_files'] = __( 'Download Monitor protect', 'download-monitor' );

		return $bulk_actions;
	}

	/**
	 * Handle our bulk actions
	 *
	 * @param $location
	 * @param $doaction
	 * @param $post_ids
	 *
	 * @return string
	 * @since 4.7.2
	 */
	public function dlm_ml_handle_bulk( $location, $doaction, $post_ids ) {

		global $pagenow;
		if ( 'dlm_protect_files' === $doaction ) {
			return admin_url(
				add_query_arg(
					array(
						'dlm_action' => $doaction,
						'posts'      => $post_ids
					), '/upload.php' ) );
		}

		return $location;
	}

	/**
	 * Bulk action for protecting files
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function dlm_ml_do_bulk() {
		// If there's no action or posts, bail
		if ( ! isset( $_GET['dlm_action'] ) || ! isset( $_GET['posts'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['dlm_action'] ) );
		$posts = array_map( 'absint', $_GET['posts'] );

		if ( 'dlm_protect_files' === $action ) {
			foreach ( $posts as $post_id ) {
				// If it's not an attachment or is already protected, skip it
				if ( 'attachment' !== get_post_type( $post_id ) || ( '1' === get_post_meta( $post_id, 'dlm_protected_file', true ) ) ) {
					continue;
				}
				// Create the file object
				$file = array(
					'attachment_id' => $post_id,
					'user_id'       => get_current_user_id(),
					'title'         => get_the_title( $post_id ),
				);
				// Move the file
				download_monitor()->service( 'file_manager' )->move_file_to_dlm_uploads( $file['attachment_id'] );
				// Create the Download
				$this->create_download( $file );
			}
		}
		// Redirect to the media library when finished.
		wp_redirect( admin_url( 'upload.php' ) );
		exit;
	}
}
