<?php

/**
 * DLM_Logging_List_Table class.
 *
 * @extends WP_List_Table
 */
class DLM_Logging_List_Table extends WP_List_Table {

	private $filter_status = '';
	private $logs_per_page = 25;
	private $filter_month = '';
	private $filter_user = 0;

	/** @var bool $display_delete_message */
	private $display_delete_message = false;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		global $status, $page, $wpdb;

		parent::__construct( array(
			'singular' => 'log',
			'plural'   => 'logs',
			'ajax'     => false
		) );

		$this->filter_status = isset( $_REQUEST['filter_status'] ) ? sanitize_text_field( wp_unslash($_REQUEST['filter_status']) ) : '';
		$this->logs_per_page = ! empty( $_REQUEST['logs_per_page'] ) ? intval( $_REQUEST['logs_per_page'] ) : 25;
		$this->filter_month  = ! empty( $_REQUEST['filter_month'] ) ? sanitize_text_field( wp_unslash($_REQUEST['filter_month']) ) : '';
		$this->filter_user   = ! empty( $_REQUEST['filter_user'] ) ? intval( $_REQUEST['filter_user'] ) : 0;

		if ( $this->logs_per_page < 1 ) {
			$this->logs_per_page = 9999999999999;
		}
	}

	/**
	 * The checkbox column
	 *
	 * @param DLM_Log_Item $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="log[]" value="%s" />', $item->get_id()
		);
	}

	/**
	 * Add bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'download-monitor' )
		);

		return $actions;
	}

	/**
	 * column_default function.
	 *
	 * @access public
	 *
	 * @param DLM_Log_Item $log
	 * @param mixed $column_name
	 *
	 * @return string
	 */
	public function column_default( $log, $column_name ) {
	    switch ( $column_name ) {
			case 'status' :
				switch ( $log->get_download_status() ) {
					case 'failed' :
						$download_status = '<span class="failed" title="' . esc_attr( $log->get_download_status_message() ) . '">&nbsp;</span>';
						break;
					case 'redirected' :
						$download_status = '<span class="redirected" title="' . esc_attr( $log->get_download_status_message() ) . '">&nbsp;</span>';
						break;
					default :
						$download_status = '<span class="completed" title="' . __( 'Download Complete', 'download-monitor' ) . '">&nbsp;</span>';
						break;
				}

				return $download_status;
				break;
			case 'date' :
			    $time_str = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $log->get_download_date()->format( 'U' ) );
				return '<time title="' . $time_str . '"">' . sprintf( __( '%s ago', 'download-monitor' ), human_time_diff( $log->get_download_date()->format( 'U' ), current_time( 'timestamp' ) ) ) . '</time>';
				break;
			case 'download' :
				try {
					/** @var DLM_Download $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $log->get_download_id() );

					try {
						$version_obj = download_monitor()->service( 'version_repository' )->retrieve_single( $log->get_version_id() );
                        $download->set_version( $version_obj );
                    } catch ( Exception $e ) {

					}


					$download_string = '<a href="' . admin_url( 'post.php?post=' . absint( $download->get_id() ) . '&action=edit' ) . '">';
					$download_string .= '#' . $download->get_id() . ' &ndash; ' . esc_html( $download->get_title() );
					$download_string .= '</a>';

					if ( $log->get_version() ) {
						if ( $download->version_exists( $log->get_version_id() ) ) {
							$download_string .= sprintf( __( ' (v%s)', 'download-monitor' ), $log->get_version() );
						} else {
							$download_string .= sprintf( __( ' (v%s no longer exists)', 'download-monitor' ), $log->get_version() );
						}
					}
				} catch ( Exception $e ) {
					$download_string = sprintf( __( 'Download #%d (no longer exists)', 'download-monitor' ), $log->get_download_id() );
				}

				return $download_string;
				break;
			case 'file' :
				try {
					/** @var DLM_Download $download */
					$download = download_monitor()->service( 'download_repository' )->retrieve_single( $log->get_download_id() );

					try {
						$version_obj = download_monitor()->service( 'version_repository' )->retrieve_single( $log->get_version_id() );
						$download->set_version( $version_obj );
					} catch ( Exception $e ) {

					}

					if ( ! $download->version_exists( $log->get_version_id() ) || ! $download->get_version()->get_filename() ) {
						throw new Exception( "No version found" );
					}

					$download_string = '<code>' . $download->get_version()->get_filename() . '</code>';
				} catch ( Exception $e ) {
					$download_string = '&ndash;';
				}

				return $download_string;
				break;
			case 'user' :
				if ( $log->get_user_id() ) {
					$user = get_user_by( 'id', $log->get_user_id() );
				}

				if ( ! isset( $user ) || ! $user ) {
					$user_string = __( 'Non-member', 'download-monitor' );
				} else {
					$user_string = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">';
					$user_string .= $user->user_login . ' &ndash; ';
					$user_string .= '<a href="mailto:' . $user->user_email . '">';
					$user_string .= $user->user_email;
					$user_string .= '</a>';
				}

				return $user_string;
				break;
			case 'user_ip' :
				return '<a href="http://whois.arin.net/rest/ip/' . $log->get_user_ip() . '" target="_blank">' . $log->get_user_ip() . '</a>';
				break;
		}
	}

	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '',
			'status'   => '',
			'download' => __( 'Download', 'download-monitor' ),
			'file'     => __( 'File', 'download-monitor' ),
			'user'     => __( 'User', 'download-monitor' ),
			'user_ip'  => __( 'IP Address', 'download-monitor' ),
			'date'     => __( 'Date', 'download-monitor' ),
		);

		return $columns;
	}

	/**
     * Sortable columns
     *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'download' => array( 'download_id', false ),
			'file'     => array( 'version_id', false ),
			'user'     => array( 'user_id', false ),
			'user_ip'  => array( 'user_ip', false ),
			'date'     => array( 'download_date', false )
		);
	}

	/**
	 * Generate the table navigation above or below the table
	 */
	public function display_tablenav( $which ) {

		// output nonce
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		// display 'delete' success message
		if ( 'top' == $which && true === $this->display_delete_message ) {
			?>
            <div id="message" class="updated notice notice-success">
                <p><?php echo esc_html__( 'Log entries deleted', 'download-monitor' ); ?></p>
            </div>
			<?php
		}

		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
            </div>

			<?php if ( 'top' == $which ) { ?>

                <div class="alignleft actions">

                    <select name="filter_status">
                        <option value=""><?php echo esc_html__( 'Any status', 'download-monitor' ); ?></option>
                        <option
                                value="failed" <?php selected( $this->filter_status, 'failed' ); ?>><?php echo esc_html__( 'Failed', 'download-monitor' ); ?></option>
                        <option
                                value="redirected" <?php selected( $this->filter_status, 'redirected' ); ?>><?php echo esc_html__( 'Redirected', 'download-monitor' ); ?></option>
                        <option
                                value="completed" <?php selected( $this->filter_status, 'completed' ); ?>><?php echo esc_html__( 'Completed', 'download-monitor' ); ?></option>
                    </select>
					<?php
					global $wpdb, $wp_locale;

					$months = $wpdb->get_results( "
							SELECT DISTINCT YEAR( download_date ) AS year, MONTH( download_date ) AS month
							FROM {$wpdb->download_log}
							ORDER BY download_date DESC
						"
					);

					$month_count = count( $months );

					if ( $month_count && ! ( 1 == $month_count && 0 == $months[0]->month ) ) {
						$m = isset( $_GET['filter_month'] ) ? sanitize_text_field( wp_unslash($_GET['filter_month']) ) : 0;
						?>
                        <select name="filter_month">
                            <option <?php selected( $m, 0 ); ?> value='0'><?php echo esc_html__( 'Show all dates' ); ?></option>
							<?php
							foreach ( $months as $arc_row ) {
								if ( 0 == $arc_row->year ) {
									continue;
								}

								$month = zeroise( $arc_row->month, 2 );
								$year  = $arc_row->year;

								printf( "<option %s value='%s'>%s</option>\n",
									selected( $m, $year . '-' . $month, false ),
									esc_attr( $year . '-' . $month ),

									sprintf( esc_html__( '%1$s %2$d' ), esc_html($wp_locale->get_month( $month )), esc_html($year) )
								);
							}
							?>
                        </select>
					<?php } ?>
                    <select name="filter_user">
                        <option value="0"><?php echo esc_html__( 'Select a User', 'download-monitor' ); ?></option>
						<?php
						$users = $wpdb->get_results( "
							SELECT DISTINCT user_id
							FROM {$wpdb->download_log}" );

						foreach ( $users as $a_user ) {
							if ( $a_user->user_id == '0' ) {
								continue;
							}
							$the_user = get_userdata( $a_user->user_id );

							//skip users that no longer exist
							if( ! $the_user ){
								continue;
							}

							?>
                        <option value="<?php echo esc_attr( $a_user->user_id ); ?>" <?php echo ( $this->filter_user == $a_user->user_id ) ? 'selected="selected"' : ''; ?>>
							<?php echo esc_html( $the_user->display_name ); ?>
                            </option><?php
						}
						?>
                    </select>
                    <select name="logs_per_page">
                        <option value="25"><?php echo esc_html__( '25 per page', 'download-monitor' ); ?></option>
                        <option
                                value="50" <?php selected( $this->logs_per_page, 50 ) ?>><?php echo esc_html__( '50 per page', 'download-monitor' ); ?></option>
                        <option
                                value="100" <?php selected( $this->logs_per_page, 100 ) ?>><?php echo esc_html__( '100 per page', 'download-monitor' ); ?></option>
                        <option
                                value="200" <?php selected( $this->logs_per_page, 200 ) ?>><?php echo esc_html__( '200 per page', 'download-monitor' ); ?></option>
                        <option
                                value="-1" <?php selected( $this->logs_per_page, - 1 ) ?>><?php echo esc_html__( 'Show All', 'download-monitor' ); ?></option>
                    </select>
                    <input type="hidden" name="post_type" value="dlm_download"/>
                    <input type="hidden" name="page" value="download-monitor-logs"/>
                    <input type="submit" value="<?php echo esc_html__( 'Filter', 'download-monitor' ); ?>" class="button"/>
                </div>
				<?php
			}
			?>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
            <br class="clear"/>
        </div>
		<?php
	}

	/**
	 * prepare_items function.
	 *
	 * @access public
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		// process bulk action
		$this->process_bulk_action();

		// Init headers
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$per_page     = absint( $this->logs_per_page );
		$current_page = absint( $this->get_pagenum() );

		// setup filters
		$filters = array();

		// filter status
		if ( $this->filter_status ) {
			$filters[] = array(
				'key'   => 'download_status',
				'value' => $this->filter_status
			);
		}

		// filter month
		if ( $this->filter_month ) {
			$filters[] = array(
				'key'      => 'download_date',
				'value'    => date( 'Y-m-01', strtotime( $this->filter_month ) ),
				'operator' => '>='
			);

			$filters[] = array(
				'key'      => 'download_date',
				'value'    => date( 'Y-m-t', strtotime( $this->filter_month ) ),
				'operator' => '<='
			);
		}

		// filter on user
		if ( $this->filter_user > 0 ) {
			$filters[] = array(
				'key'   => 'user_id',
				'value' => $this->filter_user
			);
		}

		// check for order
		$order_by = ( ! empty( $_GET['orderby'] ) ) ? sanitize_sql_orderby( wp_unslash($_GET['orderby']) ) : 'download_date';
		$order    = 'DESC';
		// phpcs:ignore
		if ( isset( $_GET['order'] ) && 'ASC' == strtoupper( $_GET['order'] ) ) {
			$order = 'ASC';
		}

		/** @var DLM_WordPress_Log_Item_Repository $log_item_repository */
		$log_item_repository = download_monitor()->service( 'log_item_repository' );

		$total_items = $log_item_repository->num_rows( $filters );
		$this->items = $log_item_repository->retrieve( $filters, $per_page, ( ( $current_page - 1 ) * $per_page ), $order_by, $order );

		// Pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => absint( $this->logs_per_page ),
			'total_pages' => ( ( $total_items > 0 ) ? ceil( $total_items / absint( $this->logs_per_page ) ) : 1 )
		) );
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// check nonce
			// phpcs:ignore
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'process_bulk_action() nonce check failed', 'download-monitor' ) );
			}

			// check capability
			if ( ! current_user_can( 'dlm_manage_logs' ) ) {
				wp_die( esc_html__( "You're not allowed to delete logs!", 'download-monitor' ) );
			}

			if ( empty( $_POST['log'] ) ) {
				wp_die( esc_html__( "We don't have logs to delete", 'download-monitor' ) );
			}

			// check
			if ( count( $_POST['log'] ) > 0 ) {

				// delete the posted logs
				// phpcs:ignore
				foreach ( $_POST['log'] as $log_id ) {
					download_monitor()->service( 'log_item_repository' )->delete( absint($log_id) );
				}

				// display delete message
				$this->display_delete_message = true;

			}

		}

	}

}
