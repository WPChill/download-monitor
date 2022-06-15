<?php

class DLM_Post_Type_Manager {

	/**
	 * Setup hooks
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'register_dlm_download_post_meta_rest' ) );
		add_action( 'init', array( $this, 'register' ), 10 );

		add_filter( 'views_edit-dlm_download', array( $this, 'add_extensions_tab' ), 10, 1 );

		add_action( 'current_screen', array( $this, 'disable_geditor'));
	}

	/**
	 * Register Post Types
	 */
	public function register() {

		// Register Download Post Type
		register_post_type( "dlm_download",
			apply_filters( 'dlm_cpt_dlm_download_args', array(
				'labels'              => array(
					'all_items'          => __( 'All Downloads', 'download-monitor' ),
					'name'               => __( 'Downloads', 'download-monitor' ),
					'singular_name'      => __( 'Download', 'download-monitor' ),
					'add_new'            => __( 'Add New', 'download-monitor' ),
					'add_new_item'       => __( 'Add Download', 'download-monitor' ),
					'edit'               => __( 'Edit', 'download-monitor' ),
					'edit_item'          => __( 'Edit Download', 'download-monitor' ),
					'new_item'           => __( 'New Download', 'download-monitor' ),
					'view'               => __( 'View Download', 'download-monitor' ),
					'view_item'          => __( 'View Download', 'download-monitor' ),
					'search_items'       => __( 'Search Downloads', 'download-monitor' ),
					'not_found'          => __( 'No Downloads found', 'download-monitor' ),
					'not_found_in_trash' => __( 'No Downloads found in trash', 'download-monitor' ),
					'parent'             => __( 'Parent Download', 'download-monitor' )
				),
				'description'         => __( 'This is where you can create and manage downloads for your site.', 'download-monitor' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'publish_posts'       => 'manage_downloads',
					'edit_posts'          => 'manage_downloads',
					'edit_others_posts'   => 'manage_downloads',
					'delete_posts'        => 'manage_downloads',
					'delete_others_posts' => 'manage_downloads',
					'read_private_posts'  => 'manage_downloads',
					'edit_post'           => 'manage_downloads',
					'delete_post'         => 'manage_downloads',
					'read_post'           => 'manage_downloads'
				),
				'publicly_queryable'  => false,
				'exclude_from_search' => ( 1 !== absint( get_option( 'dlm_wp_search_enabled', 0 ) ) ),
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => apply_filters( 'dlm_cpt_dlm_download_supports', array(
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'custom-fields'
				) ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false,
				'menu_position'       => 35,
				'show_in_rest'        => true,
				'menu_icon'           => 'data:image/png;base64,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'
			) )
		);

		// Register Download Version Post Type
		register_post_type( "dlm_download_version",
			apply_filters( 'dlm_cpt_dlm_download_version_args', array(
				'labels'              => array(
					'all_items'          => __( 'All Download Versions', 'download-monitor' ),
					'name'               => __( 'Download Versions', 'download-monitor' ),
					'singular_name'      => __( 'Download Version', 'download-monitor' ),
					'add_new'            => __( 'Add New', 'download-monitor' ),
					'add_new_item'       => __( 'Add Download Version', 'download-monitor' ),
					'edit'               => __( 'Edit', 'download-monitor' ),
					'edit_item'          => __( 'Edit Download Version', 'download-monitor' ),
					'new_item'           => __( 'New Download Version', 'download-monitor' ),
					'view'               => __( 'View Download Version', 'download-monitor' ),
					'view_item'          => __( 'View Download Version', 'download-monitor' ),
					'search_items'       => __( 'Search Download Versions', 'download-monitor' ),
					'not_found'          => __( 'No Download Versions found', 'download-monitor' ),
					'not_found_in_trash' => __( 'No Download Versions found in trash', 'download-monitor' ),
					'parent'             => __( 'Parent Download Version', 'download-monitor' )
				),
				'public'              => false,
				'show_ui'             => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_nav_menus'   => false
			) )
		);

		do_action( 'dlm_after_post_type_register' );


	}

	public function register_dlm_download_post_meta_rest() {
		register_rest_field( 'dlm_download', 'featured', array(
			'get_callback' => function( $post_arr ) {
				return get_post_meta( $post_arr['id'], '_featured', true );

			},
		));
		// @todo: Delete after testing, download_count post meta won't exist anymore
		/* register_rest_field( 'dlm_download', 'download_count', array(
			'get_callback' => function( $post_arr ) {
				return get_post_meta( $post_arr['id'], '_download_count', true );

			},
		)); */
		register_rest_field( 'dlm_download', 'author', array(
			'get_callback' => function( $post_arr ) {
				return get_the_author_meta( 'nickname', $post_arr['author'] );
			},
		));

	}

	public function add_extensions_tab( $views ) {
		$this->display_extension_tab();
		return $views;
	}

	public function display_extension_tab() {
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			$tabs = array(
				'downloads'       => array(
					'name'     => __('Downloads','download-monitor'),
					'url'      => admin_url( 'edit.php?post_type=dlm_download' ),
					'priority' => '1'
				),
				'suggest_feature' => array(
					'name'     => esc_html__( 'Suggest a feature', 'download-monitor' ),
					'icon'     => 'dashicons-external',
					'url'      => 'https://forms.gle/3igARBBzrbp6M8Fc7',
					'target'   => '_blank',
					'priority' => '60'
				),
			);

			if ( current_user_can( 'install_plugins' ) ) {
				$tabs[ 'extensions' ] = array(
					'name'     => esc_html__( 'Extensions', 'download-monitor' ),
					'url'      => admin_url( 'edit.php?post_type=dlm_download&page=dlm-extensions' ),
					'priority' => '5',
				);
			}

			/**
			 * Hook for DLM CPT table view tabs
			 *
			 * @hooked DLM_Admin_Extensions dlm_cpt_tabs()
			 */
			$tabs = apply_filters( 'dlm_add_edit_tabs', $tabs );

			uasort( $tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );

			DLM_Admin_Helper::dlm_tab_navigation($tabs,'downloads');
			?>
		</h2>
		<br/>
		<?php
	}

	/**
	 * Explicitely disable the gutenberg editor for downloads
	 * This is needed because the download edit page is not compatible with the gutenberg editor
	 */
	public function disable_geditor() {

		$screen = get_current_screen();
		if( $screen->post_type == 'dlm_download' ) {
			add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		}

		}

}

