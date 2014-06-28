<?php
/**
 * DLM_Logging_List_Table class.
 *
 * @extends WP_List_Table
 */
class DLM_Logging_List_Table extends WP_List_Table {

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	function __construct(){
		global $status, $page, $wpdb;

		parent::__construct( array(
			'singular'  => 'log',
			'plural'    => 'logs',
			'ajax'      => false
		) );

		$this->filter_status = isset( $_REQUEST['filter_status'] ) ? sanitize_text_field( $_REQUEST['filter_status'] ) : '';
		$this->logs_per_page = ! empty( $_REQUEST['logs_per_page'] ) ? intval( $_REQUEST['logs_per_page'] ) : 25;
		$this->filter_month  = ! empty( $_REQUEST['filter_month'] ) ? sanitize_text_field( $_REQUEST['filter_month'] ) : '';

		if ( $this->logs_per_page < 1 )
			$this->logs_per_page = 9999999999999;
	}

	/**
	 * column_default function.
	 *
	 * @access public
	 * @param mixed $log
	 * @param mixed $column_name
	 * @return void
	 */
	function column_default( $log, $column_name ) {
		switch( $column_name ) {
			case 'status' :
				switch ( $log->download_status ) {
					case 'failed' :
						$download_status = '<span class="failed" title="' . esc_attr( $log->download_status_message ) . '">&#10082;</span>';
					break;
					case 'redirected' :
						$download_status = '<span class="redirected" title="' . esc_attr( $log->download_status_message ) . '">&#10140;</span>';
					break;
					default :
						$download_status = '<span class="completed" title="' . __( 'Download Complete', 'download-monitor' ) . '">&#10004;</span>';
					break;
				}

				return $download_status;
			break;
			case 'date' :
				return '<time title="' . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $log->download_date ) ) . '"">' . sprintf( __( '%s ago', 'download-monitor' ), human_time_diff( strtotime( $log->download_date ), current_time( 'timestamp' ) ) ) . '</time>';
			break;
			case 'download' :
				$download = new DLM_Download( $log->download_id );
				$download->set_version( $log->version_id );

				if ( ! $download->exists() ) {
					$download_string  = sprintf( __( 'Download #%d (no longer exists)', 'download-monitor' ), $log->download_id );
				} else {
					$download_string = '<a href="' . admin_url( 'post.php?post=' . $download->id . '&action=edit' ) . '">';
					$download_string .= '#' . $download->id . ' &ndash; ' . $download->get_the_title();
					$download_string .= '</a>';
				}

				if ( $log->version )
					$download_string .= ' (' . sprintf( __( 'v%s', 'download-monitor' ), $log->version ) . ')';

				return $download_string;
			break;
			case 'file' :
				$download = new DLM_Download( $log->download_id );
				$download->set_version( $log->version_id );

				if ( $download->exists() && $download->get_the_filename() )
					$download_string = '<code>' . $download->get_the_filename() . '</code>';
				else
					$download_string = '&ndash;';

				return $download_string;
			break;
			case 'user' :
				if ( $log->user_id )
					$user = get_user_by( 'id', $log->user_id );

				if ( ! isset( $user ) || ! $user ) {
					$user_string  = __( 'Non-member', 'download-monitor' );
				} else {
					$user_string  = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">';
					$user_string .= $user->user_login . ' &ndash; ';
					$user_string .= '<a href="mailto:' . $user->user_email . '">';
					$user_string .= $user->user_email;
					$user_string .= '</a>';
				}

				return $user_string;
			break;
			case 'user_ip' :
				return '<a href="http://whois.arin.net/rest/ip/' . $log->user_ip . '" target="_blank">' . $log->user_ip . '</a>';
			break;
			case 'user_ua' :
				$ua = $this->uaparser->parse( $log->user_agent );

