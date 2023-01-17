jQuery( function ( $ ) {

    $( '.dlm-duplicate-download' ).click( function () {

        $.post( ajaxurl, {
            action: 'dlm_download_duplicator_duplicate',
            nonce: $( this ).attr( 'data-value' ),
            download_id: $( this ).attr( 'rel' )
        }, function ( response ) {
            if( 'success' == response.result ) {
                window.location.href = response.success_url;
            }else {
                alert( 'Something went wrong while duplicating download. Please contact support.' );
            }
        } );

    } );

} );