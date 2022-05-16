<?php

class DLM_Custom_Columns {

	public function setup() {
		add_filter( 'manage_edit-dlm_download_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_dlm_download_posts_custom_column', array( $this, 'column_data' ), 2 );
		add_filter( 'manage_edit-dlm_download_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'the_title', array( $this, 'prepend_id_to_title' ), 15, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'set_primary_column_name' ), 10, 2 );
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

		/** @var DLM_Download $download */
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( array(
			'p'           => absint( $post->ID ),
			'post_status' => array( 'any', 'trash' )
		), 1 );

		if ( 0 == count( $downloads ) ) {
			return;
		}

		$download = $downloads[0];
		switch ( $column ) {
			case "download_title":
				global $wp_list_table;

				/** @var DLM_Download_Version $file */
				$file = $download->get_version();

				if ( ! $wp_list_table ) {
					$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
				}

				$wp_list_table->column_title( $post );

				if ( $file->get_filename() ) {
					echo '<a class="dlm-file-link" href="' . esc_url( $download->get_the_download_link() ) . '"><code>' . esc_html( $file->get_filename() );
					if ( $size = $download->get_version()->get_filesize_formatted() ) {
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
			case "download_tag" :
				if ( ! $terms = get_the_term_list( $post->ID, 'dlm_download_tag', '', ', ', '' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo wp_kses_post( $terms );
				}
				break;
			case "featured" :
				if ( $download->is_featured() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "locked_download" :
				$is_locked = apply_filters( 'dlm_download_is_locked', $download->is_members_only(), $download );
				if ( $is_locked ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "redirect_only" :
				if ( $download->is_redirect_only() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "version" :
				/** @var DLM_Download_Version $file */
				$file = $download->get_version();
				if ( $file && $file->get_version() ) {
					echo esc_html( $file->get_version() );
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;

			case "shortcode" :
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-shortcode" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy shortcode', 'download-monitor' ) . '</span><div class="dl-shortcode-copy"><code>[download id="' . absint( $post->ID ) . '"]</code><input type="text" value="[download id=\'' . absint( $post->ID ) . '\']" class="hidden"></div></div></button>';
				break;
			case "download_count" :
				echo number_format( $download->get_download_count(), 0, '.', ',' );
				break;
			case "featured" :
				if ( $download->is_featured() ) {
					echo '<img src="' . esc_url( download_monitor()->get_plugin_url() ) . '/assets/images/on.png" alt="yes" />';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
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
			'download_id'       => 'download_id',
			'download_count'    => 'download_count',
			'featured'          => 'featured',
			'locked_download'   => 'locked_download',
			'redirect_only'     => 'redirect_only',
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
	public function set_primary_column_name( $column_name, $context ){
		if( 'edit-dlm_download' === $context ){

			return 'download_title';
		}

		return $column_name;
    }
}
