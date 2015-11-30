jQuery( function ( $ ) {
    $( document ).ready( function () {
        // dlm_last_settings_tab is only set when settings are saved and the page is reloaded
        if ( typeof dlm_last_settings_tab !== 'undefined' ) {
            var elm = $( '.nav-tab-wrapper a[href="#settings-' + dlm_last_settings_tab + '"]' );
            if ( typeof elm !== 'undefined' ) {
                set_active_tab( elm );
            }
        }
    });

    function set_active_tab ( elm ) {
        $( '.settings_panel' ).hide();
        $( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
        $( elm.attr( 'href' ) ).show();
        elm.addClass( 'nav-tab-active' );

        var tab = elm.attr( 'href' ).replace( "#settings-" , "" );
        $( '#setting-dlm_last_settings_tab' ).val( tab );
    }

    $( '.nav-tab-wrapper a' ).click( function () {
        set_active_tab( $( this ) );
        return false;
    } );
    $( '#setting-dlm_default_template' ).change( function () {
        if ( $( this ).val() == 'custom' ) {
            $( '#setting-dlm_custom_template' ).closest( 'tr' ).show();
        } else {
            $( '#setting-dlm_custom_template' ).closest( 'tr' ).hide();
        }
    } ).change();
    $( '#setting-dlm_enable_logging' ).change( function () {
        if ( $( this ).is(":checked") === true ) {
            $( '#setting-dlm_count_unique_ips' ).closest( 'tr' ).show();
        } else {
            $( '#setting-dlm_count_unique_ips' ).closest( 'tr' ).hide();
        }
    } ).change();

    $( '.nav-tab-wrapper a:first' ).click();

    $( '.dlm-notice.is-dismissible' ).on( 'click', '.notice-dismiss', function ( event ) {
        //$( '#dlm-ajax-nonce' ).val()
        var notice_el = $( this ).closest( '.dlm-notice' );

        var notice = notice_el.attr( 'id' );
        var notice_nonce = notice_el.attr( 'data-nonce' );
        $.post(
            ajaxurl,
            {
                action: 'dlm_dismiss_notice',
                nonce: notice_nonce,
                notice: notice
            },
            function ( response ) {}
        )
    } );
} );