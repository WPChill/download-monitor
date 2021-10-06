jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

	$.each( $( '.dlm-reports-block-table' ), function ( k, v ) {
		new DLM_Reports_Block_Table( v );
	} );

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
		lastMonth.setDate( lastMonth.getDate() - 30 );

		const endDate = createDateElement( yesterday );
		const startDate = createDateElement( lastMonth );

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
		data_test.labels = new_labels;

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
};

jQuery.fn.extend( {
	dlm_reports_date_range: function ( start_date, end_date, url ) {
		new DLM_Reports_Date_Range_Selector( this, start_date, end_date, url );
		return this;
	}
} );

var DLM_Reports_Date_Range_Selector = function ( c, sd, ed, u ) {

	this.container = c;
	this.startDate = new Date( sd );
	this.endDate = new Date( ed );
	this.url = u;
	this.el = null;
	this.opened = false;

	const chartData = jQuery( '#total_downloads_chart' ).data( 'stats' );

	this.chartElement = document.getElementById( 'total_downloads_chart' );
	this.chartData = chartData.length ? JSON.parse( chartData ) : false;

	if ( this.chartData ) {
		this.chartDataSets = this.chartData.datasets[0]['data'];
	}

	this.startDateInput = null;
	this.endDateInput = null;

	this.setup = function () {
		var instance = this;
		this.container.click( function () {
			instance.toggleDisplay();
			return false;
		} );
	};

	this.setup();

};

DLM_Reports_Date_Range_Selector.prototype.toggleDisplay = function () {
	if ( this.opened ) {
		this.hide();
	} else {
		this.display();
	}
};

