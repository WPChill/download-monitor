<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'DLM_Hasher' ) ) {

	class DLM_Hasher {

		/**
		 * Get array with hashes for given file path.
		 * Array will always contain all hash keys but hash values will only be set if user option for hash is turned on.
		 *
		 * @param  string  $file_path
		 *
		 * @return array
		 */
		public function get_file_hashes( $file_path ) {
			$md5    = false;
			$sha1   = false;
			$sha256 = false;
			$crc32b = false;

			if ( $file_path ) {
				list( $file_path, $remote_file ) = download_monitor()
					->service( 'file_manager' )->parse_file_path( $file_path );

				if ( ! empty( $file_path ) ) {
					if ( ! $remote_file
					     || apply_filters( 'dlm_allow_remote_hash_file', false )
					) {

						// Check for enabled md5 hash and generate it
						if ( $this->is_hash_enabled( 'md5' ) ) {
							$md5 = $this->generate_hash( 'md5', $file_path );
						}

						// Check for enabled sha1 hash and generate it
						if ( $this->is_hash_enabled( 'sha1' ) ) {
							$sha1 = $this->generate_hash( 'sha1', $file_path );
						}

						// Check for enabled sha256 hash and generate it
						if ( $this->is_hash_enabled( 'sha256' ) ) {
							$sha256 = $this->generate_hash( 'sha256',
								$file_path );
						}

						// Check for enabled crc32b hash and generate it
						if ( $this->is_hash_enabled( 'crc32b' ) ) {
							$crc32b = $this->generate_hash( 'crc32b',
								$file_path );
						}

					}
				}
			}

			// Return generated hashes
			return apply_filters( "dlm_file_hashes", array(
				'md5'    => $md5,
				'sha1'   => $sha1,
				'sha256' => $sha256,
				'crc32b' => $crc32b,
			), $file_path );
		}

		/**
		 * Generate hash of $type for $file_path
		 *
		 * @param  string  $type
		 * @param  string  $file_path
		 *
		 * @return string
		 */
		public function generate_hash( $type, $file_path ) {

			$file_manager  = download_monitor()->service( 'file_manager' );
			$allowed_paths = $file_manager->get_allowed_paths();
			$common_path   = DLM_Utils::longest_common_path( $allowed_paths );
			$hash          = "";

			// Check to see if the path is an absolute one or a relative one, in which case we need to make it absolute
			if ( $common_path && strlen( $common_path ) > 1
			     && false === strpos( $file_path, $common_path )
			) {
				$file_path = $common_path . $file_path;
			}

			// Cycle through has types and generate hash
			switch ( $type ) {
				case 'md5':
					$hash = hash_file( 'md5', $file_path );
					break;
				case 'sha1':
					$hash = hash_file( 'sha1', $file_path );
					break;
				case 'sha256':
					$hash = hash_file( 'sha256', $file_path );
					break;
				case 'crc32b':
					$hash = hash_file( 'crc32b', $file_path );
					break;
			}

			return $hash;
		}

		/**
		 * Check if generation of given hash $type is enabled
		 *
		 * @param  string  $type  The type of hash taken into consideration
		 *
		 * @return bool
		 */
		public function is_hash_enabled( $type ) {
			/**
			 * Hook to disable generation of hash
			 *
			 * @hook  dlm_generate_hash_ . $type
			 *
			 * @hooked: DLM_Backwards_Compatibility->hashes_compatibility() - 5
			 *
			 * @since 4.9.6
			 */
			return apply_filters( 'dlm_generate_hash_' . $type, false );
		}

		/**
		 * Get available and enabled hashes
		 *
		 * @return array
		 */
		public function get_available_hashes() {
			$hashes = array( 'md5', 'sha1', 'crc32b', 'sha256' );
			// Retrieve the hashes that are enabled
			foreach ( $hashes as $hash_key => $hash ) {
				if ( ! $this->is_hash_enabled( $hash ) ) {
					unset( $hashes[ $hash_key ] );
				}
			}

			return $hashes;
		}
	}
}
