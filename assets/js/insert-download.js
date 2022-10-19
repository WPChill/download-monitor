jQuery( function ($) {
    // Browse for file
    jQuery( 'body' ).on( 'click', 'a.dlm_insert_download', function () {

        tb_show( dlm_id_strings.insert_download, 'media-upload.php?type=add_download&amp;from=wpdlm01&amp;TB_iframe=true&amp;height=200' );

        return false;
    } );

    $(document).on('click', '#dlm-protect-file', function (e) {
        // Prevent default for form submission
        e.preventDefault();

        const objectData = $(this).data(),
              button     = $(this),
              buttonText = button.text(),
              data       = {
                  action       : ('protect_file' === objectData.action) ? 'dlm_protect_file' : 'dlm_unprotect_file',
                  title        : objectData.title,
                  _ajax_nonce  : objectData.nonce,
                  user_id      : objectData.user_id,
                  file         : objectData.file,
                  attachment_id: objectData.post_id
              }

        button.text('Please wait...');

        $.post(ajaxurl, data, function (response) {
            if (response.success) {
                button.text(response.data.text);
                if (response.data.url) {
                    button.parents('.attachment-info').find('#attachment-details-two-column-copy-link').val(response.data.url);
                }
                const nextAction = ('protect_file' === objectData.action) ? 'unprotect_file' : 'protect_file';
                button.data('action', nextAction);

                setTimeout(function () {
                    const nextButtonText = ('protect_file' === nextAction) ? 'Protect File' : 'Unprotect File';
                    button.text(nextButtonText);
                }, 3000);
            } else {
                button.text(response.data);
                setTimeout(function () {
                    button.text(buttonText);
                }, 3000);
            }
        });
    });
});