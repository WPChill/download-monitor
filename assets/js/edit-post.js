jQuery( function ($) {
    // Browse for file
    jQuery( 'body' ).on( 'click', 'a.add_download', function () {

        tb_show( dlm_ep_strings.insert_download, 'media-upload.php?post_id=' + $( this ).attr( 'rel' ) + '&amp;type=add_download&amp;from=wpdlm01&amp;TB_iframe=true&amp;height=200' );

        return false;
    } );
} );