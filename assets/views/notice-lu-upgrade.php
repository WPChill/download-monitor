<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="dlm-lu-upgrade-notice">
	<h3><?php echo esc_html__('It looks like you upgraded to the latest version of Download Monitor from a legacy version (3.x)', 'download-monitor' ); ?></h3>
	<p><?php printf( esc_html__( "Currently your downloads don't work like they should, we need to %s before they'll work again.", 'download-monitor' ), sprintf( '<strong>%s</strong>', esc_html__( 'upgrade your downloads', 'download-monitor' ) ) ); ?></p>
	<p><?php printf( esc_html__( "We've created an upgrading tool that will do all the work for you. You can read more about this tool on %sour website (click here)%s or start the upgrade now.", 'download-monitor'), '<a href="https://www.download-monitor.com/kb/legacy-upgrade?utm_source=plugin&utm_medium=dlm-lu-upgrade-notice&utm_campaign=dlm-lu-more-information" target="_blank">', '</a>' ); ?></p>
    <a href="<?php echo esc_url( admin_url( 'options.php?page=dlm_legacy_upgrade' ) ); ?>" class="button"><?php echo esc_html__( 'Take me to the Upgrade Tool', 'download-monitor' ); ?></a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dlm_download&dlm_lu_hide_notice=1' ) ); ?>" class="dlm-lu-upgrade-notice-hide"><?php echo esc_html__( 'hide notice', 'download-monitor' ); ?></a>
</div>
