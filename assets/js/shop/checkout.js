jQuery(function ($) {
    $('#dlm-form-checkout').submit(function (e) {


        let customer = {
            first_name: $(this).find('#dlm_first_name').val(),
            last_name: $(this).find('#dlm_last_name').val(),
            company: $(this).find('#dlm_company').val(),
            email: $(this).find('#dlm_email').val(),
            address_1: $(this).find('#dlm_address_1').val(),
            postcode: $(this).find('#dlm_postcode').val(),
            city: $(this).find('#dlm_city').val(),
            country: $(this).find('#dlm_country').val(),
        };

        let data = {
            payment_gateway: $('input[name=dlm_gateway]:checked', $(this)).val(),
            customer: customer
        };

        console.log(data);

        $.post(dlm_strings.ajax_url_place_order, data, function (response) {
            console.log(response);
        });

        return false;
    });
});