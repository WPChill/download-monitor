jQuery( function ( $ ) {

	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

	new DLM_Reports_Date_Range_Selector( $( '#dlm-date-range-picker' ) );

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

	dlmDownloadsSummary( false, false );
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
	const calendar_start_date = new Date( dlmReportsStats[0].date );
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

		const chart_data = createDataOnDate( obj.date1, obj.date2 );

		dlmCreateChart( chart_data, this.chartElement );

		dlmDownloadsSummary( obj.date1, obj.date2 );

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
 * @param dataType
 * @returns {*}
 */
const createDataOnDate = ( startDateInput, endDateInput, dataType = 'chart' ) => {

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

	let dayDownloads = getDates( new Date( startDate ), new Date( endDate ) );

	let start = Object.values( dlmReportsStats ).findIndex( ( element ) => {

		let element_date = new Date( element.date );
		element_date = createDateElement( element_date );

		return startDate === element_date;
	} );

	let end = Object.values( dlmReportsStats ).findIndex( ( element ) => {

		let element_date = new Date( element.date );
		element_date = createDateElement( element_date );
		return endDate === element_date;

	} );

	if ( -1 === start && -1 === end ) {

		if ( 'chart' !== dataType ) {
			return false;
		}

		return Object.assign( {}, dayDownloads );
	}

	if ( -1 === start ) {
		start = 0;
	}

	if ( -1 === end ) {
		end = dlmReportsStats.length;
	}

	const data = dlmReportsStats.slice( start, end );

	if ( 'chart' !== dataType ) {
		// Return both data and length of full date interval
		return {stats: data, daysLength: Object.keys( dayDownloads ).length};
	}

	Object.values( data ).forEach( ( day ) => {

		const downloads = JSON.parse( day.download_ids );

		let dateTime = new Date( day.date );

		const date = createDateElement( dateTime );

		Object.values( downloads ).forEach( ( item, index ) => {

			if ( 'undefined' === typeof dayDownloads[date] ) {
				dayDownloads[date] = item.downloads;
			} else {
				dayDownloads[date] = dayDownloads[date] + item.downloads;
			}

		} );

	} );

	return Object.assign( {}, dayDownloads );
};

const dlmCreateChart = ( data, chartId ) => {

	if ( data && chartId ) {

		const chart = Chart.getChart( 'total_downloads_chart' );

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

const dlmDownloadsSummary = ( startDateInput, endDateInput ) => {

	const info = createDataOnDate( startDateInput, endDateInput, false );

	if ( !info.stats ) {
		return;
	}

	let mostDownloaded = {};
	let totalDownloads = 0;
	// Lets prepare the items based on item id and not date so that we can get the most downloaded item
	info.stats.forEach( ( itemSet, index ) => {

		itemSet = JSON.parse( itemSet.download_ids );

		Object.values( itemSet ).forEach( ( item, index ) => {
			totalDownloads += item.downloads;
			mostDownloaded[item.id] = ('undefined' === typeof mostDownloaded[item.id]) ? {
				downloads: item.downloads,
				title    : item.title
			} : {downloads: mostDownloaded[item.id]['downloads'] + item.downloads, title: item.title};
		} );
	} );

	// max_obj will be the maximum value and index from most_downloaded array of the most downloaded Download
	const max_obj = Object.values( mostDownloaded ).reduce( function ( previousValue, currentValue, currentIndex ) {

		const prev = ('undefined' !== typeof previousValue.maximum) ? previousValue.maximum : 0;

		if ( prev >= currentValue.downloads && 'undefined' !== typeof previousValue.index ) {

			return {maximum: previousValue.maximum, index: previousValue.index, title: previousValue.title};
		} else {
			return {maximum: currentValue.downloads, index: currentIndex, title: currentValue.title};
		}
	}, 0 );

	// We get the Average by dividing total downloads to the number of entries stats array has, seeing that it's keys are the selected days
	const dailyAverageDownloads = parseInt( parseInt( totalDownloads ) / parseInt( info.daysLength ) );

	jQuery( '.dlm-reports-block-summary li#popular span' ).html( max_obj.title );
	jQuery( '.dlm-reports-block-summary li#total span' ).html( totalDownloads );
	jQuery( '.dlm-reports-block-summary li#average span' ).html( dailyAverageDownloads );


}

/**
 * Get all dates in set intervals
 * Used for chart data
 *
 * @param startDate
 * @param endDate
 * @returns {*[]}
 */
const getDates = ( startDate, endDate ) => {
	const dates = [];
	let currentDate = startDate;

	const addDays = ( currentDate ) => {
		const date = new Date( currentDate )
		date.setDate( currentDate.getDate() + 1 )
		return date
	};
	while ( currentDate <= endDate ) {

		dates[createDateElement( currentDate )] = 0;
		currentDate = addDays( currentDate );
	}
	return dates
}