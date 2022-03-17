<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    
    
}
if ( ! class_exists( 'WP_Posts_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );
    
    
}

class DLM_Custom_Test extends WP_Posts_List_Table{
    public function __construct( $args = array() ) {
       // add_filter( 'the_title' , array($this, 'test'), 99, 2);
    parent::__construct();
    
    }

    public function test( $pt, $pi){
        $pt = $pi . ' - ' . $pt;
        return $pt;
    }

}