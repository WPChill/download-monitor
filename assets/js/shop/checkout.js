jQuery( function ( $ ) {
	$( '#dlm-form-checkout' ).submit( function ( e ) {


		var customer = {
			first_name: $( this ).find( '#dlm_first_name' ).val(),
			last_name: $( this ).find( '#dlm_last_name' ).val(),
			company: $( this ).find( '#dlm_company' ).val(),
			email: $( this ).find( '#dlm_email' ).val(),
			address_1: $( this ).find( '#dlm_address_1' ).val(),
			postcode: $( this ).find( '#dlm_postcode' ).val(),
			city: $( this ).find( '#dlm_city' ).val(),
			country: $( this ).find( '#dlm_country' ).val(),
		};

		var data = {
			payment_gateway: $( 'input[name=dlm_gateway]:checked', $( this ) ).val(),
			customer: customer
		};

		//console.log(data);

		$.post( dlm_strings.ajax_url_place_order, data, function ( response ) {
			if ( response.success === true && typeof response.redirect != 'undefined' ) {
				window.location.replace(response.redirect);
			}
		} );

		return false;
	} );
} );