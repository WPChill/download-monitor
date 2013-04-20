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
        global $status, $page;

        parent::__construct( array(
            'singular'  => 'log',
            'plural'    => 'logs',
            'ajax'      => false
        ) );
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
	        			$download_status = '<span class="completed" title="' . __( 'Download Complete', 'download_monitor' ) . '">&#10004;</span>';
	        		break;
        		}

        		return $download_status;
        	break;
        	case 'date' :
        		return '<time title="' . date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $log->download_date ) ) . '"">' . sprintf( __( '%s ago', 'download_monitor' ), human_time_diff( strtotime( $log->download_date ) ) ) . '</time>';
        	break;
        	case 'download' :
        		$download = new DLM_Download( $log->download_id );
        		$download->set_version( $log->version_id );

        		if ( ! $download->exists() ) {
	        		$download_string  = sprintf( __( 'Download #%d (no longer exists)', 'download_monitor' ), $log->download_id );
        		} else {
        			$download_string = '<a href="' . admin_url( 'post.php?post=' . $download->id . '&action=edit' ) . '">';
	        		$download_string .= '#' . $download->id . ' &ndash; ' . $download->get_the_title();
	        		$download_string .= '</a>';
        		}

        		if ( $log->version )
        			$download_string .= ' (' . sprintf( __( 'v%s', 'download_monitor' ), $log->version ) . ')';

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
	        		$user_string  = __( 'Non-member', 'download_monitor' );
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
        		return '<a href="http://whois.arin.net/rest/net/NET-' . $log->user_ip . '" target="_blank">' . $log->user_ip . '</a>';
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
        	'download'   => __( 'Download', 'download_monitor' ),
        	'file'   => __( 'File', 'download_monitor' ),
        	'user'       => __( 'User', 'download_monitor' ),
            'user_ip'    => __( 'IP Address', 'download_monitor' ),
            'user_ua'    => __( 'User Agent', 'download_monitor' ),
            'date'       => __( 'Date', 'download_monitor' ),
        );
        return $columns;
    }

    /**
     * prepare_items function.
     *
     * @access public
     * @return void
     */
    function prepare_items() {
        global $wpdb;

        $per_page     = 40;
        $current_page = $this->get_pagenum();

        // Init headers
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        // Total Count of Logs
        $total_items = $wpdb->get_var(
	        "SELECT COUNT( ID ) FROM {$wpdb->download_log} WHERE type = 'download'"
        );

        // Get Logs
        $this->items = $wpdb->get_results(
	        $wpdb->prepare(
	        	"SELECT * FROM {$wpdb->download_log}
	        	WHERE type = 'download'
	        	ORDER BY download_date DESC
	        	LIMIT %d, %d",
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