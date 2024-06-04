<?php
/**
 * Edit DLM Download Paths
 *
 * @package    Download Monitor
 * @subpackage Multisite
 * @since      5.0.0
 */


if ( ! current_user_can( 'manage_sites' ) ) {
	wp_die( __( 'Sorry, you are not allowed to edit this site.' ) );
}

get_current_screen()->add_help_tab( get_site_screen_help_tab_args() );
get_current_screen()->set_help_sidebar( get_site_screen_help_sidebar_content() );

$id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;

if ( ! $id ) {
	wp_die( __( 'Invalid site ID.' ) );
}

$details = get_site( $id );
if ( ! $details ) {
	wp_die( __( 'The requested site does not exist.' ) );
}

if ( ! can_edit_network( $details->site_id ) ) {
	wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403 );
}

$parsed_scheme = parse_url( $details->siteurl, PHP_URL_SCHEME );
$is_main_site  = is_main_site( $id );

if ( isset( $_GET['update'] ) ) {
	$messages = array();
	if ( 'updated' === $_GET['update'] ) {
		$messages[] = __( 'Site info updated.' );
	}
}
// Handle the form submission.
$this->handle_form_submission();
// Switch to the site.
switch_to_blog( $id );
// Used in the HTML title tag.
/* translators: %s: Site title. */
$title = sprintf( __( 'Download Paths for: %s' ), esc_html( $details->blogname ) );

$parent_file  = 'sites.php';
$submenu_file = 'sites.php';

require_once ABSPATH . 'wp-admin/admin-header.php';

?>

	<div class="wrap">
		<h1 id="edit-site"><?php
			echo $title; ?></h1>
		<p class="edit-site-actions">
			<a href="<?php
			echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php
				_e( 'Visit' ); ?></a> |
			<a href="<?php
			echo esc_url( get_admin_url( $id ) ); ?>"><?php
				_e( 'Dashboard' ); ?></a>
		</p>
		<?php

		network_edit_site_nav(
			array(
				'blog_id'  => $id,
				'selected' => 'dlm-paths',
			)
		);

		if ( ! empty( $messages ) ) {
			$notice_args = array(
				'type'        => 'success',
				'dismissible' => true,
				'id'          => 'message',
			);

			foreach ( $messages as $msg ) {
				wp_admin_notice( $msg, $notice_args );
			}
		}

		?>
		<form method='post' action='<?php
		echo 'admin.php?page=download-monitor-paths&id=' . absint( $_GET['id'] ); ?>&action=update-site'>
			<?php
			wp_nonce_field( 'edit-site' ); ?>
			<input type="hidden" name="id" value="<?php
			echo esc_attr( $id ); ?>"/>
			<input type="hidden" name="page" value="download-monitor-paths">
			<?php

			$this->table = new DLM_Downloads_Path_Table();
			if ( isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] && isset( $_REQUEST['url'] ) ) {
				$this->edit_screen( (int) $_REQUEST['url'] );
			} else {
				// Show list table.
				$this->table->prepare_items();
				$this->table->render_views();
				$this->table->display();
			}


			/**
			 * Fires at the end of the site info form in network admin.
			 *
			 * @param  int  $id  The site ID.
			 *
			 * @since 5.6.0
			 *
			 */
			do_action( 'network_site_info_form', $id );

			submit_button();
			?>
		</form>

	</div>
<?php
require_once ABSPATH . 'wp-admin/admin-footer.php';
restore_current_blog();
