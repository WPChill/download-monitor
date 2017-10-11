<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Admin_CPT class.
 *
 * Add/modify CPT screen
 *
 */
class DLM_Admin_CPT {

	/**
	 * __construct function.
	 *
	 * @access public
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

		// bulk and quick edit
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
		add_action( 'quick_edit_custom_box',  array( $this, 'quick_edit' ), 10, 2 );
		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );
	}

	/**
	 * downloads_by_category function.
	 *
	 * @access public
	 *
	 * @param int $show_counts (default: 1)
	 * @param int $hierarchical (default: 1)
	 * @param int $show_uncategorized (default: 1)
	 * @param string $orderby (default: '')
	 *
	 * @return void
	 */
	public function downloads_by_category( $show_counts = 1, $hierarchical = 1, $show_uncategorized = 1, $orderby = '' ) {
		global $typenow, $wp_query;

		if ( $typenow != 'dlm_download' ) {
			return;
		}

		include_once( 'class-dlm-category-walker.php' );

		$r                 = array();
		$r['pad_counts']   = 1;
		$r['hierarchical'] = $hierarchical;
		$r['hide_empty']   = 1;
		$r['show_count']   = $show_counts;
		$r['selected']     = ( isset( $wp_query->query['dlm_download_category'] ) ) ? $wp_query->query['dlm_download_category'] : '';

		$r['menu_order'] = false;

		if ( $orderby == 'order' ) {
			$r['menu_order'] = 'asc';
		} elseif ( $orderby ) {
			$r['orderby'] = $orderby;
		}

		$terms = get_terms( 'dlm_download_category', $r );

		if ( ! $terms ) {
			return;
		}

		$output = "<select name='dlm_download_category' id='dropdown_dlm_download_category'>";
		$output .= '<option value="" ' . selected( isset( $_GET['dlm_download_category'] ) ? $_GET['dlm_download_category'] : '', '', false ) . '>' . __( 'Select a category', 'download-monitor' ) . '</option>';
		$output .= $this->walk_category_dropdown_tree( $terms, 0, $r );
		$output .= "</select>";

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
		if ( empty( $args[2]['walker'] ) || ! is_a( $args[2]['walker'], 'Walker' ) ) {
			$walker = new DLM_Category_Walker();
		} else {
			$walker = $args[2]['walker'];
		}

		return call_user_func_array( array( $walker, 'walk' ), $args );
	}

	/**
	 * delete_post function.
	 *
	 * @access public
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	public function delete_post( $id ) {
		global $wpdb;

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			switch ( $post_type ) {
				case 'dlm_download' :
					$versions = get_children( 'post_parent=' . $id . '&post_type=dlm_download_version' );
					if ( is_array( $versions ) && count( $versions ) > 0 ) {
						foreach ( $versions as $child ) {
							wp_delete_post( $child->ID, true );
						}
					}
					break;
			}
		}
	}

	/**
	 * enter_title_here function.
	 *
	 * @param string $text
	 * @param WP_Post $post
	 *
	 * @access public
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( 'dlm_download' == $post->post_type ) {
			return __( 'Download title', 'download-monitor' );
		}

		return $text;
	}

	/**
	 * post_updated_messages function.
	 *
	 * @access public
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$messages['dlm_download'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Download updated.', 'download-monitor' ),
			2  => __( 'Custom field updated.', 'download-monitor' ),
			3  => __( 'Custom field deleted.', 'download-monitor' ),
			4  => __( 'Download updated.', 'download-monitor' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Download restored to revision from %s', 'download-monitor' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Download published.', 'download-monitor' ),
			7  => __( 'Download saved.', 'download-monitor' ),
			8  => __( 'Download submitted.', 'download-monitor' ),
			9  => sprintf( __( 'Download scheduled for: <strong>%1$s</strong>.', 'download-monitor' ),
				date_i18n( __( 'M j, Y @ G:i', 'download-monitor' ), strtotime( $post->post_date ) ) ),
			10 => __( 'Download draft updated.', 'download-monitor' ),
		);

		return $messages;
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
	public function columns( $columns ) {
		$columns = array();

		$columns["cb"]             = "<input type=\"checkbox\" />";
		$columns["thumb"]          = '<span>' . __( "Image", 'download-monitor' ) . '</span>';
		$columns["title"]          = __( "Title", 'download-monitor' );
		$columns["download_id"]    = __( "ID", 'download-monitor' );
		$columns["file"]           = __( "File", 'download-monitor' );
		$columns["version"]        = __( "Version", 'download-monitor' );
		$columns["download_cat"]   = __( "Categories", 'download-monitor' );
		$columns["download_tag"]   = __( "Tags", 'download-monitor' );
		$columns["download_count"] = __( "Download count", 'download-monitor' );
		$columns["featured"]       = __( "Featured", 'download-monitor' );
		$columns["members_only"]   = __( "Members only", 'download-monitor' );
		$columns["redirect_only"]  = __( "Redirect only", 'download-monitor' );
		$columns["date"]           = __( "Date posted", 'download-monitor' );

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
	public function custom_columns( $column ) {
		global $post;

		$download = new DLM_Download( $post->ID );
		$file     = $download->get_file_version();

		switch ( $column ) {
			case "thumb" :
				echo $download->get_the_image();
				break;
			case "download_id" :
				echo $post->ID;
				break;
			case "download_cat" :
				if ( ! $terms = get_the_term_list( $post->ID, 'dlm_download_category', '', ', ', '' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo $terms;
				}
				break;
			case "download_tag" :
				if ( ! $terms = get_the_term_list( $post->ID, 'dlm_download_tag', '', ', ', '' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo $terms;
				}
				break;
			case "featured" :
				if ( $download->is_featured() ) {
					echo '<span class="yes">' . __( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "members_only" :
				if ( $download->is_members_only() ) {
					echo '<span class="yes">' . __( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "redirect_only" :
				if ( $download->redirect_only() ) {
					echo '<span class="yes">' . __( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "file" :
				if ( $file ) {
					echo '<a href="' . $download->get_the_download_link() . '"><code>' . $file->filename;
					if ( $size = $download->get_the_filesize() ) {
						echo ' &ndash; ' . $size;
					}
					echo '</code></a>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "version" :
				if ( $file && $file->version ) {
					echo $file->version;
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case "download_count" :
				echo number_format( $download->get_the_download_count(), 0, '.', ',' );
				break;
			case "featured" :
				if ( $download->is_featured() ) {
					echo '<img src="' . WP_DLM::get_plugin_url() . '/assets/images/on.png" alt="yes" />';
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
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'download_id' == $vars['orderby'] ) {
				$vars['orderby'] = 'ID';
			} elseif ( 'download_count' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_download_count',
					'orderby'  => 'meta_value_num'
				) );

			} elseif ( 'featured' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_featured',
					'orderby'  => 'meta_value'
				) );

			} elseif ( 'members_only' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_members_only',
					'orderby'  => 'meta_value'
				) );

			} elseif ( 'redirect_only' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_redirect_only',
					'orderby'  => 'meta_value'
				) );
			}
		}

		return $vars;
	}

	/**
	 * Custom bulk edit - form
	 *
	 * @param mixed $column_name
	 * @param mixed $post_type
	 */
	public function quick_edit( $column_name, $post_type ) {

		// only on our PT
		if ( 'dlm_download' != $post_type || 'featured' != $column_name ) {
			return;
		}

		// nonce field
		wp_nonce_field( 'dlm_quick_edit_nonce', 'dlm_quick_edit_nonce' );

		$this->bulk_quick_edit_fields();
	}

