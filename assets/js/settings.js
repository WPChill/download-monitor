jQuery( function ( $ ) {

	function dlm_set_active_tab( elm ) {
		if ( $( elm ).hasClass( 'nav-tab-active' ) ) {
			return false;
		}

		var tabID = $( elm ).attr( 'href' ).replace( dlm_settings_vars.settings_url, "" );

		$( '.settings_panel' ).hide();
		$( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		$( tabID ).show();
		$( elm ).addClass( 'nav-tab-active' );
		$( '#setting-dlm_settings_tab_saved' ).val( tabID.replace( "#settings-", "" ) );
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
			var elm = $( '.nav-tab-wrapper a[href="' + dlm_settings_vars.settings_url + '#settings-' + dlm_settings_tab_saved + '"]' );
			if ( typeof elm !== 'undefined' ) {
				dlm_set_active_tab( elm );
			}
		}

		// load lazy-select elements
		$.each( $( '.dlm-lazy-select' ), function () {

			var lazy_select_el = $( this );

			// add AJAX loader
			$( '<span>' ).addClass( 'dlm-lazy-select-loader' ).append(
				$( '<img>' ).attr( 'src', dlm_settings_vars.img_path + 'ajax-loader.gif' )
			).insertAfter( lazy_select_el );

			// load data
			$.post( ajaxurl, {
				action: 'dlm_settings_lazy_select',
				nonce: dlm_settings_vars.lazy_select_nonce,
				option: lazy_select_el.attr( 'name' )
			}, function ( response ) {

				// remove current option(s)
				lazy_select_el.find( 'option' ).remove();

				// set new options
				if ( response ) {
					var selected = lazy_select_el.data( 'selected' );
					for ( var i = 0; i < response.length; i ++ ) {
						var opt = $( '<option>' ).attr( 'value', response[i].key ).html( response[i].lbl );
						if ( selected === response[i].key ) {
							opt.attr( 'selected', 'selected' );
						}
						lazy_select_el.append( opt );
					}
				}

				// remove ajax loader
				lazy_select_el.parent().find( '.dlm-lazy-select-loader' ).remove();

			} );


		} );

	} );

} );