				return $ua->toFullString;
			break;
		}
	}

	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_columns(){
		$columns = array(
			'status'     => '',
			'download'   => __( 'Download', 'download-monitor' ),
			'file'   => __( 'File', 'download-monitor' ),
			'user'       => __( 'User', 'download-monitor' ),
			'user_ip'    => __( 'IP Address', 'download-monitor' ),
			'user_ua'    => __( 'User Agent', 'download-monitor' ),
			'date'       => __( 'Date', 'download-monitor' ),
		);
		return $columns;
	}

	/**
	 * Generate the table navigation above or below the table
	 */
	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'top' == $which ) : ?>
				<div class="alignleft actions">
					<select name="filter_status">
						<option value=""><?php _e( 'Any status', 'download-monitor' ); ?></option>
						<option value="failed" <?php selected( $this->filter_status, 'failed' ); ?>><?php _e( 'Failed', 'download-monitor' ); ?></option>
						<option value="redirected" <?php selected( $this->filter_status, 'redirected' ); ?>><?php _e( 'Redirected', 'download-monitor' ); ?></option>
						<option value="completed" <?php selected( $this->filter_status, 'completed' ); ?>><?php _e( 'Completed', 'download-monitor' ); ?></option>
					</select>
					<?php
						global $wpdb, $wp_locale;

						$months = $wpdb->get_results( "
							SELECT DISTINCT YEAR( download_date ) AS year, MONTH( download_date ) AS month
							FROM {$wpdb->download_log}
							WHERE type = 'download'
							ORDER BY download_date DESC
						"
						);

						$month_count = count( $months );

						if ( $month_count && ! ( 1 == $month_count && 0 == $months[0]->month ) ) :
							$m = isset( $_GET['filter_month'] ) ? $_GET['filter_month'] : 0;
							?>
							<select name="filter_month">
								<option <?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
								<?php
										foreach ( $months as $arc_row ) {
											if ( 0 == $arc_row->year )
												continue;

											$month = zeroise( $arc_row->month, 2 );
											$year = $arc_row->year;

											printf( "<option %s value='%s'>%s</option>\n",
												selected( $m, $year . '-' . $month, false ),
												esc_attr( $year . '-' . $month ),

												sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
											);
										}
								 ?>
							</select>
						<?php endif;
					?>
					<select name="logs_per_page">
						<option value="25"><?php _e( '25 per page', 'download-monitor' ); ?></option>
						<option value="50" <?php selected( $this->logs_per_page, 50 ) ?>><?php _e( '50 per page', 'download-monitor' ); ?></option>
						<option value="100" <?php selected( $this->logs_per_page, 100 ) ?>><?php _e( '100 per page', 'download-monitor' ); ?></option>
						<option value="200" <?php selected( $this->logs_per_page, 200 ) ?>><?php _e( '200 per page', 'download-monitor' ); ?></option>
						<option value="-1" <?php selected( $this->logs_per_page, -1 ) ?>><?php _e( 'Show All', 'download-monitor' ); ?></option>
					</select>
					<input type="hidden" name="post_type" value="dlm_download" />
					<input type="hidden" name="page" value="download-monitor-logs" />
					<input type="submit" value="<?php _e( 'Filter', 'download-monitor' ); ?>" class="button" />
				</div>
			<?php endif; ?>
			<?php
					$this->extra_tablenav( $which );
					$this->pagination( $which );
			?>
			<br class="clear" />
		</div><?php
	}

	/**
	 * prepare_items function.
	 *
	 * @access public
	 * @return void
	 */
	function prepare_items() {
		global $wpdb;

		$per_page      = $this->logs_per_page;
		$current_page  = $this->get_pagenum();
		$filter_status = $this->filter_status;
		$filter_month  = date( "m", strtotime( $this->filter_month ) );
		$filter_year   = date( "Y", strtotime( $this->filter_month ) );

		// Init headers
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$query_where = " type = 'download' ";

		if ( $this->filter_status )
			$query_where .= " AND download_status = '{$filter_status}' ";

		if ( $this->filter_month )
			$query_where .= " AND download_date >= '" . date( 'Y-m-01', strtotime( $this->filter_month ) ) . "' ";

		if ( $this->filter_month )
			$query_where .= " AND download_date <= '" . date( 'Y-m-t', strtotime( $this->filter_month ) ) . "' ";

		// Total Count of Logs
		$total_items = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE {$query_where};" );

		// Get Logs
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->download_log} WHERE {$query_where} ORDER BY download_date DESC LIMIT %d, %d;",
				( $current_page - 1 ) * $per_page,
				$per_page
			)
		);

		// Pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );

		// Parser
		if ( ! class_exists( 'UAParser' ) )
				require_once( "uaparser/uaparser.php" );

		$this->uaparser = new UAParser;
	}
}
