jQuery( function ( $ ) {

	$( '#dlm-form-checkout' ).submit( function ( e ) {

		var form = $( this );

		dlmShopShowLoading( form );

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
		$.post( dlm_strings.ajax_url_place_order, data, function ( response ) {
			if ( response.success === true && typeof response.redirect !== 'undefined' ) {
				window.location.replace( response.redirect );
				return false;
			}
			dlmShopHideLoading( form );
		} );

		return false;
	} );

	function dlmShopShowLoading( form ) {
		$( form ).find( '#dlm_checkout_submit' ).attr( 'disabled', true );

		var overlayBg = $( '<div>' ).addClass( 'dlm-checkout-overlay-bg' );

		var overlay = $( '<div>' ).addClass( 'dlm-checkout-overlay' );
		overlay.append( $( '<h2>' ).html( dlm_strings.overlay_title ) );
		overlay.append( $( '<span>' ).html( dlm_strings.overlay_body ) );
		overlay.append( $( '<img>' ).attr( 'src', dlm_strings.overlay_img_src ) );

		$( 'body' ).append( overlayBg );
		$( 'body' ).append( overlay );

		overlayBg.fadeIn( 300, function () {
			overlay.css( 'display', 'block' ).css( 'top', '47%' );
			overlay.animate( {
				"top": "+=3%"
			}, 300 );
		} );
	}

	function dlmShopHideLoading( form ) {

		var overlay = $( '.dlm-checkout-overlay:first' );
		var overlayBg = $( '.dlm-checkout-overlay-bg:first' );

		overlay.fadeOut( 300, function () {
			overlay.remove();
		} );

		overlayBg.fadeOut( 300, function () {
			overlayBg.remove();
			$( form ).find( '#dlm_checkout_submit' ).attr( 'disabled', false );
		} );
	}
} );


