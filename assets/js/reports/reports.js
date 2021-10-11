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

jQuery.fn.extend( {
	dlm_reports_date_range: function () {
		new DLM_Reports_Date_Range_Selector( this );
		return this;
	}
} );

/**
 * DLM_Reports_Block_Chart
 *
 * @param c
 * @constructor
 */
const DLM_Reports_Block_Chart = function ( c ) {

	this.container = c;
	this.id = null;

	this.queryData = null;

	this.data = null;
	this.chart = null;

	this.id = jQuery( this.container ).attr( 'id' );
	this.render();

};

DLM_Reports_Block_Chart.prototype.render = function () {

	const chartId = document.getElementById( 'total_downloads_chart' );

	dlmCreateChart( createDataOnDate( false, false ), chartId );

	dlmTotalDownloads( false, false );
};

/**
 * DLM_Reports_Block_Table
 *
 * @param c
 * @constructor
 */
const DLM_Reports_Block_Table = function ( c ) {

	this.container = c;
	this.id = null;

	this.data = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.render();
	};

	this.setup();

};

DLM_Reports_Block_Table.prototype.render = function () {
	if ( this.data === null || (this.data.length < 2) ) {
		return;
	}

	const instance = this;

	// the table
	const table = jQuery( document.createElement( 'table' ) );

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

	for ( let i = 1; i < instance.data['downloads'].length; i++ ) {
		// new row
		const tr = document.createElement( 'tr' );

		// loop
		for ( let j = 0; j < instance.data['downloads'][i].length; j++ ) {
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
	jQuery( instance.container ).next( '#downloads-block-navigation' ).find( 'button[data-action="load-more"]' ).removeClass( 'hidden' );

};

const DLM_Table_Navigation = function () {

	jQuery( '#downloads-block-navigation' ).on( 'click', 'button', function () {

		const main_parent   = jQuery( this ).parents( '#total_downloads_table_wrapper' ),
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
					link.find( 'button' ).removeAttr( 'disabled' );
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

const DLM_Reports_Date_Range_Selector = function ( c ) {

	this.container = c;
	this.el = null;
	this.opened = false;

	this.chartElement = document.getElementById( 'total_downloads_chart' );

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
	const calendar_start_date = new Date( dlmReportsStats.chart[0].x );
	const currDate = new Date();

	var configObject = {
		separator : ' to ',
		autoClose : true,
		setValue  : function ( s, s1, s2 ) {
			element.find( '#dlm_start_date' ).val( s1 );
			element.find( '#dlm_end_date' ).val( s2 );
		},
		inline    : true,
		alwaysOpen: true,
		container : '#dlm_date_range_picker',
		// End date should be current date
		endDate: new Date(),
		// Start date should be the first info we get about downloads
		startDate      : calendar_start_date,
		showShortcuts  : true,
		shortcuts      : null,
		customShortcuts: [

			{
				name : 'Last 7 Days',
				dates: function () {

					return [ new Date( currDate.getFullYear(), currDate.getMonth(), currDate.getDate() - 7 ), currDate ];
				}
			},

			{
				name : 'Last 30 Days',
				dates: function () {

					return [ new Date( currDate.getFullYear(), currDate.getMonth(), currDate.getDate() - 30 ), currDate ];
				}
			},
			{
				name : 'This month',
				dates: function () {

					return [ new Date( currDate.getFullYear(), currDate.getMonth(), 1 ), currDate ];
				},
			},
			{
				name : 'Last month',
				dates: function () {

					let start = moment().month( currDate.getMonth() - 1 ).startOf( 'month' )._d;
					let end = moment().month( currDate.getMonth() - 1 ).endOf( 'month' )._d;

					if ( 0 === currDate.getMonth() ) {
						start = moment().year( currDate.getFullYear() - 1 ).month( 11 ).startOf( 'month' )._d;
						end = moment().year( currDate.getFullYear() - 1 ).month( 11 ).endOf( 'month' )._d;
					}

					return [ start, end ];
				}
			},
			{
				name : 'This Year',
				dates: function () {

					const start = moment().startOf( 'year' )._d;

					return [ start, currDate ];
				}
			},
			{
				name : 'Last Year',
				dates: function () {

					const start = moment().year( currDate.getFullYear() - 1 ).month( 0 ).startOf( 'month' )._d;
					const end = moment().year( currDate.getFullYear() - 1 ).month( 11 ).endOf( 'month' )._d;


					return [ start, end ];
				}
			},
			{
				name : 'All time',
				dates: function () {

					return [ calendar_start_date, currDate ];
				}
			},
		]
	};

	element.dateRangePicker( configObject ).bind( 'datepicker-change', ( event, obj ) => {

		if ( obj.date1 && obj.date2 ) {

			const date_s = obj.date1.toLocaleDateString( undefined, {
				year : 'numeric',
				month: 'short',
				day  : '2-digit'
			} );

			const date_e = obj.date2.toLocaleDateString( undefined, {
				year : 'numeric',
				month: 'short',
				day  : '2-digit'
			} );

			element.parent().find( 'span.date-range-info' ).text( date_s + ' to ' + date_e );
		}

		dlmCreateChart( createDataOnDate( obj.date1, obj.date2 ), this.chartElement );

		dlmTotalDownloads( obj.date1, obj.date2 );

		element.data( 'dateRangePicker' ).close();
	} );
};

DLM_Reports_Date_Range_Selector.prototype.hide = function () {
	this.opened = false;
	this.el.remove();
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
};

/**
 * Filter data to send to chart based on user input start & end date
 *
 * @param startDateInput
 * @param endDateInput
 * @returns {*}
 */
const createDataOnDate = ( startDateInput, endDateInput ) => {


	let startDate, endDate;

	if ( 'undefined' !== typeof startDateInput && startDateInput ) {

		startDate = createDateElement( new Date( startDateInput ) );
	} else {

		const lastMonth = new Date();
		lastMonth.setDate( lastMonth.getDate() - 30 );
		startDate = createDateElement( lastMonth );
	}

	if ( 'undefined' !== typeof endDateInput && endDateInput ) {

		endDate = createDateElement( new Date( endDateInput ) );
	} else {

		const yesterday = new Date();
		yesterday.setDate( yesterday.getDate() );
		endDate = createDateElement( yesterday );
	}

	const start = dlmReportsStats.chart.findIndex( ( element ) => {

		let element_date = new Date( element.x );
		element_date = createDateElement( element_date );

		return startDate === element_date;
	} );

	const end = dlmReportsStats.chart.findIndex( ( element ) => {

		let element_date = new Date( element.x );
		element_date = createDateElement( element_date );
		return endDate === element_date;

	} );

	return dlmReportsStats.chart.slice( start, end );

};

const dlmCreateChart = ( data, chartId ) => {

	if ( data && chartId ) {

		const chart = Chart.getChart( "total_downloads_chart" );

		if ( 'undefined' !== typeof chart ) {
			chart.destroy();
		}

		this.chart = new Chart( chartId, {
			title      : "",
			data       : {
				datasets: [ {
					label: 'Downloads',
					color: '#000fff',
					data : data
				} ]
			},
			type       : 'line',
			height     : 450,
			show_dots  : 0,
			x_axis_mode: "tick",
			y_axis_mode: "span",
			is_series  : 1,
			options    : {
				scales    : {
					ticks: {
						maxRotation: 50,
						minRotation: 50
					},
				},
				animation : false,
				elements  : {
					line: {
						borderColor: 'rgba(255,0,206)',
						borderWidth: 2
					},
				},
				fill      : {
					target: 'origin',
					above : 'rgba(255, 0, 206, 0.3)'
				},
				normalized: true,
				parsing   : {
					xAxisKey: 'x',
					yAxisKey: 'y'
				},
				//parsing: false
			}
		} );
	}
};

const dlmTotalDownloads = ( startDateInput, endDateInput ) => {

	let startDate, endDate;

	if ( 'undefined' !== typeof startDateInput && startDateInput ) {

		startDate = createDateElement( new Date( startDateInput ) );
	} else {

		const lastMonth = new Date();
		lastMonth.setDate( lastMonth.getDate() - 30 );
		startDate = createDateElement( lastMonth );
	}

	if ( 'undefined' !== typeof endDateInput && endDateInput ) {

		endDate = createDateElement( new Date( endDateInput ) );
	} else {

		const yesterday = new Date();
		yesterday.setDate( yesterday.getDate() );
		endDate = createDateElement( yesterday );
	}

	const download_info = JSON.parse( JSON.stringify( dlmReportsStats ) );
	let stats = Object.values( download_info.summary );

	let start = stats.findIndex( ( element ) => {

		return startDate === createDateElement( new Date( element['date'] ) );
	} );

	let end = stats.findIndex( ( element ) => {

		return endDate === createDateElement( new Date( element['date'] ) );
	} );

	if ( -1 === start ) {
		start = 0;
	}

	if ( -1 === end ) {
		end = stats.length
	}

	stats = stats.slice( start, end );
	let most_downloaded = {};

	stats.forEach( ( itemSet, index ) => {

		itemSet = Object.values( itemSet );

		itemSet.forEach( ( item, index ) => {
			most_downloaded[item.id] = ('undefined' !== typeof most_downloaded[item.id]) ? most_downloaded[item.id] + item.downloads : item.downloads + 0;
		} );

	} );

	// max_obj will be the maximum value and index from most_downloaded array of the most downloaded Download
	// What we need next is to get the title of the Download - from first tests adding the title to the array we get from PHP is not a good idea
	const max_obj = Object.values( most_downloaded ).reduce( function ( previousValue, currentValue, currentIndex ) {

		const prev = ('undefined' !== typeof previousValue.maximum) ? previousValue.maximum : 0;

		if ( prev >= currentValue && 'undefined' !== typeof previousValue.index ) {

			return {maximum: previousValue.maximum, index: previousValue.index};
		} else {
			return {maximum: currentValue, index: currentIndex};
		}
	}, 0 );

	return;

	jQuery( '.dlm-reports-block-summary li#total span' ).html();

}