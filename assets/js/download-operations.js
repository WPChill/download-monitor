jQuery( function ($) {

    // Browse for file
    jQuery( 'body' ).on( 'click', 'a.dlm_insert_download', function () {

        tb_show( dlm_id_strings.insert_download, 'media-upload.php?type=add_download&amp;from=wpdlm01&amp;TB_iframe=true&amp;height=200' );

        return false;
    } );

    // Browse for file
    jQuery( 'body' ).on( 'click', '.dlm-extension-filtering a', function (event) {
        event.preventDefault();

        const target = jQuery(this).attr('id'),
              paidExtensions = jQuery('.dlm_extensions .feature-list > .feature-block').not('.free-extension'),
              freeExtensions = jQuery('.dlm_extensions .feature-list > .feature-block.free-extension'),
            tabs = jQuery(this).parents('.dlm-extension-filtering').find('li').not(jQuery(this).parent());
        jQuery(this).parent().addClass('active-section');
        tabs.removeClass('active-section');
        if ( 'free-extensions' === target ) {
            freeExtensions.show( );
            paidExtensions.hide( );
        } else if ( 'pro-extensions' === target ){
            freeExtensions.hide( );
            paidExtensions.show( );
        } else {
            freeExtensions.show( );
            paidExtensions.show( );
        }
    } );

    $(document).on('click', '#dlm-protect-file', function (e) {
        // Prevent default for form submission
        e.preventDefault();

        const objectData = $(this).data(),
              button     = $(this),
              buttonText = button.text(),
              data       = {
                  action       : ('protect_file' === objectData.action) ? 'dlm_protect_file' : 'dlm_unprotect_file',
                  _ajax_nonce  : objectData.nonce,
                  user_id      : objectData.user_id,
                  file         : objectData.file,
                  attachment_id: objectData.post_id
              }
        button.attr('disabled', 'disabled');
        button.text('Please wait...');

        $.post(ajaxurl, data, function (response) {
            if (response.success) {
                button.text(response.data.text);
                if (response.data.url) {
                    button.parents('.attachment-info').find('#attachment-details-two-column-copy-link').val(response.data.url);
                    jQuery('#attachment_url').val(response.data.url);
                }
                const nextAction = ('protect_file' === objectData.action) ? 'unprotect_file' : 'protect_file';
                button.data('action', nextAction);

                setTimeout(function () {
                    const nextButtonText = ('protect_file' === nextAction) ? 'Protect File' : 'Unprotect File';
                    button.text(nextButtonText);
                    button.removeAttr('disabled');
                }, 3000);
            } else {
                button.text(response.data);
                setTimeout(function () {
                    button.text(buttonText);
                    button.removeAttr('disabled');
                }, 3000);
            }
        });
    });

    /**
     * Display the dropdown select settings
     */
    jQuery('.dlm-dropdown-select-settings').on('click', function (e) {
        e.stopPropagation();
        jQuery(this).parent().find('.dlm-dropdown-select-settings__body').toggleClass('dlm-dropdown-select-settings__body--open');
    });

    /**
     * Save the dropdown select settings
     */
    jQuery('.dlm-dropdown-select-wrapper__settings input[type="checkbox"]').on('change', function (e) {
        e.stopPropagation();
        e.preventDefault();
        const inputs = jQuery(this).parents('.dlm-dropdown-select-wrapper__settings').find('input[type="checkbox"]'),
              wrapperId     = jQuery(this).parents('.dlm-dropdown-select-wrapper').attr('id');
        let options  = [];

        Object.values(inputs).forEach((element) => {
            $element = jQuery(element);
            if ($element.is(':checked')) {
                options.push($element.val());
            }
        });

        // Get all the values from the inputs
        const data = {
            action     : 'dlm_save_dropdown_select_settings_' + wrapperId,
            _ajax_nonce: dlm_ajax_nonce,
            options    : options,
        }

        // Send the data to the server
        jQuery.post(ajaxurl, data, function (response) {
            if (response.success) {
                jQuery(document).trigger('dlm_after_dropdown_select_save_' + wrapperId, [response, options]);
            }
        });
    });
});

jQuery(document).ready(function () {
    if (undefined !== wp.media) {
        wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend(
            {
                className: function () {
                    // Mainly class for attachment.
                    let attachmentClass = 'attachment';

                    // If the dlmCustomClass attribute exists than apply it.
                    if ('undefined' !== this.model.get('dlmCustomClass')) {
                        attachmentClass += ' ' + this.model.get('dlmCustomClass');
                    }
                    // If the customClass attirbute exists than apply it.
                    if ('undefined' !== this.model.get('customClass')) {
                        attachmentClass += ' ' + this.model.get('customClass');
                    }
                    // Trigger this event in case other plugins want to attach to this.
                    jQuery(document).trigger('dlm_custom_attachment_class', [this.model, attachmentClass]);
                    // Return the class for attachment.
                    return attachmentClass;
                }
            }
        );
    }
});