jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

} );

var DLM_Reports_Data_Fetch = function ( id, from, to, period, cb ) {
	this.id = id;
	this.cb = cb;
	this.period = period;
	this.from = from;
	this.to = to;

	this.fetch();
};

DLM_Reports_Data_Fetch.prototype.fetch = function () {
	var id = this.id;
	var cb = this.cb;
	var from = this.from;
	var to = this.to;
	var period = this.period;
	jQuery.get( ajaxurl, {
		action: 'dlm_reports_data',
		nonce: dlm_rs.dlm_ajax_nonce,
		id: id,
		from: from,
		to: to,
		period: period
	}, function ( response ) {
		cb( response );
	} );
};

var DLM_Reports_Block_Chart = function ( c ) {

	this.container = c;
	this.id = null;
	this.type = null;
	this.from = null;
	this.to = null;
	this.period = null;
	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.type = jQuery( this.container ).attr( 'type' );
		this.to = jQuery( this.container ).data( 'to' );
		this.from = jQuery( this.container ).data( 'from' );
		this.period = jQuery( this.container ).data( 'period' );
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Chart.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.from, this.to, this.period, function ( response ) {
		instance.data = response;
		instance.render();
	} );
};

DLM_Reports_Block_Chart.prototype.render = function () {
	if ( this.data === null ) {
		return;
	}

	this.chart = new Chart( {
		parent: this.container,
		title: "",
		data: this.data,
		type: this.type,
		height: 250,
		show_dots: 0,
		x_axis_mode: "tick",
		y_axis_mode: "span",
		is_series: 1,
		format_tooltip_x: function ( d ) {
			return (
				d + ""
			).toUpperCase()
		},
		format_tooltip_y: function ( d ) {
			return d + " downloads"
		}
	} );
};