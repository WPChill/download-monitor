jQuery( function ( $ ) {

	function dlm_set_active_tab( elm ) {
		if ( $( elm ).hasClass( 'nav-tab-active' ) ) {
			return false;
		}

		$( '.settings_panel' ).hide();
		$( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		$( $( elm ).attr( 'href' ) ).show();
		$( elm ).addClass( 'nav-tab-active' );
		$( '#setting-dlm_settings_tab_saved' ).val( $( elm ).attr( 'href' ).replace( "#settings-", "" ) );
		return true;
	}

	$( '.nav-tab-wrapper a' ).click( function () {
		return dlm_set_active_tab( $( this ) );
	} );

	$( '#setting-dlm_default_template' ).change( function () {
		if ( $( this ).val() == 'custom' ) {
			$( '#setting-dlm_custom_template' ).closest( 'tr' ).show();
		} else {
			$( '#setting-dlm_custom_template' ).closest( 'tr' ).hide();
		}
	} ).change();

	$( '#setting-dlm_enable_logging' ).change( function () {
		if ( $( this ).is( ":checked" ) === true ) {
			$( '#setting-dlm_count_unique_ips' ).closest( 'tr' ).show();
		} else {
			$( '#setting-dlm_count_unique_ips' ).closest( 'tr' ).hide();
		}
	} ).change();

	// load tab of hash, if no hash is present load first tab.
	if ( window.location.hash ) {
		var active_tab = window.location.hash.replace( '#', '' );
		$( '.nav-tab-wrapper a#dlm-tab-' + active_tab ).click();
	} else {
		$( '.nav-tab-wrapper a:first' ).click();
	}

	// listen to hash changes
	$( window ).bind( 'hashchange', function ( e ) {
		var active_tab = window.location.hash.replace( '#', '' );
		$( '.nav-tab-wrapper a#dlm-tab-' + active_tab ).click();
	} );

	$( document ).ready( function () {
		// dlm_last_settings_tab is only set when settings are saved and the page is reloaded
		if ( typeof dlm_settings_tab_saved !== 'undefined' ) {
			var elm = $( '.nav-tab-wrapper a[href="#settings-' + dlm_settings_tab_saved + '"]' );
			if ( typeof elm !== 'undefined' ) {
				dlm_set_active_tab( elm );
			}
		}
	} );

} );