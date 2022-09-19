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
				'menu_icon'           => 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iNTEyLjAwMDAwMHB0IiBoZWlnaHQ9IjUxNi4wMDAwMDBwdCIgdmlld0JveD0iMCAwIDUxMi4wMDAwMDAgNTE2LjAwMDAwMCIKIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiPgoKPGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMC4wMDAwMDAsNTE2LjAwMDAwMCkgc2NhbGUoMC4xMDAwMDAsLTAuMTAwMDAwKSIKZmlsbD0iIzAwMDAwMCIgc3Ryb2tlPSJub25lIj4KPHBhdGggZD0iTTE3MzAgMjY2NCBsMCAtOTM1IDQ5OCAzIDQ5NyA0IDk1IDI2IGMyODUgNzcgNDg5IDI0MCA2MDYgNDgzIDYwCjEyNCA4NCAyMjQgOTEgMzc1IDEyIDI4OCAtNzIgNTE3IC0yNjEgNzA2IC0xMDMgMTAzIC0yMDEgMTY2IC0zMzQgMjE0IC0xNTQKNTYgLTIwNyA2MCAtNzIzIDYwIGwtNDY5IDAgMCAtOTM2eiBtOTcxIDQwOSBjMTI1IC02MiAyMTIgLTE4OSAyMjggLTMzNCAyNgotMjMyIC03MyAtNDIwIC0yNTUgLTQ4OSAtNDYgLTE3IC03OCAtMjAgLTIwOSAtMjAgbC0xNTUgMCAwIDQ0MSAwIDQ0MSAxNjMgLTMKYzE2MCAtNCAxNjQgLTUgMjI4IC0zNnoiLz4KPC9nPgo8L3N2Zz4K'
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

