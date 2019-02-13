jQuery( function ( $ ) {

	$.each( $( '.dlm-create-page' ), function ( k, v ) {
		new DLM_Onboarding_CP( v );
	} );

	/*
	$('.dlm-create-page').click(function() {

		var page = $(this).data('page');

		$(this).html(dlm_onboarding.lbl_creating);

		// set loading

		// do ajax request

			// check response

				// if success, change button to green with checkmark

	});
	*/
} );

var DLM_Onboarding_CP = function ( el ) {
	this.el = el;
	this.page = jQuery( el ).data( 'page' );
	this.isProcessing = false;
	this.setup();
};

DLM_Onboarding_CP.prototype.setup = function () {
	var instance = this;
	jQuery( this.el ).click( function () {
		instance.process();
	} );
};

DLM_Onboarding_CP.prototype.process = function () {
	if ( this.isProcessing ) {
		return false;
	}

	this.isProcessing = true;

	jQuery.get( dlm_onboarding.ajax_url_create_page, {
		page: this.page
	}, function ( response ) {
		console.log( response );
	} );

};