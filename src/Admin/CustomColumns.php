<?php

class DLM_Custom_Columns {

	// Variable used for columns in order to not ge the download for each column.
	private $column_download;

	public function setup() {
		add_filter( 'manage_edit-dlm_download_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_dlm_download_posts_custom_column', array( $this, 'column_data' ), 2 );
		add_filter( 'manage_edit-dlm_download_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'the_title', array( $this, 'prepend_id_to_title' ), 15, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'set_primary_column_name' ), 10, 2 );
	}

	/**
	 * Get the download based on post ID, used for setting columns info
	 *
	 * @param  mixed $post_id
	 * @return object $download
	 */
	private function get_download( $post_id ) {

		/** @var DLM_Download $download */
		try {
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $post_id );
		} catch ( Exception $e ) {
			$download = new DLM_Download();
		}
		return $download;
	}

	/**
	 * columns function.
	 *
	 * @access public
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_columns( $columns ) {
		$columns = array();

		$columns["cb"]              = "<input type=\"checkbox\" />";
		$columns["download_title"]  = __( "Download Title", 'download-monitor' );
		$columns["download_cat"]    = __( "Categories", 'download-monitor' );
		$columns["version"]         = __( "Version", 'download-monitor' );
		$columns["shortcode"]       = __( "Shortcode", 'download-monitor' );
		$columns["download_link"]   = __( "Download link", 'download-monitor' );
		$columns["download_tag"]    = __( "Tags", 'download-monitor' );
		$columns["download_count"]  = __( "Download count", 'download-monitor' );
		$columns["featured"]        = __( "Featured", 'download-monitor' );
		$columns["locked_download"] = __( "Locked", 'download-monitor' );
		$columns["redirect_only"]   = __( "Redirect only", 'download-monitor' );
		$columns["date"]            = __( "Date posted", 'download-monitor' );

		return $columns;
	}

	/**
	 * custom_columns function.
	 *
	 * @access public
	 *
	 * @param mixed $column
	 *
	 * @return void
	 */
	public function column_data( $column ) {
		global $post;

		if ( ! isset( $this->column_download ) || $post->ID !== $this->column_download->get_id() ) {
			// Store our download in a variable so that we won't have to get the column for each column that uses it.
			// First check for global, as data is set for the__post.
			$this->column_download = isset( $GLOBALS['dlm_download'] ) ? $GLOBALS['dlm_download'] : $this->get_download( $post->ID );
		}

		switch ( $column ) {
			case "download_title":
				global $wp_list_table;

				/** @var DLM_Download_Version $file */
				$file = $this->column_download->get_version();

				if ( ! $wp_list_table ) {
					$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
				}

				$wp_list_table->column_title( $post );

				if ( $file->get_filename() ) {
					echo '<a class="dlm-file-link" href="' . esc_url( $this->column_download->get_the_download_link() ) . '"><code>' . esc_html( $file->get_filename() );
					if ( $size = $this->column_download->get_version()->get_filesize_formatted() ) {
						echo ' &ndash; ' . esc_html( $size );
					}
					echo '</code></a>';
				} else {
					echo '<div class="dlm-listing-no-file"><code>No file provided</code></div>';
				}

				break;
			case "download_cat" :
				if ( ! $terms = get_the_terms( $post->ID, 'dlm_download_category' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					foreach ( $terms as $term ) {
						echo '<a href=' . esc_url( add_query_arg( 'dlm_download_category', esc_attr( $term->slug ) ) ) . '>' . esc_html( $term->name ) . '</a> ';
					}
				}
				break;
			case 'download_tag':
				$terms = get_the_term_list( $post->ID, 'dlm_download_tag', '', ', ', '' );
				if ( ! $terms ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo wp_kses_post( $terms );
				}
				break;
			case 'featured':
				if ( $this->column_download->is_featured() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "locked_download" :
				$is_locked = apply_filters( 'dlm_download_is_locked', $this->column_download->is_members_only(), $this->column_download );
				if ( $is_locked ) {
					echo '<span class="yes" ' . ( $this->column_download->is_members_only() ? 'data-members_only="Yes"' : '' ) . '>' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'redirect_only':
				if ( $this->column_download->is_redirect_only() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "version" :
				/** @var DLM_Download_Version $file */
				$file = $this->column_download->get_version();
				if ( $file && $file->get_version() ) {
					echo esc_html( $file->get_version() );
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;

			case "shortcode" :
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-shortcode" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy shortcode', 'download-monitor' ) . '</span><div class="dl-shortcode-copy"><code>[download id="' . absint( $post->ID ) . '"]</code><input type="text" readonly value="[download id=\'' . absint( $post->ID ) . '\']" class="dlm-copy-shortcode-input"></div></div></button>';
				break;
			case "download_link" :
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-admin-links" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy download link', 'download-monitor' ) . '</span><div class="dl-shortcode-copy">' . esc_url( $this->column_download->get_the_download_link() ) . '<input type="text" readonly value="' . esc_url( $this->column_download->get_the_download_link() ) . '" class="dlm-copy-shortcode-input"></div></div></button>';
				break;
			case "download_count" :
				echo number_format( $this->column_download->get_download_count(), 0, '.', ',' );
				break;
		}
	}

	/**
	 * sortable_columns function.
	 *
	 * @access public
	 *
	 * @param mixed $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'download_id'     => 'download_id',
			'download_title'  => 'download_title',
			'download_count'  => 'download_count',
			'featured'        => 'featured',
			'locked_download' => 'locked_download',
			'redirect_only'   => 'redirect_only',
		);

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Prepends the id to the title.
	 *
	 * @access public
	 *
	 * @param string $title
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function prepend_id_to_title( $title, $id = null ) {

		if ( ! isset( $id ) ) {
			$id = get_the_ID();
		}

		if ( 'dlm_download' === get_post_type( $id ) ) {
			return '#' . $id . ' - ' . $title;
		}

		return $title;
	}

	/**
	 * Defaults the primary column name to 'download_title'
	 *
	 * @access public
	 *
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function set_primary_column_name( $column_name, $context ) {
		if ( 'edit-dlm_download' === $context ) {

			return 'download_title';
		}

		return $column_name;
	}
}
