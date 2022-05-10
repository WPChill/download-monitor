<?php

class DLM_WordPress_Download_Repository implements DLM_Download_Repository {

	/**
	 * Filter query arguments for download WP_Query queries
	 *
	 * @param array $args
	 * @param int   $limit
	 * @param int   $offset
	 *
	 * @return array
	 */
	private function filter_query_args( $args = array(), $limit = 0, $offset = 0 ) {

		// limit must be int, not abs
		$limit = intval( $limit );

		// most be absint
		$offset = absint( $offset );

		// start with removing reserved keys
		unset( $args['post_type'] );
		unset( $args['posts_per_page'] );
		unset( $args['offset'] );
		unset( $args['paged'] );
		unset( $args['nopaging'] );

		// setup our reserved keys
		$args['post_type']      = 'dlm_download';
		$args['posts_per_page'] = - 1;

		// set limit if set
		if ( $limit > 0 ) {
			$args['posts_per_page'] = $limit;
		}

		// set offset if set
		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}

		return apply_filters( 'dlm_backwards_compatibility_query_args', $args );
	}

	/**
	 * Returns number of rows for given filters
	 *
	 * @param array $filters
	 *
	 * @return int
	 */
	public function num_rows( $filters = array() ) {
		$q = new WP_Query();
		$q->query( $this->filter_query_args( $filters ) );

		return $q->found_posts;
	}

	/**
	 * Retrieve single download
	 *
	 * @param int $id
	 *
	 * @return DLM_Download
	 * @throws Exception
	 */
	public function retrieve_single( $id ) {

		$downloads = $this->retrieve( array( 'p' => absint( $id ) ) );

		if ( count( $downloads ) != 1 ) {
			throw new Exception( 'Download not found' );
		}

		return array_shift( $downloads );
	}

	/**
	 * Retreieve the version download count
	 *
	 * @param  mixed $version_id
	 * @return array
	 */
	public function retrieve_download_count( $download_id ) {
		global $wpdb;

		$download_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(`ID`) FROM {$wpdb->download_log} WHERE download_id = %s", $download_id ) );

		return apply_filters( 'dlm_add_meta_download_count', $download_count, $download_id );
	}

	/**
	 * Retrieve downloads
	 *
	 * @param array $filters
	 * @param int   $limit
	 * @param int   $offset
	 *
	 * @return array<DLM_Download>
	 */
	public function retrieve( $filters = array(), $limit = 0, $offset = 0 ) {

		// WPML gives original website language in AJAX Requests
		// So we handle all the languages, as the download will be searched based on the post ID which will be unique
		// First, let's check if WPML is installed and activated - check for it's class.
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;
			$sitepress->switch_lang( 'all' );
		}

		// In order for the query to properly work when ordering by count we need to add the post_type to the filters
		if ( ! isset( $filters['post_type'] ) ) {
			$filters['post_type'] = 'dlm_download';
		}

		$q = new WP_Query();

		do_action( 'dlm_backwards_compatibility', $filters );

		$posts = $q->query( $this->filter_query_args( $filters, $limit, $offset ) );

		$items = $this->create_downloads_from_array( $posts );

		do_action( 'dlm_reset_postdata', $filters );

		return $items;
	}

	/**
	 * @param DLM_Download $download
	 *
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function persist( $download ) {

		// check if new download or existing
		if ( 0 == $download->get_id() ) {

			// create
			$download_id = wp_insert_post(
				array(
					'post_title'   => $download->get_title(),
					'post_content' => $download->get_description(),
					'post_excerpt' => $download->get_excerpt(),
					'post_author'  => $download->get_author(),
					'post_type'    => 'dlm_download',
					'post_status'  => $download->get_status(),
				)
			);

			if ( is_wp_error( $download_id ) ) {
				throw new \Exception( 'Unable to insert download in WordPress database' );
			}
			// set new vehicle ID.
			$download->set_id( $download_id );

		} else {

			// update.
			$download_id = wp_update_post(
				array(
					'ID'           => $download->get_id(),
					'post_title'   => $download->get_title(),
					'post_content' => $download->get_description(),
					'post_excerpt' => $download->get_excerpt(),
					'post_author'  => $download->get_author(),
					'post_status'  => $download->get_status(),
				)
			);

			if ( is_wp_error( $download_id ) ) {
				throw new \Exception( 'Unable to update download in WordPress database' );
			}
		}

		// persist 'Download Options'.
		update_post_meta( $download_id, '_featured', ( ( $download->is_featured() ) ? 'yes' : 'no' ) );
		update_post_meta( $download_id, '_members_only', ( ( $download->is_members_only() ) ? 'yes' : 'no' ) );
		update_post_meta( $download_id, '_redirect_only', ( ( $download->is_redirect_only() ) ? 'yes' : 'no' ) );

		// clear versions transient.
		download_monitor()->service( 'transient_manager' )->clear_versions_transient( $download_id );

		return true;
	}


	/**
	 * Get ordered by download count Downloads
	 *
	 * @param  mixed $order The order of the downloads, can take values DESC or ASC.
	 * @param  mixed $limit How many rows should we get.
	 * @param  mixed $offset From what entry should the retriever begin.
	 * @param  mixed $array_return Specify is the return should be the query results or an array of Download objects
	 * @return mixed
	 */
	public function get_orderly_downloads( $order = 'DESC', $limit = 15, $offset = 0 ) {

		global $wpdb;

		if ( ! DLM_Utils::table_checker( $wpdb->download_log ) ) {

			return false;
		}

		if ( 0 === $limit ) {
			$sql_limit = '';
		} else {
			$offset    = absint( $limit ) * absint( $offset );
			$sql_limit = "LIMIT {$offset},{$limit}";
		}

		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'DESC';
		}

		$results = $wpdb->get_results( "SELECT posts.ID, posts.post_title, posts.post_status, posts.post_name, posts.post_author, posts.post_content, posts.post_excerpt, COUNT(dlm_logs.ID) as counts FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->download_log} dlm_logs ON posts.ID = dlm_logs.download_id WHERE posts.post_type = 'dlm_download' GROUP BY posts.ID ORDER BY counts {$order} {$sql_limit};" );

		$items = $this->create_downloads_from_array( $results );

		return $items;

	}

	/**
	 * Create an array of Downloads objects from an array containing DB info about Downloads CPT
	 *
	 * @param  mixed $downloads Array, usually the result of WP_Query or get_posts.
	 * @return array
	 */
	public function create_downloads_from_array( $downloads ) {

		$items = array();

		if ( null !== $downloads && ! empty( $downloads ) ) {

			foreach ( $downloads as $post ) {

				$download = download_monitor()->service( 'download_factory' )->make( ( ( 1 == get_post_meta( $post->ID, '_is_purchasable', true ) ) ? 'product' : 'regular' ) );
				$download->set_id( $post->ID );
				$download->set_status( $post->post_status );
				$download->set_title( $post->post_title );
				$download->set_slug( $post->post_name );
				$download->set_author( $post->post_author );
				$download->set_description( $post->post_content );
				$download->set_excerpt( wpautop( do_shortcode( $post->post_excerpt ) ) );
				$download->set_redirect_only( ( 'yes' === get_post_meta( $post->ID, '_redirect_only', true ) ) );
				$download->set_featured( ( 'yes' === get_post_meta( $post->ID, '_featured', true ) ) );
				$download->set_members_only( ( 'yes' === get_post_meta( $post->ID, '_members_only', true ) ) );
				$download->set_download_count( absint( $this->retrieve_download_count( $post->ID ) ) );

				// This is added for backwards compatibility but will be removed in a later version!
				$download->post = $post;

				// add download to return array.
				$items[] = $download;
			}
		}

		return $items;

	}

}
