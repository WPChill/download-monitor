<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// Include the WP_List_Table class
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DLM_Other_Downloads_Table extends WP_List_Table {
	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Other Download Path', 'reviveso-pro' ),
				'plural'   => __( 'Other Downloads Path', 'reviveso-pro' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No approved directory URLs found.', 'woocommerce' );
	}

	/**
	 * Displays the list of views available on this table.
	 */
	public function render_views() {

		$enabled_count  = $this->get_count_enabled();
		$disabled_count = $this->get_count_disabled();
		$all_count      = $enabled_count + $disabled_count;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_view = isset( $_REQUEST['view'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['view'] ) ) : 'all';

		$all_url   = esc_url( add_query_arg( 'view', 'all', $this->get_base_url() ) );
		$all_class = 'all' === $selected_view ? 'class="current"' : '';
		$all_text  = sprintf(
			/* translators: %s is the count of approved directory list entries. */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$all_count,
				'Approved product download directory views',
				'woocommerce'
			),
			$all_count
		);

		$enabled_url   = esc_url( add_query_arg( 'view', 'enabled', $this->get_base_url() ) );
		$enabled_class = 'enabled' === $selected_view ? 'class="current"' : '';
		$enabled_text  = sprintf(
			/* translators: %s is the count of enabled approved directory list entries. */
			_nx(
				'Enabled <span class="count">(%s)</span>',
				'Enabled <span class="count">(%s)</span>',
				$enabled_count,
				'Approved product download directory views',
				'woocommerce'
			),
			$enabled_count
		);

		$disabled_url   = esc_url( add_query_arg( 'view', 'disabled', $this->get_base_url() ) );
		$disabled_class = 'disabled' === $selected_view ? 'class="current"' : '';
		$disabled_text  = sprintf(
			/* translators: %s is the count of disabled directory list entries. */
			_nx(
				'Disabled <span class="count">(%s)</span>',
				'Disabled <span class="count">(%s)</span>',
				$disabled_count,
				'Approved product download directory views',
				'woocommerce'
			),
			$disabled_count
		);

		$views = array(
			'all'      => "<a href='{$all_url}' {$all_class}>{$all_text}</a>",
			'enabled'  => "<a href='{$enabled_url}' {$enabled_class}>{$enabled_text}</a>",
			'disabled' => "<a href='{$disabled_url}' {$disabled_class}>{$disabled_text}</a>",
		);

		$this->screen->render_screen_reader_content( 'heading_views' );

		echo '<ul class="subsubsub list-table-filters">';
		foreach ( $views as $slug => $view ) {
			$views[ $slug ] = "<li class='{$slug}'>{$view}";
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ' | </li>', $views ) . "</li>\n";
		echo '</ul>';
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		return array(
			'cb'    => '<input type="checkbox" />',
			'path_val' => __( 'URL', 'woocommerce' ),
			'enabled' => __( 'Enabled','woocommerce' ),
		);
	}

	/**
	 * Checklist column, used for selecting items for processing by a bulk action.
	 *
	 * @param StoredUrl $item The approved directory information for the current row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
        
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', esc_attr( $this->_args['singular'] ), esc_attr( $item['id'] ) );
	}

	/**
	 * URL column.
	 *
	 * @param StoredUrl $item The approved directory information for the current row.
	 *
	 * @return string
	 */
	public function column_path_val( $item ) {

		$id      = (int) $item['id'] ;
		$url     = esc_html( $item['path_val'] );
		$enabled = 'enabled' == $item['enabled'];

		$edit_url            = esc_url( $this->get_action_url( 'edit', $id ) );
		$enable_disable_url  = esc_url( $enabled ? $this->get_action_url( 'disable', $id ) : $this->get_action_url( 'enable', $id ) );
		$enable_disable_text = esc_html( $enabled ? __( 'Disable', 'woocommerce' ) : __( 'Enable', 'woocommerce' ) );
		$delete_url          = esc_url( $this->get_action_url( 'delete', $id ) );
		$edit_link           = "<a href='{$edit_url}'>" . esc_html_x( 'Edit', 'Product downloads list', 'woocommerce' ) . '</a>';
		$enable_disable_link = "<a href='{$enable_disable_url}'>{$enable_disable_text}</a>";
		$delete_link         = "<a href='{$delete_url}' class='submitdelete wc-confirm-delete'>" . esc_html_x( 'Delete permanently', 'Product downloads list', 'woocommerce' ) . '</a>';
		$url_link            = "<a href='{$edit_url}'>{$url}</a>";

		return "
			<div><strong>{$url_link}</strong></div>
			<div class='row-actions'>
				<span class='id'>ID: {$id}</span> |
				<span class='edit'>{$edit_link}</span> |
				<span class='enable-disable'>{$enable_disable_link}</span> |
				<span class='delete'><a class='submitdelete'>{$delete_link}</a></span>
			</div>
		";
	}

	/**
	 * Rule-is-enabled column.
	 *
	 * @param StoredUrl $item The approved directory information for the current row.
	 *
	 * @return string
	 */
	public function column_enabled( $item ): string {
        
		return 'enabled' == $item['enabled']
			? '<mark class="yes" title="' . esc_html__( 'Enabled', 'woocommerce' ) . '"><span class="dashicons dashicons-yes"></span></mark>'
			: '<mark class="no" title="' . esc_html__( 'Disabled', 'woocommerce' ) . '">&ndash;</mark>';
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'enable'  => __( 'Enable rule', 'woocommerce' ),
			'disable' => __( 'Disable rule', 'woocommerce' ),
			'delete'  => __( 'Delete permanently', 'woocommerce' ),
		);
	}

	/**
	 * Builds an action URL (ie, to edit or delete a row).
	 *
	 * @param string $action       The action to be created.
	 * @param int    $id           The ID that is the subject of the action.
	 * @param string $nonce_action Action used to add a nonce to the URL.
	 *
	 * @return string
	 */
	public function get_action_url( string $action, int $id, string $nonce_action = 'modify_approved_directories' ): string {
		return add_query_arg(
			array(
				'check'  => wp_create_nonce( $nonce_action ),
				'action' => $action,
				'url'    => $id,
			),
			$this->get_base_url()
		);
	}

	/**
	 * Supplies the 'base' admin URL for this admin table.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		return add_query_arg(
			array(
				'post_type'    => 'dlm_download',
                'page' => 'download-monitor-settings',
				'tab'     => 'advanced',
				'section' => 'download_path',
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Generate the table navigation above or below the table.
	 * Included to remove extra nonce input.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {

		echo '<div class="tablenav ' . esc_attr( $which ) . '">';

		if ( $this->has_items() ) {
			echo '<div class="alignleft actions bulkactions">';
			$this->bulk_actions( $which );

			if ( true ) {
				echo '<a href="' . esc_url( $this->get_action_url( 'enable-all', 0 ) ) . '" class="wp-core-ui button">' . esc_html_x( 'Enable All', 'Approved product download directories', 'woocommerce' ) . '</a> ';
			}

			if ( true ) {
				echo '<a href="' . esc_url( $this->get_action_url( 'disable-all', 0 ) ) . '" class="wp-core-ui button">' . esc_html_x( 'Disable All', 'Approved product download directories', 'woocommerce' ) . '</a>';
			}

			echo '</div>';
		}

		$this->pagination( $which );
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$current_page = $this->get_pagenum();
		$per_page     = $this->get_items_per_page( 'edit_approved_directories_per_page' );

		switch ( $_REQUEST['view'] ?? '' ) {
			case 'enabled':
				$enabled = 'enabled';
				break;

			case 'disabled':
				$enabled = 'disabled';
				break;

			default:
				$enabled = 'all';
				break;
		}

        $paths = get_option( 'dlm_downloads_path' );

        if( ! is_array( $paths ) ){
            $paths = array( array( 'id' => 1, 'path_val' => $paths, 'enabled' => true ) );
            update_option( 'dlm_downloads_path', $paths );
        }

        if( 'all' != $enabled ){
            foreach( $paths as $path ){
                if( 'enabled' == $enabled && $path['enabled'] ){
                    $res[] = $path;
                    continue;
                }
                if( 'disabled' == $enabled && ! $path['enabled'] ){
                    $res[] = $path;
                    continue;
                }
            }
        }else{
            $res = $paths;
        }
        $total_paths = count( $res );
        $total_pages = ceil( $total_paths / $per_page );

        if( count( $res ) > $per_page ){
            $res = array_chunk( $res, $per_page )[$current_page];
        }

		$this->items = $res;

		// Set the pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_paths,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			)
		);
	}

    private function get_count_enabled(){
        $paths = get_option( 'dlm_downloads_path' );
        $res   = array();
        if( ! is_array( $paths ) ){
            $paths = array( array( 'id' => 1, 'path_val' => $paths, 'enabled' => true ) );
            update_option( 'dlm_downloads_path', $paths );
        }
        foreach( $paths as $path ){
            if( $path['enabled'] ){
                $res[] = $path;
            }
        }

        return count( $res );
    }

    
    private function get_count_disabled(){
        $paths = get_option( 'dlm_downloads_path' );
        $res   = array();
        if( ! is_array( $paths ) ){
            $paths = array( array( 'id' => 1, 'path_val' => $paths, 'enabled' => true ) );
            update_option( 'dlm_downloads_path', $paths );
        }
        foreach( $paths as $path ){
            if( ! $path['enabled'] ){
                $res[] = $path;
            }
        }

        return count( $res );
    }
}
