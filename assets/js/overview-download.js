(function ( $ ) {

    // we create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;

    // and then we overwrite the function with our own code
    inlineEditPost.edit = function ( id ) {

        // "call" the original WP edit function
        // we don't want to leave WordPress hanging
        $wp_inline_edit.apply( this, arguments );

        // now we take care of our business

        // get the post ID
        var $post_id = 0;
        if ( typeof( id ) == 'object' ) {
            $post_id = parseInt( this.getId( id ) );
        }

        if ( $post_id > 0 ) {

            // define rows
            var $edit_row = $( '#edit-' + $post_id );
            var $post_row = $( '#post-' + $post_id );

            // get data
            var featured = ('Yes' == $( '.column-featured', $post_row ).text() );
            var members_only = ('Yes' == $( '.column-locked_download span', $post_row ).data('members_only') );
            var redirect_only = ('Yes' == $( '.column-redirect_only', $post_row ).text() );

            // populate data
            $( ':input[name="_featured"]', $edit_row ).prop( 'checked', featured );
            $( ':input[name="_members_only"]', $edit_row ).prop( 'checked', members_only );
            $( ':input[name="_redirect_only"]', $edit_row ).prop( 'checked', redirect_only );
        }
    };

    // Copy shortcode functionality
    $('.copy-dlm-shortcode').click( (e) => {
        e.preventDefault();

        const target = $(e.currentTarget);
        const dlm_shortcode = target.find('input');
        dlm_shortcode.trigger("focus");
        dlm_shortcode.trigger("select");
        document.execCommand('copy');

        if ( 'undefined' !== typeof navigator.clipboard ) {
            navigator.clipboard.writeText(dlm_shortcode.val());
        }

        if ( 'undefined' !== typeof window.ClipboardJS  ) {
            window.ClipboardJS.copy(dlm_shortcode.val());
        }

        $(this).next('span').text($(this).data('item') + ' copied');
        $('.copy-dlm-button').not($(this)).parent().find('span').text('');
        dlm_shortcode.trigger("blur");

        target.find('.wpchill-tooltip-content span').text(dlm_download_overview.shortcode_copied);
        setTimeout(() => {
            target.find('.wpchill-tooltip-content span').text(dlm_download_overview.copy_shortcode);
        }, 1000);
    });

})( jQuery );