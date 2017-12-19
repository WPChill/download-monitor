<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="dlm-lu-upgrade-notice">
	<h3>It looks like you upgraded to the latest version of Download Monitor from a legacy version (3.x)</h3>
	<p>Currently your downloads don't work like they should, we need to <strong>upgrade your downloads</strong> before they'll work again.</p>
	<p>We've created an upgrading tool that will do all the work for you. You can read more about this tool on <a href="https://www.download-monitor.com/kb/legacy-upgrade?utm_source=plugin&utm_medium=dlm-lu-upgrade-notice&utm_campaign=dlm-lu-more-information" target="_blank">our website (click here)</a> or start the upgrade now.</p>
    <a href="<?php echo admin_url( 'options.php?page=dlm_legacy_upgrade' ); ?>" class="button">Take me to the Upgrade Tool</a>
    <a href="<?php echo admin_url( 'edit.php?post_type=dlm_download&dlm_lu_hide_notice=1' ); ?>" class="dlm-lu-upgrade-notice-hide">hide notice</a>
</div>
