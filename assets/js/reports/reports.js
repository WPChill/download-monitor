jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

	/*$.each( $( '.dlm-reports-block-table' ), function ( k, v ) {
		new DLM_Reports_Block_Table( v );
	} );*/

	new DLM_Table_Navigation();
} );

/**
 * DLM_Reports_Data
 *
 * @param el
 * @constructor
 */
var DLM_Reports_Data = function ( el ) {
	this.type = null;
	this.from = null;
	this.to = null;
	this.period = null;

	this.init = function ( el ) {
		this.type = jQuery( el ).data( 'type' );
		this.to = jQuery( el ).data( 'to' );
		this.from = jQuery( el ).data( 'from' );
		this.period = jQuery( el ).data( 'period' );
		this.page = (jQuery( el ).find( 'table.active' ).length > 0) ? jQuery( el ).find( 'table.active' ).data( 'page' ) : 0;
	};
	this.init( el );
};

/**
 * DLM_Reports_Data_Fetch
 *
 * @param id
 * @param data
 * @param cb
 * @constructor
 */
var DLM_Reports_Data_Fetch = function ( id, data, cb ) {
	this.id = id;
	this.data = data;
	this.cb = cb;
	this.fetch();
};

DLM_Reports_Data_Fetch.prototype.fetch = function () {
	var id     = this.id,
	    cb     = this.cb,
	    from   = this.data.from,
	    to     = this.data.to,
	    period = this.data.period,
	    offset = this.data.page;

	jQuery.get( ajaxurl, {
		action: 'dlm_reports_data',
		nonce : dlm_rs.ajax_nonce,
		id    : id,
		from  : from,
		to    : to,
		period: period,
		page  : offset
	}, function ( response ) {
		cb( response );
	} );
};

