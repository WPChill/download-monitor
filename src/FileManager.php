<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'DLM_File_Manager' ) ) {
	/**
	 * DLM_File_Manager class.
	 *
	 * Class used to handle file operations and data.
	 */
	class DLM_File_Manager {

		/**
		 * Returns a listing of all files in the specified folder and all subdirectories up to 100 levels deep.
		 * The depth of the recursiveness can be controlled by the $levels param.
		 *
		 * @access public
		 *
		 * @param  string  $folder  (default: '')
		 *
		 * @return array|bool
		 */
		public function list_files( $folder = '' ) {
			// If no folder is specified, return false
			if ( empty( $folder ) ) {
				return false;
			}
			// If not dir, return false
			if ( ! is_dir( $folder ) ) {
				return false;
			}
			// If the folder does not exist, return false
			$files_folders = scandir( $folder );
			if ( ! $files_folders ) {
				return false;
			}

			// A listing of all files and dirs in $folder, excepting . and ..
			// By default, the sorted order is alphabetical in ascending order
			$files = array_diff( scandir( $folder ), array( '..', '.' ) );

			$dlm_files = array();

			foreach ( $files as $file ) {
				$dlm_files[] = array(
					'type' => ( is_dir( $folder . '/' . $file ) ? 'folder'
						: 'file' ),
					'path' => $folder . '/' . $file,
				);
			}

			return $dlm_files;
		}

		/**
		 * Parse a file path and return the new path and whether it's remote
		 *
		 * @param  string  $file_path
		 *
		 * @return array
		 */
		public function parse_file_path( $file_path ) {
			$remote_file      = true;
			$parsed_file_path = parse_url( $file_path );
			$wp_uploads       = wp_upload_dir();
			$wp_uploads_dir   = $wp_uploads['basedir'];
			$wp_uploads_url   = $wp_uploads['baseurl'];
			$allowed_paths    = $this->get_allowed_paths();
			$condition_met    = false;
			$allowed_path     = false;
			$restriction      = false;
			if ( false !== strpos( $file_path, '127.0.0.1' ) ) {
				$file_path        = untrailingslashit( ABSPATH )
				                    . $parsed_file_path['path'];
				$parsed_file_path = parse_url( $file_path );
			}

			// Fix for plugins that modify the uploads dir
			// add filter in order to return files
			if ( apply_filters( 'dlm_check_file_paths', false, $file_path, $remote_file ) ) {
				return array( $file_path, $remote_file );
			}

			// Check file path
			if ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp', ) ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/** The file lies in the server */
				$remote_file = false;
			} elseif ( strpos( $wp_uploads_dir, '://' ) !== false ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/**
				 * This is a file located on a network drive.
				 * WordPress VIP is a provider that uses network drive paths
				 * Only allow if root (vip://) is predefined in Settings > Advanced > Approved Download Paths
				 * Example of path: vip://wp-content/upload...
				 **/
				$remote_file = false;
				$path        = array_reduce( $allowed_paths,
					function ( $carry, $path ) use (
						$wp_uploads_dir,
						$wp_uploads_url,
						$file_path
					) {
						return strpos( $path, '://' ) !== false
						       && strpos( $wp_uploads_dir, $path ) !== false
							? trim( str_replace( $wp_uploads_url,
							                     $wp_uploads_dir,
							                     $file_path ) )
							: $carry;
					},
					                         false );

				// realpath() will return false on network drive paths, so just check if exists
				$file_path = file_exists( $path ) ? $path : realpath( $file_path );
			} elseif ( strpos( $file_path, $wp_uploads_url ) !== false ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/** This is a local file given by URL, so we need to figure out the path */
				$remote_file = false;
				$file_path   = trim( str_replace( $wp_uploads_url, $wp_uploads_dir, $file_path ) );
				$file_path   = realpath( $file_path );
			} elseif ( is_multisite() && ( ( strpos( $file_path, network_site_url( '/', 'http' ) ) !== false ) || ( strpos( $file_path, network_site_url( '/', 'https' ) ) !== false ) ) ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/** This is a local file outside wp-content so figure out the path */
				$remote_file = false;
				// Try to replace network url
				$file_path = str_replace( network_site_url( '/', 'https' ),
				                          ABSPATH,
				                          $file_path );
				$file_path = str_replace( network_site_url( '/', 'http' ),
				                          ABSPATH,
				                          $file_path );
				// Try to replace upload URL
				$file_path = str_replace( $wp_uploads_url,
				                          $wp_uploads_dir,
				                          $file_path );
				$file_path = realpath( $file_path );
			} elseif ( false !== strpos( $file_path, site_url( '/', 'http' ) ) || false !== strpos( $file_path, site_url( '/', 'https' ) ) ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/** This is a local file outside wp-content so figure out the path */
				$remote_file = false;
				$file_path   = str_replace( site_url( '/', 'https' ),
				                            ABSPATH,
				                            $file_path );
				$file_path   = str_replace( site_url( '/', 'http' ),
				                            ABSPATH,
				                            $file_path );
				$file_path   = realpath( $file_path );
			} elseif ( file_exists( ABSPATH . $file_path ) ) {
				// File existence found, weathers it's relative, absolute or remote.
				$condition_met = true;
				/** Path needs an abspath to work */
				$remote_file = false;
				$file_path   = ABSPATH . $file_path;
				$file_path   = realpath( $file_path );
			}


			// File not remote, so we need to check if it's in the allowed paths
			if ( ! empty( $allowed_paths ) ) {
				$file_path = str_replace( DIRECTORY_SEPARATOR, '/', $file_path );
				// Cycle through the allowed paths and check if one of the allowed paths are in the file path.
				foreach ( $allowed_paths as $path ) {
					// Condition already met in the above checks, so we need to check if the file is in one of the allowed paths
					if ( $condition_met ) {
						if ( false !== strpos( $file_path, $path ) ) {
							$allowed_path = true;
							break;
						}
					} else { // No conditions prior met, so we need to backwards construct the path
						// Check if it's a remote file
						// Check if the scheme is allowed
						$scheme_check = isset( $parsed_file_path['scheme'] ) && in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp', ) );
						// Check if the domain is the same as the site
						$domain_check = false !== strpos( $file_path, site_url( '/', 'http' ) ) || false !== strpos( $file_path, site_url( '/', 'https' ) );
						// If has scheme but not domain, break
						if ( $scheme_check && ! $domain_check ) {
							break;
						}
						// Check if the file is a child of the allowed path
						if ( file_exists( $path . $file_path ) ) {
							$allowed_path  = true;
							$condition_met = true;
							$remote_file   = false;
							$file_path     = untrailingslashit( $path ) . '/' . ltrim( $file_path, '/' );
							break;
						}
						// File might be a child of the allowed path but has a common directory, so it might not detect a direct $path . $file_path match
						$base_file_path     = $this->mb_pathinfo( $file_path )['dirname'];
						$path_directories   = array_filter( explode( '/', trim( $path ) ) );
						$file_directories   = array_filter( explode( '/', trim( $base_file_path ) ) );
						$common_directories = array_intersect( $file_directories, $path_directories );

						// Check if there are common directories between the file and the allowed path
						if ( ! empty( $common_directories ) ) {
							// Get the file extension and remove it from the file path, in case the common directories have the same name as the file extension
							$file_extension = $this->mb_pathinfo( $file_path )['extension'];
							if ( ! empty( $file_extension ) ) {
								$file_path = str_replace( '.' . $file_extension, '', $file_path );
							}
							// Replace the directory separator with a forward slash
							$file_path = str_replace( DIRECTORY_SEPARATOR, '/', $file_path );
							// Cycle through the common directories and remove them from the file path
							foreach ( $common_directories as $key => $common_directory ) {
								// Check if the common directory is the last in the array, and if so, remove the forward slash from before the common directory
								if ( count( $common_directories ) === ( $key - 1 ) ) {
									$replace = '/' . $common_directory;
								} else { // Otherwise, remove the forward slash from after the common directory
									$replace = $common_directory . '/';
								}
								$file_path = str_replace( $replace, '', $file_path );
							}
							// Add the file extension back to the file path
							$file_path = $file_path . '.' . $file_extension;
							// Check if the file exists in the allowed path
							if ( file_exists( $path . $file_path ) ) {
								$allowed_path  = true;
								$condition_met = true;
								$remote_file   = false;
								$file_path     = untrailingslashit( $path ) . '/' . ltrim( $file_path, '/' );
								break;
							}
						}
					}
				}
			}

			// If the file is remote, return the file path
			if ( $remote_file ) {
				return array(
					$file_path,
					$remote_file,
					$restriction,
				);
			}

			// File not found on server or is not in the allowed paths
			if ( ! $condition_met || ! $allowed_path ) {
				$restriction = true;

				return array( $file_path, false, $restriction );
			}

			return array(
				str_replace( DIRECTORY_SEPARATOR, '/', $file_path ),
				$remote_file,
				$restriction,
			);
		}

		/**
		 * Gets the filesize of a path or URL.
		 *
		 * @access public
		 *
		 * @param  string  $file_path
		 *
		 * @return string size on success, -1 on failure
		 */
		public function get_file_size( $file_path ) {
			// Check if file exists
			if ( $file_path ) {
				list( $file_path, $remote_file )
					= $this->parse_file_path( $file_path );
				// Check if file from the new path exists
				if ( ! empty( $file_path ) ) {
					if ( $remote_file ) {
						$file = wp_remote_head( $file_path );

						if ( ! is_wp_error( $file )
						     && ! empty( $file['headers']['content-length'] )
						) {
							return $file['headers']['content-length'];
						}
					} else {
						if ( file_exists( $file_path )
						     && ( $filesize
								= filesize( $file_path ) )
						) {
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
		 * @param  array  $files
		 *
		 * @return string
		 */
		public function json_encode_files( $files ) {
			if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
				$files = json_encode( $files, JSON_UNESCAPED_UNICODE );
			} else {
				$files = json_encode( $files );
				if ( function_exists( 'mb_convert_encoding' ) ) {
					$files = preg_replace_callback( '/\\\\u([0-9a-f]{4})/i',
					                                array(
						                                $this,
						                                'json_unscaped_unicode_fallback',
					                                ),
					                                $files );
				}
			}

			return $files;
		}

		/**
		 * Fallback for PHP < 5.4 where JSON_UNESCAPED_UNICODE does not exist.
		 *
		 * @param  array  $matches
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
			preg_match( '%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im',
			            $filepath,
			            $m );
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
		 * @param  string  $file_path
		 *
		 * @return string
		 */
		public function get_file_name( $file_path ) {
			return apply_filters( 'dlm_filemanager_get_file_name',
			                      current( explode( '?', DLM_Utils::basename( $file_path ) ) ) );
		}

		/**
		 * Get file type of give file name
		 *
		 * @param  string  $file_name
		 *
		 * @return string
		 */
		public function get_file_type( $file_name ) {
			return strtolower( substr( strrchr( $file_name, '.' ), 1 ) );
		}

		/**
		 * Gets md5, sha1 and crc32 hashes for a file and store it.
		 *
		 * @param  string  $file_path
		 *
		 * @return array of sizes
		 * @deprecated use hasher service get_file_hashes() instead
		 *
		 */
		public function get_file_hashes( $file_path ) {
			return download_monitor()->service( 'hasher' )
			                         ->get_file_hashes( $file_path );
		}

		/**
		 * Return the secured file path or url of the downloadable file. Should not let restricted files or out of root files to be downloaded.
		 *
		 * @param  string  $file      The file path/url
		 * @param  bool    $relative  Wheter or not to return a relative path. Default is false
		 *
		 * @return array The secured file path/url and restriction status
		 * @since 4.5.9
		 */
		public function get_secure_path( $file, $relative = false ) {
			// ABSPATH needs to be defined
			if ( ! defined( 'ABSPATH' ) ) {
				die;
			}
			$restriction = true;
			// Get file path and remote file status
			list( $file_path, $remote_file, $parser_restriction ) = $this->parse_file_path( $file );
			// Return the file path if the parser restriction is true, most likely the file is not in the allowed paths
			if ( $parser_restriction ) {
				return array( $file_path, $remote_file, $restriction );
			}

			if ( ! $file_path ) {
				return array( $file_path, $remote_file, $restriction );
			}

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
			// Check restricted schemes
			if ( in_array( $file_scheme, $restricted_schemes ) ) {
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
					return array( $file_path, $remote_file, $restriction );
				}
			}

			// Restricted directories
			$restricted_directories = download_monitor()->service( 'file_manager' )->disallowed_wp_directories();
			// Check if the file is in one of the restricted directories
			foreach ( $restricted_directories as $restricted_directory ) {
				if ( false !== strpos( $file_path, $restricted_directory ) ) {
					return array( $file_path, $remote_file, $restriction );
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
			// Add the user defined path to the allowed paths array
			return DLM_Downloads_Path_Helper::get_allowed_paths();
		}

		/**
		 * Return the correct path for the file by comparing the file path string with the allowed paths.
		 *
		 * @param  string  $file_path      The current path of the file
		 * @param  array   $allowed_paths  The allowed paths of the files
		 *
		 * @return string The correct path of the file
		 * @since 4.5.92
		 */
		public function get_correct_path( $file_path, $allowed_paths ) {
			/* We assume first assume that the path is false, as the ABSPATH is always allowed and should always be in the
			* allowed paths.
			*/
			$correct_path = false;

			// Cycle through the allowed paths and check if one of the allowed paths are in the file path.
			if ( ! empty( $allowed_paths ) ) {
				foreach ( $allowed_paths as $allowed_path ) {
					// If we encounter a scenario where the file is in the allowed path, we can trust it is in the correct path, so we should break the loop.
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
		 * @param  string  $file_path  The file's path
		 * @param  bool    $redirect   Whether to redirect the user to the correct path. Default is false.
		 *
		 * @return array|mixed|string|string[]
		 */
		public function check_symbolic_links( $file_path, $redirect = false ) {
			// On Pantheon hosted sites the upload dir is a symbolic link to another location.
			// Make a filter of all shortcuts/symbolik links so that users can attach to them because we do not know what/how the server
			// is configured.
			$shortcuts = apply_filters( 'dlm_upload_shortcuts',
			                            array( wp_get_upload_dir()['basedir'] ) );
			$scheme    = wp_parse_url( get_option( 'home' ),
			                           PHP_URL_SCHEME );
			// Get allowed paths
			$allowed_paths = download_monitor()->service( 'file_manager' )
			                                   ->get_allowed_paths();
			// Get the correct path
			$correct_path = download_monitor()->service( 'file_manager' )
			                                  ->get_correct_path( $file_path,
			                                                      $allowed_paths );

			if ( ! empty( $shortcuts ) ) {
				foreach ( $shortcuts as $shortcut ) {
					if ( is_link( $shortcut )
					     && readlink( $shortcut ) === $correct_path
					) {
						$file_path = str_replace( $correct_path,
						                          $shortcut,
						                          $file_path );
						if ( $redirect ) {
							$file_path = str_replace( ABSPATH,
							                          site_url( '/', $scheme ),
							                          $file_path );
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
					__( 'This file is already protected. Please reload your page.',
					    'download-monitor' ),
					$file
				),                   array( 'status' => 500 ) );
			}

			$reldir = dirname( $file );

			if ( in_array( $reldir, array( '\\', '/', '.' ), true ) ) {
				$reldir = '';
			}

			$protected_dir = path_join( $this->dlm_upload_dir(), $reldir );

			return $this->move_attachment_to_protected( $post_id,
			                                            $protected_dir );
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

			$protected_dir = ltrim( dirname( $file ),
			                        $this->dlm_upload_dir( '/' ) );

			return $this->move_attachment_to_protected( $post_id,
			                                            $protected_dir );
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
			// Only attachments can be moved
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				return new WP_Error( 'not_attachment', sprintf(
					__( 'The post with ID: %d is not an attachment post type.',
					    'download-monitor' ),
					$attachment_id
				),                   array( 'status' => 404 ) );
			}
			// Check if the path is relative to the WP uploads directory
			if ( path_is_absolute( $protected_dir ) ) {
				return new WP_Error( 'protected_dir_not_relative', sprintf(
					__( 'The new path provided: %s is absolute. The new path must be a path relative to the WP uploads directory.',
					    'download-monitor' ),
					$protected_dir
				),                   array( 'status' => 404 ) );
			}

			$meta = empty( $meta_input )
				? wp_get_attachment_metadata( $attachment_id ) : $meta_input;
			$meta = is_array( $meta ) ? $meta : array();

			$file       = get_post_meta( $attachment_id,
			                             '_wp_attached_file',
			                             true );
			$backups    = get_post_meta( $attachment_id,
			                             '_wp_attachment_backup_sizes',
			                             true );
			$upload_dir = wp_upload_dir();
			$old_dir    = dirname( $file );

			if ( in_array( $old_dir, array( '\\', '/', '.' ), true ) ) {
				$old_dir = '';
			}

			if ( $protected_dir === $old_dir ) {
				return true;
			}

			$old_full_path       = path_join( $upload_dir['basedir'],
			                                  $old_dir );
			$protected_full_path = path_join( $upload_dir['basedir'],
			                                  $protected_dir );
			// Try to make the directory if it doesn't exist
			if ( ! wp_mkdir_p( $protected_full_path ) ) {
				return new WP_Error( 'wp_mkdir_p_error', sprintf(
					__( 'There was an error making or verifying the directory at: %s',
					    'download-monitor' ),
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

			$result        = $this->resolve_name_conflict( $new_basenames,
			                                               $protected_full_path,
			                                               $orig_filename );
			$new_basenames = $result['new_basenames'];

			$this->rename_files( $old_basenames,
			                     $new_basenames,
			                     $old_full_path,
			                     $protected_full_path );

			$base_file_name = 0;

			$new_attached_file = path_join( $protected_dir, $new_basenames[0] );
			if ( array_key_exists( 'file', $meta ) ) {
				$meta['file'] = $new_attached_file;
			}
			// Update attached file
			update_post_meta( $attachment_id,
			                  '_wp_attached_file',
			                  $new_attached_file );

			if ( $new_basenames[ $base_file_name ]
			     != $old_basenames[ $base_file_name ]
			) {
				$pattern       = $result['pattern'];
				$replace       = $result['replace'];
				$separator     = '#';
				$orig_basename = ltrim(
					str_replace( $pattern,
					             $replace,
					             $separator . $orig_basename ),
					$separator
				);
				$meta          = $this->update_meta_sizes_file( $meta,
				                                                $new_basenames );
				$this->update_backup_files( $attachment_id,
				                            $backups,
				                            $new_basenames );
			}
			// Update meta
			update_post_meta( $attachment_id,
			                  '_wp_attachment_metadata',
			                  $meta );
			$guid = path_join( $protected_full_path, $orig_basename );
			// Set new guid
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
		public function resolve_name_conflict(
			$new_basenames,
			$protected_full_path,
			$orig_file_name
		) {
			$conflict     = true;
			$number       = 1;
			$separator    = '#';
			$med_filename = $orig_file_name;
			$pattern      = '';
			$replace      = '';

			while ( $conflict ) {
				$conflict = false;
				foreach ( $new_basenames as $basename ) {
					if ( is_file( path_join( $protected_full_path,
					                         $basename ) )
					) {
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
							str_replace( $pattern,
							             $replace,
							             $separator . implode( $separator,
							                                   $new_basenames ) ),
							$separator
						)
					);
				}
			}

			return array(
				'new_basenames' => $new_basenames,
				'pattern'       => $pattern,
				'replace'       => $replace,
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
		public function rename_files(
			$old_basenames,
			$new_basenames,
			$old_dir,
			$protected_dir
		) {
			$unique_old_basenames
				= array_values( array_unique( $old_basenames ) );
			$unique_new_basenames
				= array_values( array_unique( $new_basenames ) );
			$i  = count( $unique_old_basenames );

			while ( $i -- ) {
				$old_fullpath = path_join( $old_dir,
				                           $unique_old_basenames[ $i ] );
				$new_fullpath = path_join( $protected_dir,
				                           $unique_new_basenames[ $i ] );
				if ( is_file( $old_fullpath ) ) {
					rename( $old_fullpath, $new_fullpath );

					if ( ! is_file( $new_fullpath ) ) {
						return new WP_Error(
							'rename_failed',
							sprintf(
								__( 'Rename failed when trying to move file from: %s, to: %s',
								    'download-monitor' ),
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
		public function update_backup_files(
			$attachment_id,
			$backups,
			$new_basenames
		) {
			if ( is_array( $backups ) ) {
				$i                = 0;
				$l                = count( $backups );
				$new_backup_sizes = array_slice( $new_basenames, - $l, $l );

				foreach ( $backups as $size => $data ) {
					$backups[ $size ]['file'] = $new_backup_sizes[ $i ++ ];
				}
				update_post_meta( $attachment_id,
				                  '_wp_attachment_backup_sizes',
				                  $backups );
			}
		}

		/**
		 * Disallowed WP directories
		 *
		 * @return array
		 * @since 5.0.0
		 */
		public function disallowed_wp_directories() {
			$extra_disallowed_dirs = apply_filters( 'dlm_restricted_admin_folders', array() );
			$base_disalowed_dirs   = array(
				'wp-admin',
				'wp-includes',
				'mail',
				'etc',
			);

			foreach ( $base_disalowed_dirs as $key => $dir ) {
				$base_disalowed_dirs[ $key ] = DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
			}

			return array_merge( $base_disalowed_dirs, $extra_disallowed_dirs );
		}
	}
}
