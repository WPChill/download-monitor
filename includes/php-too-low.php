<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function dlm_admin_notice_php_version() {

	$version_parts     = explode( '.', phpversion() );
	$user_version_nice = '';
	if ( ! empty( $version_parts[0] ) ) {
		$user_version_nice .= $version_parts[0];
	}
	if ( ! empty( $version_parts[1] ) ) {
		$user_version_nice .= '.' . $version_parts[1];
	}

	?>
	<div class="notice notice-error is-dismissible">
		<h3><?php echo esc_html__( 'PHP Version too low!', 'download-monitor' ); ?></h3>
		<p>
		<?php
			printf(
				esc_html_e( "Download Monitor can't be loaded because it needs at least %1\$s but the server that is hosting your WordPress website is running %2\$s", 'download-monitor' ),
				'<strong>' . sprintf( esc_html_e( 'PHP Version %s', 'download-monitor' ), '5.3' ) . '</strong>',
				'<strong>' . sprintf( esc_html_e( 'PHP Version %s', 'download-monitor' ), esc_html( $user_version_nice ) ) . '</strong>'
			);
		?>
			</p>
		<p>
		<?php
		printf(
			esc_html_e( "You can learn more about why it's important that you update and get tips on how to update by %s", 'download-monitor' ),
			'<a href="https://www.download-monitor.com/kb/minimum-required-php-version/" target="_blank">' . esc_html_e( 'clicking this link', 'download-monitor' ) . '</a>'
		);
		?>
			</p>
		<p><?php echo esc_html__( "After you've upgraded your PHP version, Download Monitor will automatically load and work.", 'download-monitor' ); ?></p>
		<p></p>
	</div>
	<?php
}

add_action( 'admin_notices', 'dlm_admin_notice_php_version', 8 );
