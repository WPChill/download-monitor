jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

} );

var DLM_Reports_Data_Fetch = function ( id, cb ) {
	this.id = id;
	this.cb = cb;

	this.fetch();
};

DLM_Reports_Data_Fetch.prototype.fetch = function () {
	var id = this.id;
	var cb = this.cb;
	jQuery.get( ajaxurl, {
		action: 'dlm_reports_data',
		nonce: dlm_rs.dlm_ajax_nonce,
		id: id
	}, function ( response ) {
		cb( reponse );
	} );
};

var DLM_Reports_Block_Chart = function ( c ) {

	this.container = c;
	this.id = null;

	this.setup = function () {
		console.log( c );
		this.id = jQuery( this.container ).attr( 'id' );
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Chart.prototype.fetch = function () {
	new DLM_Reports_Data_Fetch( this.id, this.parse );
};

DLM_Reports_Block_Chart.prototype.parse = function ( response ) {
	console.log( response );
};