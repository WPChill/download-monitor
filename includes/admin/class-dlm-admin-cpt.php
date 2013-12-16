<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * DLM_Admin_CPT class.
 */
class DLM_Admin_CPT {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( "restrict_manage_posts", array( $this, "downloads_by_category" ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_filter( 'manage_edit-dlm_download_columns', array( $this, 'columns' ) );
		add_action( 'manage_dlm_download_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'manage_edit-dlm_download_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'request', array( $this, 'sort_columns' ) );
	}

	/**
	 * downloads_by_category function.
	 *
	 * @access public
	 * @param int $show_counts (default: 1)
	 * @param int $hierarchical (default: 1)
	 * @param int $show_uncategorized (default: 1)
	 * @param string $orderby (default: '')
	 * @return void
	 */
	public function downloads_by_category( $show_counts = 1, $hierarchical = 1, $show_uncategorized = 1, $orderby = '' ) {
		global $typenow, $wp_query;

	    if ( $typenow != 'dlm_download' )
	    	return;

		include_once( 'class-dlm-category-walker.php' );

		$r = array();
		$r['pad_counts'] 	= 1;
		$r['hierarchical'] 	= $hierarchical;
		$r['hide_empty'] 	= 1;
		$r['show_count'] 	= $show_counts;
		$r['selected'] 		= ( isset( $wp_query->query['dlm_download_category'] ) ) ? $wp_query->query['dlm_download_category'] : '';

		$r['menu_order'] = false;

		if ( $orderby == 'order' )
			$r['menu_order'] = 'asc';
		elseif ( $orderby )
			$r['orderby'] = $orderby;

		$terms = get_terms( 'dlm_download_category', $r );

		if (!$terms) return;

		$output  = "<select name='dlm_download_category' id='dropdown_dlm_download_category'>";
		$output .= '<option value="" ' .  selected( isset( $_GET['dlm_download_category'] ) ? $_GET['dlm_download_category'] : '', '', false ) . '>'.__( 'Select a category', 'download_monitor' ).'</option>';
		$output .= $this->walk_category_dropdown_tree( $terms, 0, $r );
		$output .="</select>";

		echo $output;
	}

	/**
	 * Walk the Product Categories.
	 *
	 * @access public
	 * @return void
	 */
	private function walk_category_dropdown_tree() {
		$args = func_get_args();

		// the user's options are the third parameter
		if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') )
			$walker = new DLM_Category_Walker;
		else
			$walker = $args[2]['walker'];

		return call_user_func_array( array( $walker, 'walk' ), $args );
	}
	/**
	 * delete_post function.
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function delete_post( $id ) {
		global $wpdb;

		if ( ! current_user_can( 'delete_posts' ) )
			return;

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			switch( $post_type ) {
				case 'dlm_download' :
					if ( $versions =& get_children( 'post_parent=' . $id . '&post_type=dlm_download_version' ) )
						if ( $versions )
							foreach ( $versions as $child )
								wp_delete_post( $child->ID, true );
				break;
			}
		}
	}

	/**
	 * enter_title_here function.
	 *
	 * @access public
	 * @return void
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'dlm_download' )
			return __( 'Download title', 'download_monitor' );
		return $text;
	}

	/**
	 * post_updated_messages function.
	 *
	 * @access public
	 * @param mixed $messages
	 * @return void
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['dlm_download'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __('Download updated.', 'download_monitor'),
			2 => __('Custom field updated.', 'download_monitor'),
			3 => __('Custom field deleted.', 'download_monitor'),
			4 => __('Download updated.', 'download_monitor'),
			5 => isset($_GET['revision']) ? sprintf( __('Download restored to revision from %s', 'download_monitor'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __('Download published.', 'download_monitor'),
			7 => __('Download saved.', 'download_monitor'),
			8 => __('Download submitted.', 'download_monitor'),
			9 => sprintf( __('Download scheduled for: <strong>%1$s</strong>.', 'download_monitor'),
			  date_i18n( __( 'M j, Y @ G:i', 'download_monitor' ), strtotime( $post->post_date ) ) ),
			10 => __('Download draft updated.', 'download_monitor'),
		);

		return $messages;
	}

	/**
	 * columns function.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return void
	 */
	public function columns( $columns ) {
		$columns = array();

		$columns["cb"]             = "<input type=\"checkbox\" />";
		$columns["thumb"]          = '<span>' . __("Image", 'download_monitor') . '</span>';
		$columns["title"]          = __("Title", 'download_monitor');
		$columns["download_id"]    = __("ID", 'download_monitor');
		$columns["file"]           = __("File", 'download_monitor');
		$columns["version"]        = __("Version", 'download_monitor');
		$columns["download_cat"]   = __("Categories", 'download_monitor');
		$columns["download_tag"]   = __("Tags", 'download_monitor');
		$columns["download_count"] = __( "Download count", 'download_monitor' );
		$columns["featured"]       = __( "Featured", 'download_monitor' );
		$columns["members_only"]   = __( "Members only", 'download_monitor' );
		$columns["redirect_only"]  = __( "Redirect only", 'download_monitor' );
		$columns["date"]           = __("Date posted", 'download_monitor');

		return $columns;
	}

	/**
	 * custom_columns function.
	 *
	 * @access public
	 * @param mixed $column
	 * @return void
	 */
	public function custom_columns( $column ) {
		global $post, $download_monitor;

		$download 	= new DLM_Download( $post->ID );
		$file   	= $download->get_file_version();

		switch ($column) {
			case "thumb" :
				echo $download->get_the_image();
			break;
			case "download_id" :
				echo $post->ID;
			break;
			case "download_cat" :
				if ( ! $terms = get_the_term_list( $post->ID, 'dlm_download_category', '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "download_tag" :
				if ( ! $terms = get_the_term_list( $post->ID, 'dlm_download_tag', '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "featured" :

				if ( $download->is_featured() )
					echo '<span class="yes">' . __( 'Yes', 'download_monitor' ) . '</span>';
				else
					echo '<span class="na">&ndash;</span>';

			break;
			case "members_only" :

				if ( $download->is_members_only() )
					echo '<span class="yes">' . __( 'Yes', 'download_monitor' ) . '</span>';
				else
					echo '<span class="na">&ndash;</span>';

			break;
			case "redirect_only" :

				if ( $download->redirect_only() )
					echo '<span class="yes">' . __( 'Yes', 'download_monitor' ) . '</span>';
				else
					echo '<span class="na">&ndash;</span>';

			break;
			case "file" :
				if ( $file ) {
					echo '<a href="' . $download->get_the_download_link() . '"><code>' . $file->filename;
					if ( $size = $download->get_the_filesize() )
						echo ' &ndash; ' . $size;
					echo '</code></a>';
				} else
					echo '<span class="na">&ndash;</span>';
			break;
			case "version" :
				if ( $file && $file->version )
					echo $file->version;
				else
					echo '<span class="na">&ndash;</span>';
			break;
			case "download_count" :
				echo number_format( $download->get_the_download_count(), 0, '.', ',' );
			break;
			case "featured" :

				if ( $download->is_featured() )
					echo '<img src="' . $download_monitor->plugin_url() . '/assets/images/on.png" alt="yes" />';
				else
					echo '<span class="na">&ndash;</span>';

			break;
		}
	}

	/**
	 * sortable_columns function.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return void
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'download_id'    => 'download_id',
			'download_count' => 'download_count',
			'featured'       => 'featured',
			'members_only'   => 'members_only',
			'redirect_only'  => 'redirect_only',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * sort_columns function.
	 *
	 * @access public
	 * @param mixed $vars
	 * @return void
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'download_id' == $vars['orderby'] )
				$vars['orderby'] = 'ID';

			elseif ( 'download_count' == $vars['orderby'] )
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_download_count',
					'orderby' 	=> 'meta_value_num'
				) );

			elseif ( 'featured' == $vars['orderby'] )
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_featured',
					'orderby' 	=> 'meta_value'
				) );

			elseif ( 'members_only' == $vars['orderby'] )
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_members_only',
					'orderby' 	=> 'meta_value'
				) );

			elseif ( 'redirect_only' == $vars['orderby'] )
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_redirect_only',
					'orderby' 	=> 'meta_value'
				) );
		}
		return $vars;
	}
}

new DLM_Admin_CPT();