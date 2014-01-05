<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * DLM_Shortcodes class.
 */
class DLM_Shortcodes {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_shortcode( 'total_downloads', array( $this, 'total_downloads' ) );
		add_shortcode( 'total_files', array( $this, 'total_files' ) );
		add_shortcode( 'download', array( $this, 'download' ) );
		add_shortcode( 'download_data', array( $this, 'download_data' ) );
		add_shortcode( 'downloads', array( $this, 'downloads' ) );
	}

	/**
	 * total_downloads function.
	 *
	 * @access public
	 * @return void
	 */
	public function total_downloads() {
		global $wpdb;

		return $wpdb->get_var( "
			SELECT SUM( meta_value ) FROM $wpdb->postmeta
			LEFT JOIN $wpdb->posts on $wpdb->postmeta.post_id = $wpdb->posts.ID
			WHERE meta_key = '_download_count'
			AND post_type = 'dlm_download'
			AND post_status = 'publish'
		" );
	}

	/**
	 * total_files function.
	 *
	 * @access public
	 * @return void
	 */
	public function total_files() {
		$count_posts = wp_count_posts( 'dlm_download' );

		return $count_posts->publish;
	}

	/**
	 * download function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function download( $atts, $content = '' ) {
		global $download_monitor, $dlm_download;

		extract( shortcode_atts( array(
			'id'         => '',
			'autop'      => false,
			'template'   => dlm_get_default_download_template(),
			'version_id' => '',
			'version'    => ''
		), $atts ) );

		$id = apply_filters( 'dlm_shortcode_download_id', $id );

		if ( empty( $id ) )
			return;

	  	// If we have content, wrap in a link only
	  	if ( $content ) {

	  		$download = new DLM_Download( $id );

	  		if ( $download->exists() ) {

		  		if ( $version )
					$version_id = $dlm_download->get_version_id( $version );

				if ( $version_id )
					$dlm_download->set_version( $version_id );

				return '<a href="' . $download->get_the_download_link() . '">' . $content . '</a>';

			} else {
				return '[' . __( 'Download not found', 'download_monitor' ) . ']';
			}
	  	}

	  	// If there is no content, get the template part
	  	else {

	  		ob_start();

			$downloads = new WP_Query( array(
		    	'post_type'      => 'dlm_download',
		    	'posts_per_page' => 1,
		    	'no_found_rows'  => 1,
		    	'post_status'    => 'publish',
		    	'p'              => $id
		  	) );

			if ( $downloads->have_posts() ) {

				while ( $downloads->have_posts() ) {
					$downloads->the_post();

					if ( $version )
						$version_id = $dlm_download->get_version_id( $version );

					if ( $version_id )
						$dlm_download->set_version( $version_id );

					$download_monitor->get_template_part( 'content-download', $template );
				}

			} else {
				echo '[' . __( 'Download not found', 'download_monitor' ) . ']';
			}

			wp_reset_postdata();

			if ( $autop === 'true' || $autop === true )
				return wpautop( ob_get_clean() );
			else
				return ob_get_clean();
	  	}
	}

	/**
	 * download_data function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @param mixed $content
	 * @return void
	 */
	public function download_data( $atts ) {
		global $download_monitor;

		extract( shortcode_atts( array(
			'id'         => '',
			'data'       => '',
			'version_id' => '',
			'version'    => ''
		), $atts ) );

		$id = apply_filters( 'dlm_shortcode_download_id', $id );

		if ( empty( $id ) || empty( $data ) )
			return;

		$download = new DLM_Download( $id );

		if ( $version )
			$version_id = $download->get_version_id( $version );

		if ( $version_id )
			$download->set_version( $version_id );

		switch ( $data ) {

			// File / Version Info
			case 'filename' :
				return $download->get_the_filename();
			case 'filetype' :
				return $download->get_the_filetype();
			case 'filesize' :
				return $download->get_the_filesize();
			case 'md5' :
				return $download->get_the_hash( 'md5' );
			case 'sha1' :
				return $download->get_the_hash( 'sha1' );
			case 'crc32' :
				return $download->get_the_hash( 'crc32' );
			case 'version' :
				return $download->get_the_version_number();

			// Download Info
			case 'title' :
				return $download->get_the_title();
			case 'short_description' :
				return $download->get_the_short_description();
			case 'download_link' :
				return $download->get_the_download_link();
			case 'download_count' :
				return $download->get_the_download_count();
			case 'post_content' :
				return wpautop( wptexturize( do_shortcode( $download->post->post_content ) ) );
			case 'post_date' :
				return date_i18n( get_option( 'date_format' ), strtotime( $download->post->post_date ) );
			case 'file_date' :
				return date_i18n( get_option( 'date_format' ), strtotime( $download->get_the_file_date() ) );
			case 'author' :
				return $download->get_the_author();

			// Images
			case 'image' :
				return $download->get_the_image( 'full' );
			case 'thumbnail' :
				return $download->get_the_image( 'thumbnail' );

			// Taxonomies
			case 'tags' :
				return get_the_term_list( $id, 'dlm_download_tags', '', ', ', '' );
			case 'categories' :
				return get_the_term_list( $id, 'dlm_download_category', '', ', ', '' );
		}
	}

	/**
	 * downloads function.
	 *
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function downloads( $atts ) {
		global $download_monitor, $dlm_max_num_pages;

		extract( shortcode_atts( array(
			// Query args
			'per_page'                  => '-1', // -1 = no limit
			'orderby'                   => 'title', // title, rand, ID, none, date, modifed, post__in, download_count
			'order'                     => 'desc', // ASC or DESC
			'include'                   => '', // Comma separate IDS
			'exclude'                   => '', // Comma separate IDS
			'offset'                    => '',
			'category'                  => '', // Comma separate slugs
			'category_include_children' => true, // Set to false to not include child categories
			'tag'                       => '', // Comma separate slugs
			'featured'                  => false, // Set to true to only pull featured downloads
			'members_only'              => false, // Set to true to only pull member downloads

			// Output args
			'template'                  => dlm_get_default_download_template(),
			'loop_start'                => '<ul class="dlm-downloads">',
			'loop_end'                  => '</ul>',
			'before'                    => '<li>',
			'after'                     => '</li>',
			'paginate'                  => false
		), $atts ) );

		$post__in     = ! empty( $include ) ? explode( ',', $include ) : '';
		$post__not_in = ! empty( $exclude ) ? explode( ',', $exclude ) : '';
		$order        = strtoupper( $order );
		$meta_key     = '';

		switch ( $orderby ) {
			case 'title' :
			case 'rand' :
			case 'ID' :
			case 'date' :
			case 'modified' :
			case 'post__in' :
				$orderby = $orderby;
				break;
			case 'id' :
				$orderby = 'ID';
				break;
			case 'hits' :
			case 'count' :
			case 'download_count' :
				$orderby  = 'meta_value_num';
				$meta_key = '_download_count';
				break;
			default :
				$orderby = 'title';
			break;
		}

	  	$args = array(
	    	'post_type'      => 'dlm_download',
	    	'posts_per_page' => $per_page,
	    	'offset'         => $paginate ? ( max( 1, get_query_var( 'paged' ) ) - 1 ) * $per_page : $offset,
	    	'post_status'    => 'publish',
	    	'orderby'        => $orderby,
	    	'order'          => $order,
	    	'$meta_key'      => $meta_key,
	    	'post__in'       => $post__in,
	    	'post__not_in'   => $post__not_in,
	    	'meta_query'     => array()
	  	);

	  	if ( $category || $tag ) {
		  	$args['tax_query'] = array( 'relation' => 'AND' );

		  	$categories = array_filter( explode( ',', $category ) );
		  	$tags       = array_filter( explode( ',', $tag ) );

		  	if ( ! empty( $categories ) ) {
			  	$args['tax_query'][] = array(
					'taxonomy'         => 'dlm_download_category',
					'field'            => 'slug',
					'terms'            => $categories,
					'include_children' => $category_include_children
			  	);
		  	}

		  	if ( ! empty( $tags ) ) {
			  	$args['tax_query'][] = array(
			  		'taxonomy' => 'dlm_download_tag',
					'field'    => 'slug',
					'terms'    => $tags
			  	);
		  	}
	  	}

	  	if ( $featured === 'true' || $featured === true ) {
	    	$args['meta_query'][] = array(
	    		'key'   => '_featured',
	    		'value' => 'yes'
	    	);
    	}

    	if ( $members_only === 'true' || $members_only === true ) {
	    	$args['meta_query'][] = array(
	    		'key'   => '_members_only',
	    		'value' => 'yes'
	    	);
    	}

	  	ob_start();

		$downloads         = new WP_Query( $args );
		$dlm_max_num_pages = $downloads->max_num_pages;

		if ( $downloads->have_posts() ) : ?>

			<?php echo html_entity_decode( $loop_start ); ?>

			<?php while ( $downloads->have_posts() ) : $downloads->the_post(); ?>

				<?php echo html_entity_decode( $before ); ?>

				<?php $download_monitor->get_template_part( 'content-download', $template ); ?>

				<?php echo html_entity_decode( $after ); ?>

			<?php endwhile; // end of the loop. ?>

			<?php echo html_entity_decode( $loop_end ); ?>

			<?php if ( $paginate ) $download_monitor->get_template_part( 'pagination', '' ); ?>

		<?php endif;

		wp_reset_postdata();

	  	return ob_get_clean();
	}
}

new DLM_Shortcodes();