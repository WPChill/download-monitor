<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Downloads_Path_Helper class.
 *
 * @since 5.0.0
 */
class DLM_Downloads_Path_Helper {

    /**
     * Saves the download paths if not allready existing either for single site or multisite setup.
     *
     * @param array $path string of download path.
     * @since 5.0.0
    */
    public static function save_unique_path( $path ){
        $saved_paths = self::get_all_paths();
        $add_file = true;

        foreach ( $saved_paths as $save_path ){
            if( $path == $save_path['path_val'] ){
                $add_file = false;
                break;
            }
        } 
        if( $add_file ){
            $lastkey = array_key_last( $saved_paths );
            $saved_paths[] = array( 'id' => absint( $saved_paths[$lastkey]['id'] ) + 1, 'path_val' => $path, 'enabled' => true );
            if( is_multisite() ){
                $settings = get_site_option( 'dlm_network_settings', array() );
                if( isset( $settings['dlm_downloads_path'] ) ){
                    $settings['dlm_downloads_path'] = $saved_paths;
                }else{
                    $settings = array( 'dlm_downloads_path' => $saved_paths, 'dlm_crossite_file_browse' => '0', 'dlm_turn_off_file_browser' => '0' );
                }
                
                update_site_option( 'dlm_network_settings', $settings );
            }else{
                
                update_option( 'dlm_downloads_path', $saved_paths );
            }
        }
    }

    /**
     * Saves the download paths either for single site or multisite setup.
     *
     * @param array $paths Array of download paths.
     * @since 5.0.0
    */
    public static function save_paths( $paths ){

        if( is_multisite() ){
            $settings = get_site_option( 'dlm_network_settings', array() );
            if( isset( $settings['dlm_downloads_path'] ) ){
                $settings['dlm_downloads_path'] = $paths;
            }else{
                $settings = array( 'dlm_downloads_path' => $paths, 'dlm_crossite_file_browse' => '0', 'dlm_turn_off_file_browser' => '0' );
            }
            
            update_site_option( 'dlm_network_settings', $settings );
        }else{
            update_option( 'dlm_downloads_path', $paths );
        }
    }

    /**
     * Retrieves all download paths either for single site or multisite setup.
     *
     * @return array Array of download paths.
     * @since 5.0.0
    */
    public static function get_all_paths(){
        if( is_multisite() ){
            $settings = get_site_option( 'dlm_network_settings', array() );
            return isset( $settings['dlm_downloads_path'] ) ? $settings['dlm_downloads_path'] : array();
        }else{
            $option = get_option( 'dlm_downloads_path', array() );
            // Check if it's string & do combatiblility for < 5.0.0
            if( is_string( $option ) ) {
                if( '' != $option ) {
                    // Not empty string, save as new format since 5.0.0
                    $paths = array( array( 'id' => 1, 'path_val' => $option, 'enabled' => true ) );
                    self::save_paths( $paths );
                    return $paths;
                }
                return array();
            }
            return $option;
        }
    }

    /**
     * Retrieves all allowed download paths.
     *
     * @return array Array of allowed download paths.
     * @since 5.0.0
    */
	public static function get_allowed_paths(): array {
        $user_paths  = self::get_all_paths();
        $return = array();
        if ( ! empty( $user_paths ) && is_array( $user_paths ) ) {
            foreach ( $user_paths as $user_path ) {
                if( isset( $user_path['enabled'] ) && $user_path['enabled'] ){
                    // Get user defined path
                    if( is_multisite() ){
                        if( isset( $user_path['path_val'] ) && '' != $user_path['path_val'] ){
                            if( is_main_site() ){
                                // This is main site, we replace placeholders with blank.
                                $path = str_replace( '{site_id}', '', $user_path['path_val'] );
                                
                            }else{
                                // This is sub site, we replace placeholders with site id.
                                $path = str_replace( '{site_id}', get_current_blog_id(), $user_path['path_val'] );
                            }
                        }
                    }else{
                        $path = $user_path['path_val'];
                    }
                    $return[] = str_replace( DIRECTORY_SEPARATOR,
                    '/',
                    $path );
                }
            }
        }

        return $return;
	}
    
    /**
     * Retrieves the base admin URL for the download settings.
     *
     * @return string Base admin URL.
     * @since 5.0.0
    */
	public static function get_base_url(): string { 
        if( ! defined( 'MULTISITE' ) || ! MULTISITE ){ 
            return add_query_arg(
                array(
                    'post_type'    => 'dlm_download',
                    'page' => 'download-monitor-settings',
                    'tab'     => 'advanced',
                    'section' => 'download_path',
                ),
                admin_url( 'edit.php' )
            );
        }else{
            return add_query_arg( 'page', 'download-monitor-settings', network_admin_url( 'admin.php' ) );
        }
	}
}
