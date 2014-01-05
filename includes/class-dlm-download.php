<?php
/**
 * DLM_Download class.
 */
class DLM_Download {

	private $files;
	private $file_version_ids;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function __construct( $id ) {
		$this->id              = absint( $id );
		$this->post            = get_post( $this->id );
		$this->version_id      = ''; // Use latest current version
	}

	/**
	 * __isset function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {

		// Get values or default if not set
		if ( 'members_only' == $key )
			$value = ( $value = get_post_meta( $this->id, '_members_only', true ) ) ? $value : 'no';

		elseif ( 'featured' == $key )
			$value = ( $value = get_post_meta( $this->id, '_featured', true ) ) ? $value : 'no';

		elseif ( 'redirect_only' == $key )
			$value = ( $value = get_post_meta( $this->id, '_redirect_only', true ) ) ? $value : 'no';

		else
			$value = get_post_meta( $this->id, '_' . $key, true );

		return $value;
	}

	/**
	 * exists function.
	 *
	 * @access public
	 * @return void
	 */
	public function exists() {
		return ( ! is_null( $this->post ) );
	}

	/**
	 * version_exists function.
	 *
	 * @access public
	 * @param mixed $version_id
	 * @return void
	 */
	public function version_exists( $version_id ) {
		return in_array( $version_id, array_keys( $this->get_file_versions() ) );
	}

	/**
	 * Set the download to a version other than the current / latest version it defaults to.
	 *
	 * @access public
	 * @param mixed $version_id
	 * @return void
	 */
	public function set_version( $version_id = '' ) {
		if ( $this->version_exists( $version_id ) )
			$this->version_id = $version_id;
		else
			$this->version_id = '';
	}

	/**
	 * get_title function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_title() {
		return $this->post->post_title;
	}

	/**
	 * the_title function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_title() {
		echo $this->get_the_title();
	}

	/**
	 * get_the_short_description function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_short_description() {
		return wpautop( $this->post->post_excerpt );
	}

	/**
	 * the_short_description function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_short_description() {
		echo $this->get_the_short_description();
	}

	/**
	 * get_the_image function.
	 *
	 * @access public
	 * @param string $size (default: 'full')
	 * @return void
	 */
	public function get_the_image( $size = 'full' ) {
		global $download_monitor;

		if ( has_post_thumbnail( $this->id ) )
			return get_the_post_thumbnail( $this->id, $size );
		else
			return '<img alt="Placeholder" class="wp-post-image" src="' . apply_filters( 'dlm_placeholder_image_src', $download_monitor->plugin_url() . '/assets/images/placeholder.png' ) . '" />';
	}

	/**
	 * the_image function.
	 *
	 * @access public
	 * @param string $size (default: 'full')
	 * @return void
	 */
	public function the_image( $size = 'full' ) {
		echo $this->get_the_image( $size );
	}

	/**
	 * get_author function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_author() {
		$author_id = $this->post->post_author;
		$user = get_user_by( 'ID', $author_id );
		if ( $user )
			return $user->display_name;
	}

	/**
	 * the_author function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_author() {
		echo $this->get_the_author();
	}

	/**
	 * the_download_link function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_download_link() {
		echo $this->get_the_download_link();
	}

	/**
	 * get_the_download_link function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_download_link() {
		$scheme   = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$endpoint = ( $endpoint = get_option( 'dlm_download_endpoint' ) ) ? $endpoint : 'download';
		$ep_value = get_option( 'dlm_download_endpoint_value' );

		switch ( $ep_value ) {
			case 'slug' :
				$value = $this->post->post_name;
			break;
			default :
				$value = $this->id;
			break;
		}

		if ( get_option('permalink_structure') ) {
			$link = home_url( '/' . $endpoint . '/' . $value . '/', $scheme );
		} else {
			$link = add_query_arg( $endpoint, $value, home_url( '', $scheme ) );
		}

		if ( $this->version_id ) {

			if ( $this->has_version_number() )
				$link = add_query_arg( 'version', $this->get_the_version_number(), $link );
			else
				$link = add_query_arg( 'v', $this->version_id, $link );
		}

		return apply_filters( 'dlm_download_get_the_download_link', esc_url_raw( $link ), $this, $this->version_id );
	}

	/**
	 * the_download_count function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_download_count() {
		echo $this->get_the_download_count();
	}

	/**
	 * get_the_download_count function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_download_count() {
    	if ( $this->version_id ) {
	    	return absint( $this->get_file_version()->download_count );
    	} else {
    		return absint( $this->download_count );
    	}
	}

	/**
	 * has_version_number function.
	 *
	 * @access public
	 * @return void
	 */
	public function has_version_number() {
		return ! empty( $this->get_file_version()->version );
	}

