<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * DLM_Admin_Page_List_Table class.
 *
 * @extends WP_List_Table
 */
class DLM_Admin_List_Table extends WP_List_Table {

	private $is_trash;

	/**
	 * __construct function.
	 *
	 * @access public
	 *
	 * @since  5.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'plural' => 'dlm_downloads',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return    string        Name of the primary column.
	 * @since     5.0.0
	 * @access    protected
	 *
	 */
	protected function get_primary_column_name() {
		return 'download_title';
	} // get_primary_column_name


	/**
	 * column_default function.
	 *
	 * @param  object  $item         The current item
	 * @param  string  $column_name  The current column name
	 *
	 * @since 5.0.0
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'download_title':
				/** @var DLM_Download_Version $file */
				$file = $item->get_version();

				if ( $file->get_filename() ) {
					echo '<strong><a class="dlm-file-link row-title" href="' . esc_url( admin_url( 'post.php?post=' . absint( $item->get_id() ) . '&action=edit' ) ) . '">#' . $item->get_id() . ' - ' . esc_html( $item->get_title() ) . '</a></strong>';
					echo '<a class="dlm-file-link" href="' . esc_url( $item->get_the_download_link() ) . '"><code>' . esc_html( $file->get_filename() );
					if ( $size = $item->get_version()->get_filesize_formatted() ) {
						echo ' &ndash; ' . esc_html( $size );
					}
					echo '</code></a>';
				} else {
					echo '<div class="dlm-listing-no-file"><code>No file provided</code></div>';
				}