/**
 * DLM_Reports_Block_Chart
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Chart = function ( c ) {

	this.container = c;
	this.id = null;

	this.queryData = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.queryData = new DLM_Reports_Data( this.container );
		this.render();
	};

	this.setup();

};

DLM_Reports_Block_Chart.prototype.render = function () {

	var chartId   = document.getElementById( 'total_downloads_chart' ),
	    stats     = jQuery( '#total_downloads_chart' ).data( 'stats' ),
	    data_test = stats.length ? JSON.parse( stats ) : false;

	if ( data_test ) {

		const today = new Date();
		const yesterday = new Date( today );
		yesterday.setDate( yesterday.getDate());
		const lastMonth = new Date( yesterday );
		yesterday.setDate( lastMonth.getDate() - 30 );

		const startDate = createDateElement( yesterday );
		const endDate = createDateElement( lastMonth );

		const start = data_test.datasets[0]['data'].findIndex( ( element ) => {

			let element_date = new Date( element.x );
			element_date = createDateElement( element_date );

			return startDate === element_date;
		} );

		const end = data_test.datasets[0]['data'].findIndex( ( element ) => {

			let element_date = new Date( element.x );
			element_date = createDateElement( element_date );
			return endDate === element_date;

		} );

		let new_data_sets = data_test.datasets[0]['data'].slice( start, end );
		let new_labels = data_test.labels.slice( start, end );

		data_test.datasets[0]['data'] = new_data_sets;
		data_test.labels = new_labels

		this.chart = new Chart( chartId, {
			title      : "",
			data       : data_test,
			type       : 'line',
			height     : 450,
			show_dots  : 0,
			x_axis_mode: "tick",
			y_axis_mode: "span",
			is_series  : 1,
			options    : {
				scales: {
					ticks: {
						maxRotation: 50,
						minRotation: 50
					},
				},
				//parsing: false
			}
		} );
	}
};

/**
 * DLM_Reports_Block_Table
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Table = function ( c ) {

	this.container = c;
	this.id = null;

	this.data = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.data = new DLM_Reports_Data( this.container );
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Table.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.data, function ( response ) {
		instance.data = response;
		instance.render();
	} );
};

DLM_Reports_Block_Table.prototype.render = function () {
	if ( this.data === null || (this.data.length < 2) ) {
		return;
	}

	var instance = this;

	// the table
	var table = jQuery( document.createElement( 'table' ) );

	table.attr( 'cellspacing', 0 ).attr( 'cellpadding', 0 ).attr( 'border', 0 );

	// setup header row
	var headerRow = document.createElement( 'tr' );

	for ( var i = 0; i < instance.data['downloads'][0].length; i++ ) {
		var th = document.createElement( 'th' );
		th.innerHTML = instance.data['downloads'][0][i];
		headerRow.appendChild( th );
	}

	// append header row
	table.append( headerRow );

	for ( var i = 1; i < instance.data['downloads'].length; i++ ) {
		// new row
		var tr = document.createElement( 'tr' );

		// loop
		for ( var j = 0; j < instance.data['downloads'][i].length; j++ ) {
			var td = document.createElement( 'td' );
			td.innerHTML = instance.data['downloads'][i][j];
			tr.appendChild( td );
		}

		// append row
		table.append( tr );
	}

	table.attr( 'data-page', parseInt( instance.data['offset'] ) + 1 ).css( 'left', '999px' ).addClass( 'active' );

	jQuery( instance.container ).find( 'table' ).not( table ).animate( {
		left: -9999
	}, 1200 );

	jQuery( instance.container ).find( '.dlm-reports-placeholder-no-data' ).remove();
	// put table in container
	jQuery( instance.container ).append( table ).attr( 'data-page', instance.data['offset'] );

	if ( 1 < (parseInt( instance.data['offset'] ) + 1) ) {
		jQuery( '#downloads-block-navigation button.hidden' ).removeClass( 'hidden' );
	} else {
		jQuery( '#downloads-block-navigation button' ).not( '#downloads-block-navigation button[data-action="load-more"]' ).addClass( 'hidden' );
	}

	jQuery( instance.container ).find( 'table' ).not( table ).removeClass( 'active' );

	table.animate( {
		left: 0
	}, 600 );

	jQuery( instance.container ).next( '#downloads-block-navigation' ).find( 'button' ).removeAttr( 'disabled' );
	jQuery( instance.container ).next( '#downloads-block-navigation' ).find( 'button[data-action="load-more"]' ).removeClass('hidden');

};

var DLM_Table_Navigation = function () {

	jQuery( '#downloads-block-navigation' ).on( 'click', 'button', function () {

		let main_parent   = jQuery( this ).parents( '#total_downloads_table_wrapper' ),
		    current_table = main_parent.find( 'table.active' ),
		    offset        = current_table.data( 'page' ),
		    prev_table    = current_table.prev(),
		    next_table    = current_table.next(),
		    link          = jQuery( this );

		link.attr( 'disabled', 'disabled' );

		// Check if we click the next/load more button
		if ( 'load-more' === jQuery( this ).data( 'action' ) ) {

			// If there is a
			if ( next_table.length ) {

				current_table.animate( {
					left: -999
				}, 1200 ).removeClass( 'active' );

				next_table.animate( {
					left: 0
				}, 1200 ).addClass( 'active' );

				// Remove on all buttons, as it will be at least page 2
				setTimeout( function () {
					jQuery( '#downloads-block-navigation' ).find( 'button' ).removeAttr( 'disabled' );
				}, 1200 );

			} else {

				//
				new DLM_Reports_Block_Table( main_parent.find( '.dlm-reports-block-table' ) );
			}

		} else {
			if ( 1 !== offset ) {

				current_table.animate( {
					left: -999
				}, 1200 ).removeClass( 'active' );

				prev_table.animate( {
					left: 0
				}, 1200 ).addClass( 'active' );

				if ( 2 < offset ) {
					setTimeout( function () {
						link.removeAttr( 'disabled' );
					}, 1200 );
				}
			}
		}
	} );
}