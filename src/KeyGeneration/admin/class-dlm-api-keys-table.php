<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class that handles the API Keys table
 *
 * @package DLM_Key_Generation
 */
class DLM_API_Keys_Table extends WP_List_Table {
	private $items_per_page = 20;

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
				'singular' => 'api_key',
				'plural'   => 'api_keys',
				'ajax'     => false,
			)
		);

		$this->items_per_page = ! empty( $_REQUEST['items_per_page'] )
			? intval( $_REQUEST['items_per_page'] )
			: 20;

		if ( $this->items_per_page < 1 ) {
			$this->items_per_page = 9999999999999;
		}

		add_action( 'admin_init', array( $this, 'catch_request' ), 1 );
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
		return 'username';
	} // get_primary_column_name

	/**
	 * column_default function.
	 *
	 * @since 5.0.0
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'username':
				$user = $item->get_user();
				echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . absint( $user->ID ) ) ) . '" target="_blank">' . esc_html( $user->user_login ) . '</a>';
				break;
			case 'public_key':
				echo esc_html( $item->get_public_key() );
				break;
			case 'token':
				echo esc_html( $item->get_token() );
				break;
			case 'secret_key':
				echo esc_html( $item->get_secret_key() );
				break;
			case 'create_date':
				echo esc_html( $item->get_creation_date() );
				break;
			default:
				/**
				 * Fires for each custom column in the API Keys table.
				 *
				 * @param  array  $config  The current API Key configuration.
				 */
				do_action( 'dlm_api_keys_column_' . $column_name, $item );
				break;
		}
	}

	/**
	 * The checkbox column
	 *
	 *
	 * @return string
	 * @since 5.0.0
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="api_key_id[]" value="%s" />', absint( $item->get_id() )
		);
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
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'username'    => __( 'Username', 'download-monitor' ),
			'public_key'  => __( 'Public key', 'download-monitor' ),
			'token'       => __( 'Token', 'download-monitor' ),
			'secret_key'  => __( 'Secret key', 'download-monitor' ),
			'create_date' => __( 'Creation date', 'download-monitor' ),
		);

		return apply_filters( 'dlm_api_keys_list_columns', $columns );
	}

	/**
	 * Sortable columns
	 *
	 * @return array
	 *
	 * @since 5.0.0
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'dlm_api_keys_sortable_columns',
			array()
		);
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @param  string  $which
	 *
	 * @since 5.0.0
	 */
	protected function display_tablenav( $which ) {
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
		$screen = get_current_screen();

		// Set the main SQL query.
		$query = "SELECT * FROM {$wpdb->dlm_api_keys} api_keys LEFT JOIN {$wpdb->users} users ON api_keys.user_id = users.ID WHERE 1 = 1";

		// Add the search form field to the query.
		if ( isset( $_GET['s'] ) ) {
			$search_field = sanitize_text_field( $_GET['s'] );
			$query        .= " AND  users.user_login LIKE '%{$search_field}%' ";
		}

		// Set orderby and order.
		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) ) : 'api_keys.create_date';
		$order   = ! empty( $_GET['order'] ) ? sanitize_sql_orderby( wp_unslash( $_GET['order'] ) ) : 'DESC';

		if ( ! empty( $orderby ) & ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		// Set pages and per page.
		$perpage = 20;
		$paged   = ! empty( $_GET['paged'] ) ? esc_sql( absint( $_GET['paged'] ) ) : '';

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query  .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}

		$items = $wpdb->get_results( $query );

		foreach ( $items as $item ) {
			$key           = new DLM_API_Key( $item );
			$this->items[] = $key;
		}

		if ( ! empty( $this->items ) ) {
			$totalitems = count( $this->items );
			$totalpages = ceil( $totalitems / $perpage );
		} else {
			$totalitems = 0;
			$totalpages = 0;
		}

		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page'    => $perpage,
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
		$actions = array();

		return apply_filters( 'dlm_api_keys_bulk_actions', $actions );
	}


	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param  string  $which
	 *
	 * @since 5.0.0
	 *
	 */
	protected function extra_tablenav( $which ) {
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 * @since 5.0.0
	 *
	 */
	protected function get_default_primary_column_name() {
		return 'username';
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param  DLM_API_Key  $item         API Key being acted upon.
	 * @param  string       $column_name  Current column name.
	 * @param  string       $primary      Primary column name.
	 *
	 * @return string Row actions output for API Keys, or an empty string
	 *                if the current column is not the primary column.
	 * @since 5.0.0
	 *
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions                       = array();
		$actions['dlm_regenerate_key'] = '<a href="javascript:;" class="dlm-regenerate-key" data-user-id="' . absint( $item->get_user_id() ) . '">' . __( 'Regenerate key', 'download-monitor' ) . '</a>';
		$actions['dlm_revoke_key']     = '<a href="javascript:;" class="dlm-revoke-key" data-user-id="' . absint( $item->get_user_id() ) . '" style="color:red;">' . __( 'Revoke key', 'download-monitor' ) . '</a>';

		return $this->row_actions( $actions );
	}
}
