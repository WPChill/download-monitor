<?php

class DLM_LU_Upgrader {

	/**
	 * Upgrade a single download item. Do NOT call this without it being in queue.
	 *
	 * @param $download_id
	 *
	 * @return bool
	 */
	public function upgrade_download( $download_id ) {
		global $wpdb;

		$queue = new DLM_LU_Queue();

		$legacy_tables = $queue->get_legacy_tables();

		// mark download upgrading
		$queue->mark_download_upgrading( $download_id );

		// get legacy download information
		$legacy_download = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . $legacy_tables['files'] . "` WHERE `id` = %d ;", $download_id ) );
		error_log( print_r( $legacy_download, 1 ), 0 );

		// create new Download object
		// create download object
		$download = new DLM_Download();
		$download->set_status( 'publish' );
		$download->set_author( 1 );

		// get user ID of user that created legacy download
		if ( ! empty( $legacy_download->user ) ) {
			$user = get_user_by( 'login', $legacy_download->user );
			if ( $user ) {
				$download->set_author( $user->ID );
			}
		}

		// set title & description
		$download->set_title( $legacy_download->title );
		$download->set_description( $legacy_download->file_description );
		$download->set_excerpt( "" );

		// set download options
		$download->set_featured( false ); // there was no featured in legacy
		$download->set_redirect_only( false ); // there was no redirect only in legacy
		$download->set_members_only( ( 0 === absint( $legacy_download->members ) ) );

		// set download count
		$download->set_download_count( absint( $legacy_download->hits ) );

		// store new download
		download_monitor()->service( 'download_repository' )->persist( $download );

		// create new version
		/** @var DLM_Download_Version $new_version */
		$version = new DLM_Download_Version();

		// set download id on version
		$version->set_download_id( $download->get_id() );

		// set version name on version
		$version->set_version( $legacy_download->dlversion );

		// set mirrors
		$urls = array();
		if ( $legacy_download->mirrors ) {
			$urls = explode( "\n", $legacy_download->mirrors );
		}
		$urls = array_filter( array_merge( array( $legacy_download->filename ), (array) $urls ) );
		$version->set_mirrors( $urls );

		// set other version data
		$version->set_filesize( "" ); // empty filesize so it's calculated on persist
		$version->set_author( $download->get_author() );
		$version->set_date( new DateTime( $legacy_download->postDate ) );

		// persist new version
		download_monitor()->service( 'version_repository' )->persist( $version );

		// clear download transient
		download_monitor()->service( 'transient_manager' )->clear_versions_transient( $download->get_id() );

		// upgrade category & tags

		// upgrade any custom meta data

		// mark download as upgraded
		$queue->mark_download_upgraded( $download_id, $download->get_id() );

		return true;
	}

}