DLM_Reports_Date_Range_Selector.prototype.display = function () {
	if ( this.opened ) {
		return;
	}

	this.opened = true;
	this.el = this.createElement();
	this.container.append( this.el );
	let element = this.el;

	var configObject = {
		separator      : ' to ',
		autoClose      : true,
		setValue       : function ( s, s1, s2 ) {
			element.find( '#dlm_start_date' ).val( s1 );
			element.find( '#dlm_end_date' ).val( s2 );
		},
		inline         : true,
		alwaysOpen     : true,
		container      : '#dlm_date_range_picker',
		endDate        : new Date(),
		showShortcuts  : true,
		shortcuts      : null,
		customShortcuts: [
			//if return an array of two dates, it will select the date range between the two dates
			{
				name : 'Last 7 Days',
				dates: function () {
					const start = new Date();
					const end = new Date( start );
					end.setDate( end.getDate() - 7 );

					return [ start, end ];
				}
			},
			//if only return an array of one date, it will display the month which containing the date. and it will not select any date range
			{
				name : 'Last 30 Days',
				dates: function () {
					const start = new Date();
					const end = new Date( start );
					end.setDate( end.getDate() - 30 );

					return [ start, end ];
				}
			},
			{
				name : 'This month',
				dates: function () {
					const start = new Date();
					const end = new Date( start.getFullYear(), start.getMonth(), 1 );

					return [ start, end ];
				},
			},
			{
				name : 'Last month',
				dates: function () {

					const now = new Date();

					let start = moment().month( now.getMonth() - 1 ).startOf( 'month' )._d;
					let end = moment().month( now.getMonth() - 1 ).endOf( 'month' )._d;

					if ( 0 === now.getMonth() ) {
						start = moment().year( now.getFullYear() - 1 ).month( 11 ).startOf( 'month' )._d;
						end = moment().year( now.getFullYear() - 1 ).month( 11 ).endOf( 'month' )._d;
					}

					return [ start, end ];
				}
			},
			{
				name : 'This Year',
				dates: function () {

					const end = new Date();
					const start = moment().startOf( 'year' )._d;

					return [ start, end ];
				}
			},
			{
				name : 'Last Year',
				dates: function () {

					const now = new Date();
					const start = moment().year( now.getFullYear() - 1 ).month( 0 ).startOf( 'month' )._d;
					const end = moment().year( now.getFullYear() - 1 ).month( 11 ).endOf( 'month' )._d;


					return [ start, end ];
				}
			},
			{
				name : 'All time',
				dates: function () {

					const start = moment().year( 2010 ).month( 0 ).startOf( 'month' )._d;
					const end = new Date();


					return [ start, end ];
				}
			},
		]
	};

	element.dateRangePicker( configObject ).bind( 'datepicker-change', ( event, obj ) => {

		const startDate = createDateElement( obj.date1 );
		const endDate = createDateElement( obj.date2 );

		let chartData = this.chartData;

		if ( startDate && endDate ) {

			let date_s = new Date( startDate );
			date_s = date_s.toLocaleDateString( undefined, {
				year : 'numeric',
				month: 'short',
				day  : '2-digit'
			} );

			let date_e = new Date( endDate );
			date_e = date_e.toLocaleDateString( undefined, {
				year : 'numeric',
				month: 'short',
				day  : '2-digit'
			} );

			element.parent().find( 'span.date-range-info' ).text( date_s + ' to ' + date_e );
		}

		if ( chartData ) {

			const start = this.chartDataSets.findIndex( ( element ) => {

				let element_date = new Date( element.x );
				element_date = createDateElement( element_date );

				return startDate === element_date;
			} );

			const end = this.chartDataSets.findIndex( ( element ) => {

				let element_date = new Date( element.x );
				element_date = createDateElement( element_date );

				return endDate === element_date;

			} );

			let new_data_sets = this.chartDataSets.slice( start, end );
			let new_labels = this.chartData.labels.slice( start, end );

			chartData.datasets[0]['data'] = new_data_sets;
			chartData.labels = new_labels;

			const chart = Chart.getChart( "total_downloads_chart" );

			if ( 'undefined' !== typeof chart ) {
				chart.destroy();
			}

			let current_chart = new Chart( this.chartElement, {
				title      : "",
				data       : chartData,
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

		element.data( 'dateRangePicker' ).close();
	} );
};

DLM_Reports_Date_Range_Selector.prototype.hide = function () {
	this.opened = false;
	this.el.remove();
};

DLM_Reports_Date_Range_Selector.prototype.apply = function () {

	var sd = new Date( this.startDateInput.val() + "T00:00:00" );
	var ed = new Date( this.endDateInput.val() + "T00:00:00" );
	var sds = sd.getFullYear() + "-" + (sd.getMonth() + 1) + "-" + sd.getDate();
	var eds = ed.getFullYear() + "-" + (ed.getMonth() + 1) + "-" + ed.getDate();
	this.hide();
	window.location.replace( this.url + "&date_from=" + sds + "&date_to=" + eds );
};

DLM_Reports_Date_Range_Selector.prototype.createElement = function () {
	var instance = this;

	const today = new Date();
	let dd = today.getDate() - 1;
	let mm = today.getMonth() + 1; //January is 0!
	let mmm = mm - 1;
	const yyyy = today.getFullYear();

	if ( dd < 10 ) {
		dd = '0' + dd;
	}

	if ( mm < 10 ) {
		mm = "0" + mm;
	}

	if ( mmm < 10 ) {
		mmm = "0" + mmm;
	}
	const yesterday = yyyy + '-' + mm + '-' + dd;
	const lastMonth = yyyy + '-' + mmm + '-' + dd;


	var el = jQuery( '<div>' ).addClass( 'dlm_rdrs_overlay' );
	var startDate = jQuery( '<div>' ).attr( 'id', 'dlm_date_range_picker' );
	this.startDateInput = jQuery( '<input>' ).attr( 'type', 'hidden' ).attr( 'id', 'dlm_start_date' ).attr( 'value', lastMonth );
	this.endDateInput = jQuery( '<input>' ).attr( 'type', 'hidden' ).attr( 'id', 'dlm_end_date' ).attr( 'value', yesterday );
	var actions = jQuery( '<div>' ).addClass( 'dlm_rdrs_actions' );
	var applyButton = jQuery( '<a>' ).addClass( 'button' ).html( 'Apply' ).click( function () {
		instance.apply();
		return false;
	} );

	actions.append( applyButton );
	//el.append(ul).append( startDate ).append( endDate ).append( actions ).append( this.startDateInput ).append( this.endDateInput );
	// Don't append actions for now, for the purpose of the styling. Actions will be completly removed when going to React
	el.append( startDate ).append( this.startDateInput ).append( this.endDateInput );

	el.click( function () {
		return false;
	} );
	return el;
};

/**
 * Requires a Date object and resturns a string
 * @param date
 * @returns {string}
 */
const createDateElement = ( date ) => {
	return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
}
