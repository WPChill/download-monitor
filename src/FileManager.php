<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_File_Manager {

	/**
	 * Returns a listing of all files in the specified folder and all subdirectories up to 100 levels deep.
	 * The depth of the recursiveness can be controlled by the $levels param.
	 *
	 * @access public
	 *
	 * @param string $folder (default: '')
	 *
	 * @return array|bool
	 */
	public function list_files( $folder = '' ) {
		if ( empty( $folder ) ) {
			return false;
		}

		// A listing of all files and dirs in $folder, excepting . and ..
		// By default, the sorted order is alphabetical in ascending order
		$files = array_diff( scandir( $folder ), array( '..', '.' ) );

		$dlm_files = array();

		foreach ( $files as $file ) {
			$dlm_files[] = array(
				'type' => ( is_dir( $folder . '/' . $file ) ? 'folder' : 'file' ),
				'path' => $folder . '/' . $file
			);
		}

		return $dlm_files;
	}

	/**
	 * Parse a file path and return the new path and whether or not it's remote
	 *
	 * @param  string $file_path
	 *
	 * @return array
	 */
	public function parse_file_path( $file_path ) {

		$remote_file      = true;
		$parsed_file_path = parse_url( $file_path );

		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];
		$allowed_paths  = $this->get_allowed_paths();
		$common_path    = DLM_Utils::longest_common_path( $allowed_paths );

		// Fix for plugins that modify the uploads dir
		// add filter in order to return files
		if ( apply_filters( 'dlm_check_file_paths', false, $file_path, $remote_file ) ) {
			return array( $file_path, $remote_file );
		}

		// Check if relative path or absolute path, as the file_exists function needs an absolute path
		// So that we do not trigger warnings/errors with open_basedir restrictions
		$file_check['exists']   = false;
		$file_check['relative'] = false;

		if ( isset( $parsed_file_path['path'] ) ) {
			// Check if common path is contained within the file path, if it doesn't it is a relative path,
			// or it is a non-allowed file.
			if ( $common_path && strlen( $common_path ) > 1 && false === strpos( $parsed_file_path['path'], $common_path ) ) {
				if ( is_file( realpath( trailingslashit( $common_path ) . $parsed_file_path['path'] ) ) ) { // Check if it's a relative path, so add the common path to it.
					$file_check['exists']   = true;
					$file_check['relative'] = true;
				} elseif ( file_exists( $parsed_file_path['path'] ) ) { // Check if it's an absolute path, most probably a non-allowed file.
					$file_check['exists']   = true;
				}
			} else {
				// If common path is included in the file path, check if the file exists.
				if ( file_exists( $parsed_file_path['path'] ) ) {
					$file_check['exists'] = true;
				}
			}
		}

		if ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array(
					'http',
					'https',
					'ftp'
				) ) ) && $file_check['exists']
		) {

			/** The file lies in the server */
			$remote_file = false;
			// If it's relative we need to make it absolute.
			if ( $file_check['relative'] ) {
				$file_path = trailingslashit( $common_path ) . $parsed_file_path['path'];
				$file_path = realpath( $file_path );
			}
		} elseif ( strpos( $wp_uploads_dir, '://' ) !== false ) {

			/** 
			 * This is a file located on a network drive.  
			 * WordPress VIP is a providor that uses network drive paths
			 * Only allow if root (vip://) is predefined in Settings > Misc > Other downloads path
			 * Example of path: vip://wp-content/upload...
			 **/
			$remote_file = false;
			$path = array_reduce( $allowed_paths, function ($carry, $path) use ($wp_uploads_dir, $wp_uploads_url, $file_path ) {
				return strpos( $path, '://' ) !== false && strpos( $wp_uploads_dir, $path ) !== false 
					? trim( str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path ) )
					: $carry;
			}, false);

			// realpath() will return false on network drive paths, so just check if exists
			$file_path = file_exists( $path ) ? $path : realpath( $file_path );

		}  elseif ( strpos( $file_path, $wp_uploads_url ) !== false ) {

			/** This is a local file given by URL, so we need to figure out the path */
			$remote_file = false;
			$file_path   = trim( str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path ) );
			$file_path   = realpath( $file_path );

		} elseif ( is_multisite() && ( ( strpos( $file_path, network_site_url( '/', 'http' ) ) !== false ) || ( strpos( $file_path, network_site_url( '/', 'https' ) ) !== false ) ) ) {

			/** This is a local file outside wp-content so figure out the path */
			$remote_file = false;
			// Try to replace network url
			$file_path = str_replace( network_site_url( '/', 'https' ), ABSPATH, $file_path );
			$file_path = str_replace( network_site_url( '/', 'http' ), ABSPATH, $file_path );
			// Try to replace upload URL
			$file_path = str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path );
			$file_path = realpath( $file_path );

		} elseif ( strpos( $file_path, site_url( '/', 'http' ) ) !== false || strpos( $file_path, site_url( '/', 'https' ) ) !== false ) {

			/** This is a local file outside wp-content so figure out the path */
			$remote_file = false;
			$file_path   = str_replace( site_url( '/', 'https' ), ABSPATH, $file_path );
			$file_path   = str_replace( site_url( '/', 'http' ), ABSPATH, $file_path );
			$file_path   = realpath( $file_path );

		} elseif ( file_exists( ABSPATH . $file_path ) ) {
			/** Path needs an abspath to work */
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;
			$file_path   = realpath( $file_path );
		} elseif ( '' === $common_path || strlen( $common_path ) === 1 ) {
			foreach ( $allowed_paths as $path ) {
				if ( file_exists( $path . $file_path ) ) {
					$remote_file = false;
					$file_path   = $path . $file_path;
					$file_path   = realpath( $file_path );
					break;
				}
			}
		}

		return array( str_replace( DIRECTORY_SEPARATOR, '/', $file_path ), $remote_file );
	}

	/**
	 * Gets the filesize of a path or URL.
	 *
	 * @access public
	 *
	 * @param string $file_path
	 *
	 * @return string size on success, -1 on failure
	 */
	public function get_file_size( $file_path ) {
		if ( $file_path ) {
			list( $file_path, $remote_file ) = $this->parse_file_path( $file_path );

			if ( ! empty( $file_path ) ) {
				if ( $remote_file ) {
					$file = wp_remote_head( $file_path );

					if ( ! is_wp_error( $file ) && ! empty( $file['headers']['content-length'] ) ) {
						return $file['headers']['content-length'];
					}
				} else {
					if ( file_exists( $file_path ) && ( $filesize = filesize( $file_path ) ) ) {
						return $filesize;
					}
				}
			}
		}

		return - 1;
	}

	/**
	 * Encode files for storage
	 *
	 * @param  array $files
	 *
	 * @return string
	 */
	public function json_encode_files( $files ) {
		if ( version_compare( phpversion(), "5.4.0", ">=" ) ) {
			$files = json_encode( $files, JSON_UNESCAPED_UNICODE );
		} else {
			$files = json_encode( $files );
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$files = preg_replace_callback( '/\\\\u([0-9a-f]{4})/i', array(
					$this,
					'json_unscaped_unicode_fallback'
				), $files );
			}
		}

		return $files;
	}

	/**
	 * Fallback for PHP < 5.4 where JSON_UNESCAPED_UNICODE does not exist.
	 *
	 * @param  array $matches
	 *
	 * @return string
	 */
	public function json_unscaped_unicode_fallback( $matches ) {
		$sym = mb_convert_encoding(
			pack( 'H*', $matches[1] ),
			'UTF-8',
			'UTF-16'
		);

		return $sym;
	}

	/**
	 * Multi-byte-safe pathinfo replacement.
	 *
	 * @param $filepath
	 *
	 * @return mixed
	 */
	public function mb_pathinfo( $filepath ) {
		$ret = array();
		preg_match( '%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $filepath, $m );
		if ( isset( $m[1] ) ) {
			$ret['dirname'] = $m[1];
		}
		if ( isset( $m[2] ) ) {
			$ret['basename'] = $m[2];
		}
		if ( isset( $m[5] ) ) {
			$ret['extension'] = $m[5];
		}
		if ( isset( $m[3] ) ) {
			$ret['filename'] = $m[3];
		}

		return $ret;
	}

	/**
	 * Get file name for given path
	 *
	 * @param string $file_path
	 *
	 * @return string
	 */
	public function get_file_name( $file_path ) {
		return apply_filters( 'dlm_filemanager_get_file_name', current( explode( '?', DLM_Utils::basename( $file_path ) ) ) );
	}

	/**
	 * Get file type of give file name
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public function get_file_type( $file_name ) {
		return strtolower( substr( strrchr( $file_name, "." ), 1 ) );
	}

	/**
	 * Gets md5, sha1 and crc32 hashes for a file and store it.
	 *
	 * @deprecated use hasher service get_file_hashes() instead
	 *
	 * @param string $file_path
	 *
	 * @return array of sizes
	 */
	public function get_file_hashes( $file_path ) {
		return download_monitor()->service( 'hasher' )->get_file_hashes( $file_path );
	}

	/**
	 * Return the secured file path or url of the downloadable file. Should not let restricted files or out of root files to be downloaded.
	 *
	 * @param string $file The file path/url
	 * @param bool $relative Wheter or not to return a relative path. Default is false
	 *
	 * @return array The secured file path/url and restriction status
	 * @since 4.5.9
	 */
	public function get_secure_path( $file, $relative = false ) {

		// ABSPATH needs to be defined
		if ( ! defined( 'ABSPATH' ) ) {
			die;
		}

		list( $file_path, $remote_file ) = $this->parse_file_path( $file );

		// Let's see if the file path is dirty
		$file_scheme = parse_url( $file_path, PHP_URL_SCHEME );
		// Default restricted URL schemes
		$restricted_schemes = array( 'php' );
		$restricted_schemes = array_merge(
			$restricted_schemes,
			apply_filters(
				'dlm_restricted_schemes',
				array()
			)
		);

		if ( in_array( $file_scheme, $restricted_schemes ) ) {
			$restriction = true;
			return array( $file_path, $remote_file, $restriction );
		}

		// If the file is remote, return the file path. If the file is not located on local server, return the file path.
		// This is available even if the file is one of the restricted files below. The plugin will let the user download the file,
		// but the file will be empty, with a 404 error or an error message.
		if ( $remote_file ) {
			$restriction = false;
			return array( $file_path, $remote_file, $restriction );
		}

		// The list of predefined restricted files.
		$restricted_files = array(
			'wp-config.php',
			'.htaccess',
			'php.ini',
		);

		// Specify the files that should be restricted from the download process.
		$restricted_files = array_merge(
			$restricted_files,
			apply_filters(
				'dlm_file_urls_security_files',
				array()
			)
		);

		// Loop through the restricted files and return empty string if found.
		foreach ( $restricted_files as $restricted_file ) {

			if ( basename( $file_path ) === $restricted_file ) {
				// If the file is restricted.
				$restriction = true;
				return array( $file_path, $remote_file, $restriction );
			}
		}

		$allowed_paths = $this->get_allowed_paths();
		$correct_path  = $this->get_correct_path( $file_path, $allowed_paths );

		// If the file is not in one of the allowed paths, return restriction
		if ( ! $correct_path || empty( $correct_path ) ) {
			$restriction = true;
			return array( $file_path, $remote_file, $restriction );
		}

		if ( $relative ) {
			// Now we should get longest commont path from the allowed paths.
			$common_path = DLM_Utils::longest_common_path( $allowed_paths );
			// If there is no common path, or is emtpy or is just a slash, return the file path, else do the replacement.
			if ( strlen( $common_path ) > 1 ) {
				$file_path = str_replace( $common_path, '', $file_path );
			}
		}

		$restriction = false;

		return array( $file_path, $remote_file, $restriction );

	}

	/**
	 * Get file allowed paths
	 *
	 * @return array
	 * @since 4.5.92
	 */
	public function get_allowed_paths() {

		$abspath_sub       = str_replace(DIRECTORY_SEPARATOR, '/', untrailingslashit( ABSPATH ) );
		$user_defined_path = str_replace(DIRECTORY_SEPARATOR, '/', get_option( 'dlm_downloads_path' ) );
		$allowed_paths     = array();

		if ( false === strpos( WP_CONTENT_DIR, ABSPATH ) ) {
			$content_dir   = str_replace(DIRECTORY_SEPARATOR, '/', str_replace( 'wp-content', '', untrailingslashit( WP_CONTENT_DIR ) ) );
			$allowed_paths = array( $abspath_sub, $content_dir );
		} else {
			$allowed_paths = array( $abspath_sub );
		}

		if ( $user_defined_path ) {
			$allowed_paths[] = $user_defined_path;
		}
		return $allowed_paths;
	}

	/**
	 * Return the correct path for the file by comparing the file path string with the allowed paths.
	 *
	 * @param string $file_path The current path of the file
	 * @param array $allowed_paths The allowed paths of the files
	 *
	 * @return string The correct path of the file
	 * @since 4.5.92
	 */
	public function get_correct_path( $file_path, $allowed_paths ) {

		/* We assume first assume that the path is false, as the ABSPATH is always allowed asnd should always be in the
		 * allowed paths.
		 */
		$correct_path = false;

		if ( ! empty( $allowed_paths ) ) {
			foreach ( $allowed_paths as $allowed_path ) {
				// If we encounter a scenario where the file is in the allowed path, we can trust it is in the correct path so we should break the loop.
				if ( false !== strpos( $file_path, $allowed_path ) ) {
					$correct_path = $allowed_path;
					break;
				}
			}
		}

		return $correct_path;
	}

	/**
	 * Check for symbolik links in the file path.
	 *
	 * @param string $file_path The file's path
	 * @param bool $redirect Whether or not to redirect the user to the correct path. Default is false.
	 *
	 * @return array|mixed|string|string[]
	 */
	public function check_symbolic_links( $file_path, $redirect = false ) {
		// On Pantheon hosted sites the upload dir is a symbolic link to another location.
		// Make a filter of all shortcuts/symbolik links so that users can attach to them because we do not know what how the server
		// is configured.
		$shortcuts     = apply_filters( 'dlm_upload_shortcuts', array( wp_get_upload_dir()['basedir'] ) );
		$scheme        = wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$allowed_paths = download_monitor()->service( 'file_manager' )->get_allowed_paths();
		$correct_path  = download_monitor()->service( 'file_manager' )->get_correct_path( $file_path, $allowed_paths );

		if ( ! empty( $shortcuts ) ) {
			foreach ( $shortcuts as $shortcut ) {
				if ( is_link( $shortcut ) && readlink( $shortcut ) === $correct_path ) {
					$file_path = str_replace( $correct_path, $shortcut, $file_path );
					if ( $redirect ) {
						$file_path = str_replace( ABSPATH, site_url( '/', $scheme ), $file_path );
					}
				}
			}
		}

		return $file_path;
	}

	/**
	 * Function to move files from Media Library to the DLM protected folder dlm_uploads.
	 *
	 * @param $post_id
	 *
	 * @return WP_Error
	 * @since 4.7.2
	 */
	public function move_file_to_dlm_uploads( $post_id ) {
		//Move file to dlm_uploads
		$file = get_post_meta( $post_id, '_wp_attached_file', true );

		if ( 0 === stripos( $file, $this->dlm_upload_dir( '/' ) ) ) {
			return new WP_Error( 'protected_file_existed', sprintf(
				__( 'This file is already protected. Please reload your page.', 'download-monitor' ),
				$file
			),                   array( 'status' => 500 ) );
		}

		$reldir = dirname( $file );

		if ( in_array( $reldir, array( '\\', '/', '.' ), true ) ) {
			$reldir = '';
		}

		$protected_dir = path_join( $this->dlm_upload_dir(), $reldir );
		return $this->move_attachment_to_protected( $post_id, $protected_dir );

	}

	/**
	 * Function to move files back to the Media Library.
	 *
	 * @param $post_id
	 *
	 * @return array|bool|int|WP_Error
	 * @since 4.7.2
	 */
	public function move_file_back( $post_id ) {

		$file = get_post_meta( $post_id, '_wp_attached_file', true );

		// check if files are already not in Download Monitor's protected folder
		if ( 0 !== stripos( $file, $this->dlm_upload_dir( '/' ) ) ) {
			return true;
		}

		$protected_dir = ltrim( dirname( $file ), $this->dlm_upload_dir( '/' ) );
		return  $this->move_attachment_to_protected( $post_id, $protected_dir );
	}

	/**
	 * Download Monitor's upload directory ( dlm_uploads ).
	 *
	 * @param $path
	 * @param $in_url
	 *
	 * @return string
	 * @since 4.7.2
	 */
	public function dlm_upload_dir( $path = '', $in_url = false ) {

		$dirpath = $in_url ? '/' : '';
		$dirpath .= 'dlm_uploads';
		$dirpath .= $path;

		return $dirpath;
	}

	/**
	 *  Move attachment to protected folder.
	 *
	 * @param $attachment_id
	 * @param $protected_dir
	 * @param $meta_input
	 *
	 * @return array|bool|WP_Error
	 * @since 4.7.2
	 */
	public function move_attachment_to_protected( $attachment_id, $protected_dir, $meta_input = [] ) {

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return new WP_Error( 'not_attachment', sprintf(
				__( 'The post with ID: %d is not an attachment post type.', 'download-monitor' ),
				$attachment_id
			),                   array( 'status' => 404 ) );
		}

		if ( path_is_absolute( $protected_dir ) ) {
			return new WP_Error( 'protected_dir_not_relative', sprintf(
				__( 'The new path provided: %s is absolute. The new path must be a path relative to the WP uploads directory.', 'download-monitor' ),
				$protected_dir
			),                   array( 'status' => 404 ) );
		}

		$meta = empty( $meta_input ) ? wp_get_attachment_metadata( $attachment_id ) : $meta_input;
		$meta = is_array( $meta ) ? $meta : array();

		$file       = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$backups    = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
		$upload_dir = wp_upload_dir();
		$old_dir    = dirname( $file );

		if ( in_array( $old_dir, array( '\\', '/', '.' ), true ) ) {
			$old_dir = '';
		}

		if ( $protected_dir === $old_dir ) {
			return true;
		}

		$old_full_path       = path_join( $upload_dir['basedir'], $old_dir );
		$protected_full_path = path_join( $upload_dir['basedir'], $protected_dir );

		if ( ! wp_mkdir_p( $protected_full_path ) ) {
			return new WP_Error( 'wp_mkdir_p_error', sprintf(
				__( 'There was an error making or verifying the directory at: %s', 'download-monitor' ),
				$protected_full_path
			),                   array( 'status' => 500 ) );
		}

		//Get all files
		$sizes = array();

		if ( array_key_exists( 'sizes', $meta ) ) {
			$sizes = $this->get_files_from_meta( $meta['sizes'] );
		}

		$backup_sizes  = $this->get_files_from_meta( $backups );
		$old_basenames = $new_basenames = array_merge(
			array( wp_basename( $file ) ),
			$sizes,
			$backup_sizes
		);
		$orig_basename = wp_basename( $file );

		if ( is_array( $backups ) && isset( $backups['full-orig'] ) ) {
			$orig_basename = $backups['full-orig']['file'];
		}

		$orig_filename = pathinfo( $orig_basename );
		$orig_filename = $orig_filename['filename'];

		$result        = $this->resolve_name_conflict( $new_basenames, $protected_full_path, $orig_filename );
		$new_basenames = $result['new_basenames'];

		$this->rename_files( $old_basenames, $new_basenames, $old_full_path, $protected_full_path );

		$base_file_name = 0;

		$new_attached_file = path_join( $protected_dir, $new_basenames[0] );
		if ( array_key_exists( 'file', $meta ) ) {
			$meta['file'] = $new_attached_file;
		}
		update_post_meta( $attachment_id, '_wp_attached_file', $new_attached_file );

		if ( $new_basenames[ $base_file_name ] != $old_basenames[ $base_file_name ] ) {
			$pattern       = $result['pattern'];
			$replace       = $result['replace'];
			$separator     = "#";
			$orig_basename = ltrim(
				str_replace( $pattern, $replace, $separator . $orig_basename ),
				$separator
			);
			$meta          = $this->update_meta_sizes_file( $meta, $new_basenames );
			$this->update_backup_files( $attachment_id, $backups, $new_basenames );
		}

		update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );
		$guid = path_join( $protected_full_path, $orig_basename );
		wp_update_post( array( 'ID' => $attachment_id, 'guid' => $guid ) );

		return empty( $meta_input ) ? true : $meta;
	}

	/**
	 * Get files from meta.
	 *
	 * @param $input
	 *
	 * @return array
	 * @since 4.7.2
	 */
	public function get_files_from_meta( $input ) {

		$files = array();
		if ( is_array( $input ) ) {
			foreach ( $input as $size ) {
				$files[] = $size['file'];
			}
		}

		return $files;
	}

	/**
	 * Resolve name conflict.
	 *
	 * @param $new_basenames
	 * @param $protected_full_path
	 * @param $orig_file_name
	 *
	 * @return array
	 * @since 4.7.2
	 */
	public function resolve_name_conflict( $new_basenames, $protected_full_path, $orig_file_name ) {

		$conflict     = true;
		$number       = 1;
		$separator    = "#";
		$med_filename = $orig_file_name;
		$pattern      = "";
		$replace      = "";

		while ( $conflict ) {
			$conflict = false;
			foreach ( $new_basenames as $basename ) {
				if ( is_file( path_join( $protected_full_path, $basename ) ) ) {
					$conflict = true;
					break;
				}
			}

			if ( $conflict ) {
				$new_filename = "$orig_file_name-$number";
				$number ++;
				$pattern       = "$separator$med_filename";
				$replace       = "$separator$new_filename";
				$new_basenames = explode(
					$separator,
					ltrim(
						str_replace( $pattern, $replace, $separator . implode( $separator, $new_basenames ) ),
						$separator
					)
				);

			}
		}

		return array(
			'new_basenames' => $new_basenames,
			'pattern'       => $pattern,
			'replace'       => $replace
		);
	}

	/**
	 * Rename files.
	 *
	 * @param $old_basenames
	 * @param $new_basenames
	 * @param $old_dir
	 * @param $protected_dir
	 *
	 * @return void|WP_Error
	 * @since 4.7.2
	 */
	public function rename_files( $old_basenames, $new_basenames, $old_dir, $protected_dir ) {

		$unique_old_basenames = array_values( array_unique( $old_basenames ) );
		$unique_new_basenames = array_values( array_unique( $new_basenames ) );
		$i                    = count( $unique_old_basenames );

		while ( $i -- ) {
			$old_fullpath = path_join( $old_dir, $unique_old_basenames[ $i ] );
			$new_fullpath = path_join( $protected_dir, $unique_new_basenames[ $i ] );
			if ( is_file( $old_fullpath ) ) {
				rename( $old_fullpath, $new_fullpath );

				if ( ! is_file( $new_fullpath ) ) {
					return new WP_Error(
						'rename_failed',
						sprintf(
							__( 'Rename failed when trying to move file from: %s, to: %s', 'download-monitor' ),
							$old_fullpath,
							$new_fullpath
						)
					);
				}
			}
		}
	}

	/**
	 * Update meta sizes file.
	 *
	 * @param $meta
	 * @param $new_basenames
	 *
	 * @return array
	 * @since 4.7.2
	 */
	public function update_meta_sizes_file( $meta, $new_basenames ) {

		if ( is_array( $meta['sizes'] ) ) {
			$i = 0;

			foreach ( $meta['sizes'] as $size => $data ) {
				$meta['sizes'][ $size ]['file'] = $new_basenames[ ++ $i ];
			}
		}

		return $meta;
	}

	/**
	 * Update backup files.
	 *
	 * @param $attachment_id
	 * @param $backups
	 * @param $new_basenames
	 *
	 * @return void
	 * @since 4.7.2
	 */
	public function update_backup_files( $attachment_id, $backups, $new_basenames ) {

		if ( is_array( $backups ) ) {
			$i                = 0;
			$l                = count( $backups );
			$new_backup_sizes = array_slice( $new_basenames, - $l, $l );

			foreach ( $backups as $size => $data ) {
				$backups[ $size ]['file'] = $new_backup_sizes[ $i ++ ];
			}
			update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $backups );
		}
	}
}