	/**
	 * get_the_version_number function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_version_number() {
		return ( $version = $this->get_file_version()->version ) ? $version : '1';
	}

	/**
	 * the_version_number function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_version_number() {
		echo $this->get_the_version_number();
	}

	/**
	 * get_the_filename function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_filename() {
		return $this->get_file_version()->filename;
	}

	/**
	 * the_filename function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_filename() {
		echo $this->get_the_filename();
	}

	/**
	 * get_the_file_date function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_the_file_date() {
		$post = get_post( $this->get_file_version()->id );

		return $post->post_date;
	}

	/**
	 * get_the_filesize function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_the_filesize() {
		$filesize   = $this->get_file_version()->filesize;

		if ( $filesize > 0 )
			return size_format( $filesize );
	}

	/**
	 * the_filesize function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_filesize() {
		echo $this->get_the_filesize();
	}

	/**
	 * Get the hash
	 * @param  string $type md5, sha1 or crc32
	 * @return string
	 */
	public function get_the_hash( $type = 'md5' ) {
		$hash   = $this->get_file_version()->$type;
		return $hash;
	}

	/**
	 * Get the hash
	 * @param  string $type md5, sha1 or crc32
	 * @return string
	 */
	public function the_hash( $type = 'md5' ) {
		echo $this->get_the_hash( $type );
	}

	/**
	 * get_the_filetype function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_the_filetype() {
		return $this->get_file_version()->filetype;
	}

	/**
	 * the_filetype function.
	 *
	 * @access public
	 * @return void
	 */
	public function the_filetype() {
		echo $this->get_the_filetype();
	}

    /**
     * Get a version by ID, or default to current version.
     *
     * @access public
     * @return void
     */
    public function get_file_version() {
    	$version = false;

    	if ( $this->version_id ) {
	    	$versions = $this->get_file_versions();

	    	if ( ! empty( $versions[ $this->version_id ] ) )
	    		$version = $versions[ $this->version_id ];

    	} elseif ( $versions = $this->get_file_versions() ) {
	    	$version = array_shift( $versions );
    	}

    	if ( ! $version ) {

	    	$version = new stdClass();

	    	$version->id             = 0;
	    	$version->download_id    = $this->id;
			$version->mirrors        = array();
			$version->url            = '';
			$version->filename       = '';
			$version->filetype       = '';
			$version->version        = '';
			$version->download_count = '';
			$version->filesize       = '';
    	}

    	return $version;
    }

    /**
     * Get a version ID from a version string.
     *
     * @access public
     * @return void
     */
    public function get_version_id( $version_string = '' ) {
	    $versions = $this->get_file_versions();

	    foreach ( $versions as $version_id => $version )
	    	if ( version_compare( $version->version, strtolower( $version_string ), '=' ) )
	    		return $version_id;
    }

	/**
	 * is_featured function.
	 *
	 * @access public
	 * @return bool
	 */
	function is_featured() {
		return ( $this->featured == 'yes' ) ? true : false;
	}

	/**
	 * is_members_only function.
	 *
	 * @access public
	 * @return bool
	 */
	function is_members_only() {
		return ( $this->members_only == 'yes' ) ? true : false;
	}

	/**
	 * redirect_only function.
	 *
	 * @access public
	 * @return bool
	 */
	function redirect_only() {
		return ( $this->redirect_only == 'yes' ) ? true : false;
	}

	/**
	 * get_file_version_ids function.
	 *
	 * @access public
	 * @return void
	 */
	function get_file_version_ids() {
		if ( ! is_array( $this->file_version_ids ) ) {
			$this->file_version_ids = get_posts( 'post_parent=' . $this->id . '&post_type=dlm_download_version&orderby=menu_order&order=ASC&fields=ids&post_status=publish&numberposts=-1' );
		}

		return $this->file_version_ids;
	}

    /**
     * get_file_versions function.
     *
     * @access public
     * @return void
     */
    public function get_file_versions() {
    	if ( $this->files )
    		return $this->files;

	    $version_ids = $this->get_file_version_ids();
	    $this->files = array();

	    foreach ( $version_ids as $version_id )
		    $this->files[ $version_id ] = new DLM_Download_Version( $version_id, $this->id );

	    return $this->files;
    }
}