jQuery( function ( $ ) {

	$.each( $( '.extension_license a' ), function ( k, v ) {
		$( v ).click( function () {
			var wrap = $( v ).closest( '.extension_license' );

			var ex_ac = (
				'inactive' == $( wrap ).find( '#status' ).val()
			) ? 'activate' : 'deactivate';

			$(wrap).find('.dlm_license_error').remove();

			$.post( ajaxurl, {
				action: 'dlm_extension',
				nonce: $( '#dlm-ajax-nonce' ).val(),
				product_id: $( wrap ).find( '#product_id' ).val(),
				key: $( wrap ).find( '#key' ).val(),
				email: $( wrap ).find( '#email' ).val(),
				extension_action: ex_ac,
				action_trigger: 'extensions page single'
			}, function ( response ) {
				if ( response.result == 'failed' ) {
					$( wrap ).prepend( $( "<div>" ).addClass( "dlm_license_error" ).html( response.message ) );
				} else {
					if ( 'activate' == ex_ac ) {
						$( wrap ).find( '.license-status' ).addClass( 'active' ).html( 'ACTIVE' );
						$( wrap ).find( '.button' ).html( 'Deactivate' );
						$( wrap ).find( '#status' ).val( 'active' );
						$( wrap ).find( '#key' ).attr( 'disabled', true );
						$( wrap ).find( '#email' ).attr( 'disabled', true );
					} else {
						$( wrap ).find( '.license-status' ).removeClass( 'active' ).html( 'INACTIVE' );
						$( wrap ).find( '.button' ).html( 'Activate' );
						$( wrap ).find( '#status' ).val( 'inactive' );
						$( wrap ).find( '#key' ).attr( 'disabled', false );
						$( wrap ).find( '#email' ).attr( 'disabled', false );
					}
				}
			} );

		} );
	} );

	$('#dlm-master-license-btn').on('click', (e) => {
		e.preventDefault();

		const target       = $(e.target),
			  parent       = target.parents('.dlm-master-license'),
			  license      = $('#dlm-master-license').val(),
			  emailAddress = $('#dlm-master-license-email').val(),
			  nonce        = parent.find('input[type="hidden"]').val(),
			  ex_ac        = target.data('action'),
			  extensions   = parent.parent().find('.extension_license'),
			  actionText   = (ex_ac == 'activate') ? extensions_vars.activate : extensions_vars.deactivate;
		target.attr('disabled', 'disabled');
		parent.append('<div class="dlm-master-license-response">'+ actionText +'</div>');
		$.post( ajaxurl, {
			action: 'dlm_master_license',
			nonce: nonce,
			key: license,
			email: emailAddress,
			extension_action: ex_ac,
			action_trigger: 'extensions page master'
		}, function ( response ) {
			target.removeAttr('disabled');
			if ( response.result == 'failed' ) {
				parent.find('.dlm-master-license-response').remove();
				parent.append('<div class="dlm-master-license-response">'+ response.message +'</div>');
			} else {
				window.location.href = window.location.href;
			}
		} );
	});
} );