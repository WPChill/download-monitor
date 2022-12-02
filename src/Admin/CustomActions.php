<?php

class DLM_Custom_Actions {

	private $ignored_meta = array( '_edit_last', '_edit_lock', '_download_count' );	
	/**
	 * Setup custom actions
	 */
	public function setup() {
		add_filter( 'request', array( $this, 'sort_columns' ) );

		add_action( "restrict_manage_posts", array( $this, "downloads_by_category" ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );

		// bulk and quick edit
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
		add_action( 'quick_edit_custom_box',  array( $this, 'quick_edit' ), 10, 2 );
		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );

		// duplicate download
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'wp_ajax_dlm_download_duplicator_duplicate', array( $this, 'ajax_duplicate_download' ) );

		// duplicate Admin Notice
		if ( isset( $_GET['dlm-download-duplicator-success'] ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ), 8 );
		}

		// duplicate AAM access
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'dlm-advanced-access-manager/dlm-advanced-access-manager.php' ) ) {
			require_once( 'Duplicate/DownloadDuplicatorAAM.php' );
			$aam_compat = new DLM_Download_Duplicator_AAM();
			$aam_compat->setup();
		}
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

		$r                 = array();
		$r['taxonomy']     = 'dlm_download_category';
		$r['pad_counts']   = 1;
		$r['hierarchical'] = $hierarchical;
		$r['hide_empty']   = 1;
		$r['show_count']   = $show_counts;
		$r['selected']     = ( isset( $wp_query->query['dlm_download_category'] ) ) ? $wp_query->query['dlm_download_category'] : '';
		$r['menu_order']   = false;

		if ( $orderby == 'order' ) {
			$r['menu_order'] = 'asc';
		} elseif ( $orderby ) {
			$r['orderby'] = $orderby;
		}

		$terms = get_terms( $r );

		if ( ! $terms ) {
			return;
		}

		$dlm_download_category = isset( $_GET['dlm_download_category'] ) ? sanitize_text_field( wp_unslash( $_GET['dlm_download_category'] ) ) : '';
		echo "<select name='dlm_download_category' id='dropdown_dlm_download_category'>";
		echo '<option value="" ' . selected( $dlm_download_category, '', false ) . '>' . esc_html__( 'Select a category', 'download-monitor' ) . '</option>';
		echo $this->walk_category_dropdown_tree( $terms, 0, $r ); //phpcs:ignore
		echo '</select>';

	}

	/**
	 * Walk the Product Categories.
	 *
	 * @access public
	 * @return string
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
				$vars = array_merge(
					$vars,
					array(
						'order_by_count' => '1',
				) );

			} elseif ( 'featured' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_featured',
					'orderby'  => 'meta_value'
				) );

			} elseif ( 'locked_download' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key' => '_members_only',
						),
					),
					'orderby'  => 'meta_value'
				) );

			} elseif ( 'redirect_only' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => '_redirect_only',
					'orderby'  => 'meta_value'
				) );
			} elseif ( 'download_title' === $vars['orderby'] ) {
				$vars['orderby'] = 'title';
			}
		}

		/**
		 * Add arguments to query before querying
		 * @hooked ( DLM_Backwards_Compatibility, orderby_compatibility )
		 * 
		 * @since 4.6.0
		 */
		do_action( 'dlm_query_args', $vars );

		return apply_filters( 'dlm_admin_sort_columns', $vars);
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
				<span class="title"><?php echo esc_html__( 'Download Monitor Data', 'download-monitor' ); ?></span><br/>
				<label for="_featured"><input type="checkbox" name="_featured" id="_featured" value="1"/><?php echo esc_html__( 'Featured download', 'download-monitor' ); ?></label>
				<label for="_members_only"><input type="checkbox" name="_members_only" id="_members_only" value="1"/><?php echo esc_html__( 'Members only', 'download-monitor' ); ?></label>
				<label for="_redirect_only"><input type="checkbox" name="_redirect_only" id="_redirect_only" value="1"/><?php echo esc_html__( 'Redirect to file', 'download-monitor' ); ?></label>
				<?php do_action( 'dlm_extra_quick_bulk_fields' ); ?>
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
			// phpcs:ignore
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
			// phpcs:ignore
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

	/**
	 * Add 'Duplicate Download' to row actions
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return array
	 */
	public function row_actions( $actions, $post ) {

		// Only for downloads
		if ( 'dlm_download' === $post->post_type && 'trash' !== $post->post_status ) {
			$actions['dlm_duplicate_download'] = '<a href="javascript:;" class="dlm-duplicate-download" rel="' . $post->ID . '" data-value="' . wp_create_nonce( 'dlm_duplicate_download_nonce' ) . '">' . __( 'Duplicate Download', 'download-monitor' ) . '</a>';
		}

		return $actions;
	}


	/**
	 * AJAX callback, duplicate download
	 */
	public function ajax_duplicate_download() {

		// Check AJAX nonce
		check_ajax_referer( 'dlm_duplicate_download_nonce', 'nonce' );

		// Download ID
		$download_id = absint( $_POST['download_id'] );

		try {

			/** @var DLM_Download $download */
			$download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );

			// get file version now because they're reset after the persist
			$file_versions = $download->get_versions();

			// set id to 0 and save it, this will create a new download
			$download->set_id( 0 );
			download_monitor()->service( 'download_repository' )->persist( $download );

			// Set Meta's
			$old_metas = get_post_meta( $download_id );
			if ( count( $old_metas ) > 0 ) {
				foreach ( $old_metas as $om_key => $om_vals ) {
					if ( ! in_array( $om_key, $this->ignored_meta ) ) {
						foreach ( $om_vals as $om_val ) {
							add_post_meta( $download->get_id(), $om_key, $om_val );
						}
					}
				}
			}

			// Set Tags
			$old_tags = wp_get_post_terms( $download_id, 'dlm_download_tag' );
			if ( is_array( $old_tags ) && count( $old_tags ) > 0 ) {
				$tag_ids = array();
				foreach ( $old_tags as $old_tag ) {
					$tag_ids[] = $old_tag->name;
				}
				wp_set_post_terms( $download->get_id(), $tag_ids, 'dlm_download_tag', false );
			}

			// Set Categories
			$old_cats = wp_get_post_terms( $download_id, 'dlm_download_category' );
			if ( is_array( $old_cats ) && count( $old_cats ) > 0 ) {
				$cat_ids = array();
				foreach ( $old_cats as $old_cat ) {
					$cat_ids[] = $old_cat->term_id;
				}
				wp_set_post_terms( $download->get_id(), $cat_ids, 'dlm_download_category', false );
			}

			// loop versions
			$vr = download_monitor()->service( 'version_repository' );
			if ( count( $file_versions ) > 0 ) {
				/** @var DLM_Download_Version $file_version */
				foreach ( $file_versions as $file_version ) {

					// set new data
					$file_version->set_id( 0 );
					$file_version->set_download_id( $download->get_id() );
					$vr->persist( $file_version );

					// Set meta values for this Version
					$old_file_metas = get_post_meta( $file_version );
					if ( is_array( $old_file_metas ) && count( $old_file_metas ) > 0 ) {
						foreach ( $old_file_metas as $omf_key => $omf_vals ) {
							if ( ! in_array( $omf_key, $this->ignored_meta ) ) {
								foreach ( $omf_vals as $omf_val ) {
									add_post_meta( $file_version->get_id(), $omf_key, $omf_val );
								}

							}
						}
					}
				}
			}

			// firing 'dlm_download_duplicator_download_duplicated' with new and old download id
			do_action( 'dlm_download_duplicator_download_duplicated', $download->get_id(), $download_id );

			// Done
			wp_send_json( array(
				'result'      => 'success',
				'success_url' => admin_url( 'edit.php?post_type=dlm_download&dlm-download-duplicator-success=1' )
			) );

		} catch ( Exception $exception ) {
			wp_send_json( array( 'result' => 'false' ) );
		}

		exit;

	}

	/**
	 * Display admin notice
	 */
	public function admin_notice() {
		echo '<div class="updated"><p>' . esc_html__( 'Download succesfully duplicated!', 'download-monitor' ) . '</p></div>' . PHP_EOL;
	}
}