	/**
	 * Custom bulk edit - form
	 *
	 * @param mixed $column_name
	 * @param mixed $post_type
	 */
	public function bulk_edit( $column_name, $post_type ) {

		// only on our PT
		if ( 'dlm_download' != $post_type || 'featured' != $column_name ) {
			return;
		}

		// nonce field
		wp_nonce_field( 'dlm_bulk_edit_nonce', 'dlm_bulk_edit_nonce' );

		$this->bulk_quick_edit_fields();
	}

	/**
	 * Output the build and quick edit fields
	 */
	private function bulk_quick_edit_fields() {
		?>
		<fieldset class="inline-edit-col-right inline-edit-col-dlm">
			<div class="inline-edit-col inline-edit-col-dlm-inner">
				<span class="title"><?php _e( 'Download Monitor Data', 'download-monitor' ); ?></span><br/>
				<label for="_featured"><input type="checkbox" name="_featured" id="_featured"
				                              value="1"/><?php _e( 'Featured download', 'download-monitor' ); ?></label>
				<label for="_members_only"><input type="checkbox" name="_members_only" id="_members_only"
				                                  value="1"/><?php _e( 'Members only', 'download-monitor' ); ?></label>
				<label for="_redirect_only"><input type="checkbox" name="_redirect_only" id="_redirect_only"
				                                   value="1"/><?php _e( 'Redirect to file', 'download-monitor' ); ?>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Quick and bulk edit saving
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return int
	 */
	public function bulk_and_quick_edit_save_post( $post_id, $post ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'dlm_download' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'manage_downloads', $post_id ) ) {
			return $post_id;
		}

		// handle bulk
		if ( isset( $_REQUEST['dlm_bulk_edit_nonce'] ) ) {

			// check nonce
			if ( ! wp_verify_nonce( $_REQUEST['dlm_bulk_edit_nonce'], 'dlm_bulk_edit_nonce' ) ) {
				return $post_id;
			}

			// set featured
			if ( isset( $_REQUEST['_featured'] ) ) {
				update_post_meta( $post_id, '_featured', 'yes' );
			}

			// set members only
			if ( isset( $_REQUEST['_members_only'] ) ) {
				update_post_meta( $post_id, '_members_only', 'yes' );
			}

			// set redirect only
			if ( isset( $_REQUEST['_redirect_only'] ) ) {
				update_post_meta( $post_id, '_redirect_only', 'yes' );
			}

		}

		// handle quick
		if ( isset( $_REQUEST['dlm_quick_edit_nonce'] ) ) {

			// check nonce
			if ( ! wp_verify_nonce( $_REQUEST['dlm_quick_edit_nonce'], 'dlm_quick_edit_nonce' ) ) {
				return $post_id;
			}

			// set featured
			if ( isset( $_REQUEST['_featured'] ) ) {
				update_post_meta( $post_id, '_featured', 'yes' );
			} else {
				update_post_meta( $post_id, '_featured', 'no' );
			}

			// set members only
			if ( isset( $_REQUEST['_members_only'] ) ) {
				update_post_meta( $post_id, '_members_only', 'yes' );
			} else {
				update_post_meta( $post_id, '_members_only', 'no' );
			}

			// set redirect only
			if ( isset( $_REQUEST['_redirect_only'] ) ) {
				update_post_meta( $post_id, '_redirect_only', 'yes' );
			} else {
				update_post_meta( $post_id, '_redirect_only', 'no' );
			}

		}

		return $post_id;
	}
}