				break;
			case 'download_cat':
				$links = array();
				if ( ! $terms = get_the_terms( $item->get_id(), 'dlm_download_category' ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					foreach ( $terms as $term ) {
						$links[] = '<a href=' . esc_url( add_query_arg( 'dlm_download_category', esc_attr( $term->slug ) ) ) . '>' . esc_html( $term->name ) . '(#' . absint( $term->term_id ) . ')</a> ';
					}
					echo wp_kses_post( implode( ', ', $links ) );
				}
				break;
			case 'download_tag':
				$terms = get_the_terms( $item->get_id(), 'dlm_download_tag' );

				if ( is_wp_error( $terms ) ) {
					echo '<span class="na">&ndash;</span>';
					break;
				}

				if ( empty( $terms ) ) {
					echo '<span class="na">&ndash;</span>';
					break;
				}

				$links = array();
				foreach ( $terms as $term ) {
					$link = get_term_link( $term, 'dlm_download_tag' );
					if ( is_wp_error( $link ) ) {
						continue;
					}
					$links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . esc_html( $term->name ) . '(#' . absint( $term->term_id ) . ')</a>';
				}
				if ( empty( $links ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					echo wp_kses_post( implode( ', ', $links ) );
				}
				break;
			case 'featured':
				if ( $item->is_featured() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'locked_download':
				/**
				 * Filters whether the download is locked or not.
				 *
				 * @param  bool          $is_locked  Whether the download is locked or not using members only. Other extensions tap into this filter to check if the download is locked.
				 * @param  DLM_Download  $item       The download object. Needs to be DLM_Download object.
				 *
				 * @moved 5.0.0 Moved here from CustomColumns.php
				 */
				$is_locked = apply_filters( 'dlm_download_is_locked', $item->is_members_only(), $item );
				if ( $is_locked ) {
					echo '<span class="yes" ' . ( $item->is_members_only() ? 'data-members_only="Yes"' : '' ) . '>' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'redirect_only':
				if ( $item->is_redirect_only() ) {
					echo '<span class="yes">' . esc_html__( 'Yes', 'download-monitor' ) . '</span>';
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'version':
				/** @var DLM_Download_Version $file */
				$file = $item->get_version();
				if ( $file && $file->get_version() ) {
					echo esc_html( $file->get_version() );
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;

			case 'shortcode':
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-shortcode" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy shortcode', 'download-monitor' ) . '</span><div class="dl-shortcode-copy"><code>[download id="' . absint( $item->get_id() ) . '"]</code><input type="text" readonly value="[download id=\'' . absint( $item->get_id() ) . '\']" class="dlm-copy-shortcode-input"></div></div></button>';
				break;
			case 'download_link':
				echo '<button class="wpchill-tooltip-button copy-dlm-shortcode button button-primary dashicons dashicons-admin-links" style="width:40px;"><div class="wpchill-tooltip-content"><span class="dlm-copy-text">' . esc_html__( 'Copy download link', 'download-monitor' ) . '</span><div class="dl-shortcode-copy">' . esc_url( $item->get_the_download_link() ) . '<input type="text" readonly value="' . esc_url( $item->get_the_download_link() ) . '" class="dlm-copy-shortcode-input"></div></div></button>';
				break;
			case 'download_count':
				echo number_format( $item->get_download_count(), 0, '.', ',' );
				break;
		}
	}

	/**
	 * The checkbox column
	 *
	 *
	 * @return string
	 *
	 * @since 5.0.0
	 */
	public function column_cb( $item ) {
		$show = current_user_can( 'edit_post', $item->get_id() );

		/**
		 * Filters whether to show the bulk edit checkbox for a dlm_download in its list table.
		 *
		 * By default, the checkbox is only shown if the current user can edit the post.
		 *
		 * @param  bool          $show  Whether to show the checkbox.
		 * @param  DLM_Download  $post  The current DLM_Download object.
		 *
		 * @since 5.0.0
		 *
		 */
		if ( apply_filters( 'wp_list_table_show_dlm_download_checkbox', $show, $item ) ) :
			?>
			<input id="cb-select-<?php
			echo absint( $item->get_id() ); ?>" type="checkbox" name="dlm_download[]" value="<?php
			echo absint( $item->get_id() ); ?>"/>
			<label for="cb-select-<?php
			echo absint( $item->get_id() ); ?>">
				<span class="screen-reader-text">
				<?php
				/* translators: %s: Post title. */
				printf( __( 'Select %s' ), _draft_or_post_title() );
				?>
				</span>
			</label>
			<div class="locked-indicator">
				<span class="locked-indicator-icon" aria-hidden="true"></span>
				<span class="screen-reader-text">
				<?php
				printf(
				/* translators: Hidden accessibility text. %s: Post title. */
					__( '&#8220;%s&#8221; is locked' ),
					_draft_or_post_title()
				);
				?>
				</span>
			</div>
		<?php
		endif;
	}

	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public function get_columns() {
		$columns = array();

		$columns['cb']              = '<input type="checkbox" />';
		$columns['download_title']  = __( 'Download Title', 'download-monitor' );
		$columns['download_cat']    = __( 'Categories', 'download-monitor' );
		$columns['version']         = __( 'Version', 'download-monitor' );
		$columns['shortcode']       = __( 'Shortcode', 'download-monitor' );
		$columns['download_link']   = __( 'Download link', 'download-monitor' );
		$columns['download_tag']    = __( 'Tags', 'download-monitor' );
		$columns['download_count']  = __( 'Download count', 'download-monitor' );
		$columns['featured']        = __( 'Featured', 'download-monitor' );
		$columns['locked_download'] = __( 'Locked', 'download-monitor' );
		$columns['redirect_only']   = __( 'Redirect only', 'download-monitor' );
		$columns['date']            = __( 'Date posted', 'download-monitor' );

		/**
		 * Filters the columns displayed in the DLM downloads list table.
		 *
		 * @param  array  $columns  The columns to be displayed in the list table.
		 *
		 * @since 5.0.0
		 *
		 */
		return apply_filters( 'dlm_admin_page_list_columns', $columns );
	}

	/**
	 * Sortable columns
	 *
	 * @return array
	 *
	 * @since 5.0.0
	 */
	public function get_sortable_columns() {
		$columns = array(
			'download_id'     => 'download_id',
			'download_title'  => 'download_title',
			'download_count'  => 'download_count',
			'featured'        => 'featured',
			'locked_download' => 'locked_download',
			'redirect_only'   => 'redirect_only',
		);

		/**
		 * Filters the sortable columns for the DLM downloads list table.
		 *
		 * @param  array  $columns  The sortable columns.
		 *
		 * @since 5.0.0
		 *
		 */
		return apply_filters( 'dlm_admin_page_list_sortable_columns', $columns );
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @param  string  $which
	 *
	 * @since 5.0.0
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-dlm-actions' );
			$this->display_extension_tab();
		}
		?>
		<div class="tablenav <?php
		echo esc_attr( $which ); ?>">

			<?php
			if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php
					$this->bulk_actions( $which ); ?>
				</div>
			<?php
			endif;

			if ( 'top' === $which ) {
				$this->extra_tablenav( $which );
			}
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since 5.0.0
	 */
	public function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen    = get_current_screen();
		$args      = array();
		$post_type = $this->screen->post_type;
		$per_page  = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );

		// Add the search form field to the query.
		if ( isset( $_GET['s'] ) ) {
			$args['s'] = sanitize_text_field( $_GET['s'] );
		}

		// Set orderby and order.
		$orderby = ! empty( $_GET["orderby"] ) ? sanitize_sql_orderby( wp_unslash( $_GET["orderby"] ) ) : "ASC";
		$order   = ! empty( $_GET["order"] ) ? sanitize_sql_orderby( wp_unslash( $_GET["order"] ) ) : "";
		// Set pages and per page.
		$paged = ! empty( $_GET["paged"] ) ? esc_sql( absint( $_GET["paged"] ) ) : "";

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		$args['orderby']        = $orderby;
		$args['order']          = $order;
		$args['paged']          = $paged;
		$args['posts_per_page'] = $per_page;

		$this->items = download_monitor()->service( 'download_repository' )->retrieve( $args, $per_page, $paged );
		$totalitems  = $wpdb->get_var( "SELECT COUNT( ID) FROM {$wpdb->posts} WHERE 1 = 1 AND post_type = 'dlm_download'" );
		$totalpages  = ceil( $totalitems / $per_page );

		$this->set_pagination_args(
			array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page"    => $per_page,
			)
		);

		$columns                           = $this->get_columns();
		$_wp_column_headers[ $screen->id ] = $columns;

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	/**
	 * Add bulk actions
	 *
	 * @return array
	 *
	 * @since 5.0.0
	 */
	protected function get_bulk_actions() {
		$actions       = array();
		$post_type_obj = get_post_type_object( $this->screen->post_type );

		if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
			if ( $this->is_trash ) {
				$actions['untrash'] = __( 'Restore' );
			} else {
				$actions['edit'] = __( 'Edit' );
			}
		}

		if ( current_user_can( $post_type_obj->cap->delete_posts ) ) {
			if ( $this->is_trash || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = __( 'Delete permanently' );
			} else {
				$actions['trash'] = __( 'Move to Trash' );
			}
		}

		/**
		 * Filters the list of bulk actions available on the DLM downloads list table.
		 *
		 * @param  array  $actions  An array of the available bulk actions.
		 *
		 * @since 5.0.0
		 *
		 */
		return apply_filters( 'dlm_admin_page_list_bulk_actions', $actions );
	}

	/**
	 * Handles the post date column output.
	 *
	 * @param  DLM_Download  $item  The current DLm_Download object.
	 *
	 * @global string        $mode  List table view mode.
	 *
	 * @since 5.0.0
	 *
	 */
	public function column_date( $item ) {
		global $mode;
		$id = $item->get_id();
		if ( '0000-00-00 00:00:00' === get_the_date( $id ) ) {
			$t_time    = __( 'Unpublished', 'download-monitor' );
			$time_diff = 0;
		} else {
			$t_time = sprintf(
			/* translators: 1: Post date, 2: Post time. */
				__( '%1$s at %2$s' ),
				/* translators: Post date format. See https://www.php.net/manual/datetime.format.php */
				get_the_time( __( 'Y/m/d' ), $id ),
				/* translators: Post time format. See https://www.php.net/manual/datetime.format.php */
				get_the_time( __( 'g:i a' ), $id )
			);

			$time      = get_post_timestamp( $id );
			$time_diff = time() - $time;
		}

		if ( 'publish' === $item->get_status() ) {
			$status = __( 'Published', 'download-monitor' );
		} elseif ( 'future' === $item->get_status() ) {
			if ( $time_diff > 0 ) {
				$status = '<strong class="error-message">' . __( 'Missed schedule', 'download-monitor' ) . '</strong>';
			} else {
				$status = __( 'Scheduled' );
			}
		} else {
			$status = __( 'Last Modified', 'download-monitor' );
		}

		/**
		 * Filters the status text of the post.
		 *
		 * @param  string        $status       The status text.
		 * @param  DLM_Download  $post         Post object.
		 * @param  string        $column_name  The column name.
		 * @param  string        $mode         The list display mode ('excerpt' or 'list').
		 *
		 * @since 5.0.0
		 *
		 */
		$status = apply_filters( 'dlm_download_date_column_status', $status, $item, 'date', $mode );

		if ( $status ) {
			echo $status . '<br />';
		}
		/**
		 * Filters the published, scheduled, or unpublished time of the post.
		 *
		 * @param  string        $t_time       The published time.
		 * @param  DLM_Download  $item         Download object.
		 * @param  string        $column_name  The column name.
		 * @param  string        $mode         The list display mode ('excerpt' or 'list').
		 *
		 * @since       5.0.0
		 */
		echo apply_filters( 'dlm_download_date_column_time', $t_time, $item, 'date', $mode );
	}

	/**
	 * Check if there are items or not.
	 *
	 * @return bool
	 *
	 * @since 5.0.0
	 */
	public function has_items() {
		return count( $this->items ) > 0;
	}

	/**
	 * Set rows
	 *
	 * @param  array  $posts
	 * @param  int    $level
	 *
	 * @since 5.0.0
	 */
	public function display_rows( $posts = array(), $level = 0 ) {
		foreach ( $this->items as $item ) {
			// Set the global download object.
			$GLOBALS['dlm_download'] = $item;
			$this->single_row( $item );
		}
	}

	/**
	 * Single row display
	 *
	 * @param  DLM_Download  $item  The current DLM_Download object.
	 *
	 * @since 5.0.0
	 */
	public function single_row( $item ) {
		?>
		<tr id="post-<?php
		echo absint( $item->get_id() ); ?>">
			<?php
			$this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param  DLM_Download  $item         Download being acted upon.
	 * @param  string        $column_name  Current column name.
	 * @param  string        $primary      Primary column name.
	 *
	 * @return string Row actions output for posts, or an empty string
	 *                if the current column is not the primary column.
	 * @since 5.0.0
	 *
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		// Restores the more descriptive, specific name for use within this method.
		$post = get_post( $item->get_id() );

		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post    = current_user_can( 'edit_post', $post->ID );
		$actions          = array();
		$title            = _draft_or_post_title();

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
				__( 'Edit' )
			);

			/**
			 * Filters whether Quick Edit should be enabled for the given post type.
			 *
			 * @param  bool    $enable     Whether to enable the Quick Edit functionality. Default true.
			 * @param  string  $post_type  Post type name.
			 *
			 * @since 5.0.0
			 *
			 */
			$quick_edit_enabled = apply_filters( 'quick_edit_enabled_for_post_type', true, $post->post_type );

			if ( $quick_edit_enabled && 'wp_block' !== $post->post_type ) {
				$actions['inline hide-if-no-js'] = sprintf(
					'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline', 'download-monitor' ), $title ) ),
					__( 'Quick&nbsp;Edit' )
				);
			}
		}

		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ( 'trash' === $post->post_status ) {
				$actions['untrash'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash', 'download-monitor' ), $title ) ),
					__( 'Restore' )
				);
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash', 'download-monitor' ), $title ) ),
					_x( 'Trash', 'verb' )
				);
			}

			if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID, '', true ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently', 'download-monitor' ), $title ) ),
					__( 'Delete Permanently', 'download-monitor' )
				);
			}
		}

		if ( is_post_type_viewable( $post_type_object ) ) {
			if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ), true ) ) {
				if ( $can_edit_post ) {
					$preview_link    = get_preview_post_link( $post );
					$actions['view'] = sprintf(
						'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
						esc_url( $preview_link ),
						/* translators: %s: Post title. */
						esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'download-monitor' ), $title ) ),
						__( 'Preview' )
					);
				}
			} elseif ( 'trash' !== $post->post_status ) {
				$actions['view'] = sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					get_permalink( $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'download-monitor' ), $title ) ),
					__( 'View' )
				);
			}
		}

		if ( 'wp_block' === $post->post_type ) {
			$actions['export'] = sprintf(
				'<button type="button" class="wp-list-reusable-blocks__export button-link" data-id="%s" aria-label="%s">%s</button>',
				$post->ID,
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Export &#8220;%s&#8221; as JSON', 'download-monitor' ), $title ) ),
				__( 'Export as JSON' )
			);
		}

		if ( 'trash' !== $post->post_status ) {
			$actions['dlm_duplicate_download'] = '<a href="javascript:;" class="dlm-duplicate-download" rel="' . $post->ID . '" data-value="' . wp_create_nonce( 'dlm_duplicate_download_nonce' ) . '">' . __( 'Duplicate Download', 'download-monitor' ) . '</a>';
		}

		if ( is_post_type_hierarchical( $post->post_type ) ) {
			/**
			 * Filters the array of row action links on the Pages list table.
			 *
			 * The filter is evaluated only for hierarchical post types.
			 *
			 * @param  string[]      $actions  An array of row action links. Defaults are
			 *                                 'Edit', 'Quick Edit', 'Restore', 'Trash',
			 *                                 'Delete Permanently', 'Preview', and 'View'.
			 * @param  WP_Post       $post     The post object.
			 * @param  DLM_Download  $item     The download object.
			 *
			 * @since 5.0.0
			 *
			 */
			$actions = apply_filters( 'page_row_actions', $actions, $post, $item );
		} else {
			/**
			 * Filters the array of row action links on the Posts list table.
			 *
			 * The filter is evaluated only for non-hierarchical post types.
			 *
			 * @param  string[]      $actions  An array of row action links. Defaults are
			 *                                 'Edit', 'Quick Edit', 'Restore', 'Trash',
			 *                                 'Delete Permanently', 'Preview', and 'View'.
			 * @param  WP_Post       $post     The post object.
			 * @param  DLM_Download  $item     The download object.
			 *
			 * @since 5.0.0
			 *
			 */
			$actions = apply_filters( 'post_row_actions', $actions, $post, $item );
		}

		return $this->row_actions( $actions );
	}

	/**
	 * No items display
	 *
	 * @since 5.0.0
	 */
	public function no_items() {
		global $wp_list_table;
		$wp_list_table = new DLM_Empty_Table();
	}

	/**
	 * Display the extension tab.
	 *
	 * @since 5.0.0
	 */
	public function display_extension_tab() {
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			$tabs = array(
				'downloads'       => array(
					'name'     => __( 'Downloads', 'download-monitor' ),
					'url'      => admin_url( 'edit.php?post_type=dlm_download' ),
					'priority' => '1',
				),
				'suggest_feature' => array(
					'name'     => esc_html__( 'Suggest a feature',
					                          'download-monitor' ),
					'icon'     => 'dashicons-external',
					'url'      => 'https://forms.gle/3igARBBzrbp6M8Fc7',
					'target'   => '_blank',
					'priority' => '60',
				),
			);

			if ( current_user_can( 'install_plugins' ) ) {
				$tabs['extensions'] = array(
					'name'     => esc_html__( 'Extensions',
					                          'download-monitor' ),
					'url'      => admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ),
					'priority' => '5',
				);
			}

			/**
			 * Hook for DLM CPT table view tabs
			 *
			 * @hooked DLM_Admin_Extensions dlm_cpt_tabs()
			 *
			 * @moved 5.0.0 Moved here from CustomColumns.php
			 */
			$tabs = apply_filters( 'dlm_add_edit_tabs', $tabs );

			uasort( $tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );

			DLM_Admin_Helper::dlm_tab_navigation( $tabs, 'downloads' );
			?>
		</h2>
		<br/>
		<?php
	}
}
