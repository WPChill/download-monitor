<?php

/**
 * Gets the name of the default template
 * @return string
 */
function dlm_get_default_download_template() {
	$default = get_option( 'dlm_default_template' );

	if ( $default == 'custom' ) {
		$default = get_option( 'dlm_custom_template' );
	}

	return $default;
}

// Filter the single_template with our custom function
function dlm_download_single_post_type_template($single) {
    global $wp_query, $post;
    /* Checks for single template by post type */
    if ($post->post_type == 'dlm_download' || $post->post_type == 'dlm_download_version'){
        if(file_exists(plugin_dir_path(DLM_PLUGIN_FILE) . 'includes/download-query-var-redirect.php'))
            return plugin_dir_path(DLM_PLUGIN_FILE) . 'includes/download-query-var-redirect.php';
    }
    return $single;
}
add_filter('single_template', 'dlm_download_single_post_type_template');
