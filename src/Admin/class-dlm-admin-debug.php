<?php

class DLM_Debug {

	/**
	 * Holds the class object.
	 *
	 * @since 5.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * DLM_Debug constructor.
	 *
	 * @since 5.0.0
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'dlm_export_download' ) );

		/* Fire our meta box setup function on the post editor screen. */
		add_action( 'load-post.php', array( $this, 'debug_meta_box_setup' ) );
		add_action( 'load-post-new.php', array( $this, 'debug_meta_box_setup' ) );

		// Hide debug download by default
		add_filter( 'hidden_meta_boxes', array( $this, 'hide_meta_box' ), 10, 2 );
	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Debug object.
	 * @since 5.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Debug ) ) {
			self::$instance = new DLM_Debug();
		}

		return self::$instance;
	}

	/**
	 * Export single gallery
	 *
	 * @since 5.0.0
	 */
	public function dlm_export_download() {
		// Check if nonce is set.
		if ( ! isset( $_GET['nonce'] ) ) {
			return;
		}
		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $_GET['nonce'], 'dlm_single_download' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			return;
		}
		// Check if user has edit permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( isset( $_GET['dlm_single_download'] ) ) {
			// WXR_VERSION is declared here.
			require_once ABSPATH . 'wp-admin/includes/export.php';

			$download_id = absint( $_GET['dlm_single_download'] );
			$date        = gmdate( 'Y-m-d' );
			$wp_filename = 'dlm_' . $download_id . '_debug_export_' . $date . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $wp_filename );
			header( 'Content-Type: text/csv' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );

			echo $this->get_csv_string( $download_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			die();
		}
	}

	/**
	 * Get download array
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public function get_csv_data( $download_id ) {
		// Build array
		$csv_data = array();

		/**
		 * Allow the addition of extra export fields.
		 *
		 * The following filters are available:
		 *
		 * dlm_ce_extra_fields_download         - Add extra Download fields
		 * dlm_ce_extra_fields_download_meta    - Add extra Download meta fields
		 * dlm_ce_extra_fields_version          - Add extra Version fields
		 * dlm_ce_extra_fields_version_meta     - Add extra Version meta fields
		 */
		$extra_fields = array(
			'download'      => apply_filters( 'dlm_ce_extra_fields_download', array() ),
			'download_meta' => apply_filters( 'dlm_ce_extra_fields_download_meta', array() ),
			'version'       => apply_filters( 'dlm_ce_extra_fields_version', array() ),
			'version_meta'  => apply_filters( 'dlm_ce_extra_fields_version_meta', array() ),
		);

		// Add CSV header
		$csv_data['header'] = array(
			'type',
			'download_id',
			'title',
			'description',
			'thumbnail',
			'excerpt',
			'categories',
			'tags',
			'featured',
			'members_only',
			'redirect',
			'version',
			'download_count',
			'file_date',
			'file_urls',
			'version_order',
			'published_date',
		);

		// Add extra fields to header
		if ( count( $extra_fields ) > 0 ) {
			foreach ( $extra_fields as $extra_field_type ) {
				foreach ( $extra_field_type as $extra_field ) {
					$csv_data['header'][] = $extra_field;
				}
			}
		}

		// Get the download
		$download_object = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

		// Build CSV row
		$csv_row = array(
			'type'           => 'download',
			'download_id'    => $download_object->get_id(),
			'title'          => $download_object->get_title(),
			'description'    => $download_object->get_description(),
			'excerpt'        => $download_object->get_excerpt(),
			'thumbnail'      => get_the_post_thumbnail_url( $download_object->get_id(), 'full' ),
			'published_date' => get_the_date( 'Y-m-d H:i:s', $download_object->get_id() ),
		);

		// Featured
		$csv_row['featured'] = ( $download_object->is_featured() ? 1 : 0 );

		// Members only
		$csv_row['members_only'] = ( $download_object->is_members_only() ? 1 : 0 );

		// Redirect
		$csv_row['redirect'] = ( $download_object->is_redirect_only() ? 1 : 0 );

		// Categories
		$csv_row['categories'] = $this->get_terms_string( $download_object->get_id(), 'dlm_download_category' );

		// Tags
		$csv_row['tags'] = $this->get_terms_string( $download_object->get_id(), 'dlm_download_tag' );

		// Add extra download fields
		if ( count( $extra_fields['download'] ) > 0 ) {
			foreach ( $extra_fields['download'] as $extra_field_key => $extra_field_label ) {
				// Check if the field exists in the Download object
				if ( isset( $download_object->$extra_field_key ) ) {
					$csv_row[ $extra_field_label ] = $download_object->$extra_field_key;
				}
			}
		}

		// Add extra download meta fields
		if ( count( $extra_fields['download_meta'] ) > 0 ) {
			foreach ( $extra_fields['download_meta'] as $extra_field_key => $extra_field_label ) {
				// Get the download meta
				$extra_field_value = get_post_meta( $download_object->get_id(), $extra_field_key, true );

				// Check if the field exists
				if ( '' !== $extra_field_value ) {
					$csv_row[ $extra_field_label ] = $extra_field_value;
				}
			}
		}

		// Add download row to array
		$csv_data['data'][] = $csv_row;

		// Get versions
		$versions = $download_object->get_versions();

		// Check && Loop
		if ( count( $versions ) > 0 ) {
			/** @var DLM_Download_Version $version */
			foreach ( $versions as $version ) {
				// The version post
				$version_post = get_post( $version->get_id() );

				// The version row
				$version_row = array(
					'type'           => 'version',
					'version'        => $version->get_version(),
					'download_count' => $version->get_download_count(),
					'file_date'      => $version_post->post_date,
					'file_urls'      => addslashes( implode( '|', $version->get_mirrors() ) ),
					'version_order'  => $version_post->menu_order,
				);

				// Add extra version fields
				if ( count( $extra_fields['version'] ) > 0 ) {
					foreach ( $extra_fields['version'] as $extra_field_key => $extra_field_label ) {
						// Check if the field exists in the Version object
						if ( isset( $version->$extra_field_key ) ) {
							$version_row[ $extra_field_label ] = $version->$extra_field_key;
						}
					}
				}

				// Add extra version meta fields
				if ( count( $extra_fields['version_meta'] ) > 0 ) {
					foreach ( $extra_fields['version_meta'] as $extra_field_key => $extra_field_label ) {
						// Get the Version meta
						$extra_field_value = get_post_meta( $version->get_id(), $extra_field_key, true );

						// Check if the field exists
						if ( '' !== $extra_field_value ) {
							$version_row[ $extra_field_label ] = $extra_field_value;
						}
					}
				}

				// Add version to CSV data
				$csv_data['data'][] = $version_row;
			}
		}

		//  \o/
		return $csv_data;
	}

	/**
	 * Generate the CSV row
	 *
	 * @param  array  $headers
	 * @param  array  $row
	 *
	 * @return string
	 * @since 5.0.0
	 */
	private function build_csv_row( $headers, $row ) {
		// Base csv row
		$cr = '';

		foreach ( $headers as $header ) {
			// Check if this col is set in row
			if ( isset( $row[ $header ] ) ) {
				// The column
				$col = $row[ $header ];

				// Check if the column contains double quotes
				if ( false !== strpos( $col, '"' ) ) {
					// Replace double quotes with single quotes
					$col = str_ireplace( '"', "'", $col );
				}

				// Check if the column contains a comma
				if ( ! empty( $col ) && ( "description" == $header || "excerpt" == $header || false !== strpos( $col, ',' ) ) ) {
					// Wrap data in "
					$col = '"' . $col . '"';
				}

				// Add column to row
				$cr .= $col;
			}

			// End col with comma
			$cr .= ',';
		}

		// Remove last comma \o/
		$cr = substr( $cr, 0, - 1 );

		// Return csv row
		return $cr . PHP_EOL;
	}

	/**
	 * Generate the CSV string
	 *
	 * @return String
	 * @since 5.0.0
	 */
	public function get_csv_string( $download_id ) {
		$data = $this->get_csv_data( $download_id );
		// Base
		$csv_string = '';

		// Headers
		$csv_string .= implode( ',', $data['header'] ) . PHP_EOL;

		foreach ( $data['data'] as $row ) {
			$csv_string .= $this->build_csv_row( $data['header'], $row );
		}

		// Return
		return $csv_string;
	}

	/**
	 * Get terms from $taxanomy and format them in csv string
	 *
	 * @param $download_id
	 * @param $taxonomy
	 *
	 * @return String
	 * @since 5.0.0
	 */
	private function get_terms_string( $download_id, $taxonomy ) {
		// Get terms
		$db_terms = get_the_terms( $download_id, $taxonomy );

		// Vars
		$terms        = array();
		$terms_string = '';

		// Check & Loop
		if ( false !== $db_terms && count( $db_terms ) > 0 ) {
			foreach ( $db_terms as $db_term ) {
				$terms[] = $db_term->name;
			}

			// Implode to string
			$terms_string = implode( '|', $terms );
		}

		// \o/
		return $terms_string;
	}

	/**
	 * Add Debug metabox
	 *
	 * @since 5.0.0
	 */
	public function debug_meta_box_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( $this, 'add_debug_meta_box' ), 10 );
	}

	/**
	 * Add Debug metabox
	 *
	 * @since 5.0.0
	 */
	public function add_debug_meta_box() {
		add_meta_box(
			'dlm-debug',                                           // Unique ID
			esc_html__( 'Debug download', 'download-monitor' ),    // Title
			array( $this, 'output_debug_meta' ),                   // Callback function
			'dlm_download',                                        // Admin page (or post type)
			'side',                                                // Context
			'low'         // Priority
		);
	}

	/**
	 * Default hidden debug metabox
	 *
	 * @since 5.0.0
	 */
	public function hide_meta_box( $hidden, $screen ) {
		$hidden_metaboxes = get_user_meta( get_current_user_id(), 'metaboxhidden_dlm_download', true );
		//make sure we are dealing with the correct screen
		if ( ( 'post' === $screen->base ) && ( 'dlm_download' === $screen->id ) && is_array( $hidden_metaboxes ) && in_array( 'dlm-debug', $hidden_metaboxes ) ) {
			$hidden[] = 'dlm-debug';
		}

		return $hidden;
	}

	/**
	 * Output the Debug Download metabox
	 *
	 * @since 5.0.0
	 */
	public function output_debug_meta() {
		?>
		<div class="dlm-upsells-carousel-wrapper">
			<div class="dlm-upsells-carousel">
				<div class="dlm-upsell dlm-upsell-item">
					<p class="dlm-upsell-description"><?php
						echo esc_html__( 'Export the download and send it to Download Monitor\'s support team so that we can debug your problem much easier.', 'download-monitor' ); ?></p>
					<p>
						<a href="<?php
						echo esc_url(
							add_query_arg(
								array(
									'dlm_single_download' => absint( get_the_ID() ),
									'nonce'               => wp_create_nonce( 'dlm_single_download' ),
								)
							)
						);
						?>"
						   class="button"><?php
							esc_html_e( 'Export download', 'download-monitor' ) ?></a>

					</p>
					<?php
					do_action( 'dlm_debug_metabox_content' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
