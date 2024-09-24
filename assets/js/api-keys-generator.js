jQuery(document).ready(function($) {

    $('.dlm-keygen-user-select').select2({
        ajax: {
            url: dlm_ajax.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    action: 'dlm_keygen_search_users', // AJAX action
                    _ajax_nonce: dlm_ajax.nonce,
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2, // Minimum length before searching
        placeholder: 'Search for a user',
        width: '400px'
    });


    $(document).on('click', '.dlm-keygen-generate', function(e) {
        e.preventDefault();
        user_id = $(this).parent().find( '.dlm-keygen-user-select').val();

        $.ajax({
            url: dlm_ajax.ajaxurl,
            type: 'POST', // Assuming you are sending a POST request
            dataType: 'json',
            data: {
                user_id: user_id,
                action: 'dlm_action_api_key', // AJAX action
                dlm_action: 'generate',
                _ajax_nonce: dlm_ajax.nonce, // Include the nonce for security
            },
            success: function(data) {
                if( data.success ){
                    location.reload();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle any errors here
                console.error('Error: ' + textStatus + ', ' + errorThrown);
            }
        });
    });


    $(document).on('click', '.dlm-regenerate-key', function( e ) {
        e.preventDefault();
        var user_id = $(this).data('user-id');

        $.ajax({
            url: dlm_ajax.ajaxurl,
            type: 'POST', // Assuming you are sending a POST request
            dataType: 'json',
            data: {
                user_id: user_id,
                action: 'dlm_action_api_key', // AJAX action
                dlm_action: 'regenerate',
                _ajax_nonce: dlm_ajax.nonce, // Include the nonce for security
            },
            success: function(data) {
                if( data.success ){
                    location.reload();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle any errors here
                console.error('Error: ' + textStatus + ', ' + errorThrown);
            }
        });
    });


    $(document).on('click', '.dlm-revoke-key', function( e ) {
        e.preventDefault();
        var user_id = $(this).data('user-id');

        $.ajax({
            url: dlm_ajax.ajaxurl,
            type: 'POST', // Assuming you are sending a POST request
            dataType: 'json',
            data: {
                user_id: user_id,
                action: 'dlm_action_api_key', // AJAX action
                dlm_action: 'revoke',
                _ajax_nonce: dlm_ajax.nonce, // Include the nonce for security
            },
            success: function(data) {
                if( data.success ){
                    location.reload();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle any errors here
                console.error('Error: ' + textStatus + ', ' + errorThrown);
            }
        });
    });

});