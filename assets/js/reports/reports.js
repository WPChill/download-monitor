<<<<<<< Updated upstream
jQuery( function ( $ ) {

	// init chart blocks
	$.each( $( '.dlm-reports-block-chart' ), function ( k, v ) {
		new DLM_Reports_Block_Chart( v );
	} );

	$.each( $( '.dlm-reports-block-summary' ), function ( k, v ) {
		new DLM_Reports_Block_Summary( v );
	} );

	$.each( $( '.dlm-reports-block-table' ), function ( k, v ) {
		new DLM_Reports_Block_Table( v );
	} );

	$( '#total_downloads_browser_table' ).on( 'click', 'a', function ( e ) {
		e.preventDefault();

		var target = $( this ).attr( 'href' );
		$( this ).addClass( 'nav-tab-active' );
		$( '#total_downloads_browser_table' ).find( 'a' ).not( $( this ) ).removeClass( 'nav-tab-active' );
		$( target ).removeClass( 'hidden' );
		$( '#total_downloads_browser_table' ).find( 'table' ).not( $( target ) ).addClass( 'hidden' );
	} );

} );

/**
 * Creates a loader obj used in report blocks
 *
 * @returns {Element}
 * @constructor
 */
function DLM_createLoaderObj() {
	var loaderObj = document.createElement( "div" );
	loaderObj = jQuery( loaderObj );
	loaderObj.addClass( 'dlm_reports_loader' );

	var loaderImgObj = document.createElement( "img" );
	loaderImgObj = jQuery( loaderImgObj );
	loaderImgObj.attr( 'src', dlm_rs.img_path + 'ajax-loader.gif' );

	loaderObj.append( loaderImgObj );

	return loaderObj;
}

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
	var id = this.id;
	var cb = this.cb;
	var from = this.data.from;
	var to = this.data.to;
	var period = this.data.period;
	jQuery.get( ajaxurl, {
		action: 'dlm_reports_data',
		nonce: dlm_rs.ajax_nonce,
		id: id,
		from: from,
		to: to,
		period: period
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
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Chart.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Chart.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Chart.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.queryData, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Chart.prototype.render = function () {
	if ( this.data === null ) {
		return;
=======
jQuery(function ($) {
	new DLM_Reports();
});

class DLM_Reports {

	/**
	 * The constructor for our class
	 */
	constructor() {

		this.chartContainer = document.getElementById('total_downloads_chart');
		const ctx = this.chartContainer.getContext("2d");

		/**
		 * Gradient for the chart
		 */
		this.chartColors = {
			purple: {
				default: "rgba(149, 76, 233, 1)",
				half: "rgba(149, 76, 233, 0.75)",
				quarter: "rgba(149, 76, 233, 0.5)",
				zero: "rgba(149, 76, 233, 0.05)"
			},
			blue: {
				default: "rgba(56, 88, 233, 1)",
				half: "rgba(56, 88, 233, 0.75)",
				quarter: "rgba(56, 88, 233, 0.5)",
				zero: "rgba(56, 88, 233, 0.05)"
			},
			indigo: {
				default: "rgba(80, 102, 120, 1)",
				quarter: "rgba(80, 102, 120, 0.5)"
			}
		};
		this.chartGradient = ctx.createLinearGradient(0, 25, 0, 300);
		this.chartGradient.addColorStop(0, this.chartColors.blue.half);
		this.chartGradient.addColorStop(0.45, this.chartColors.blue.quarter);
		this.chartGradient.addColorStop(1, this.chartColors.blue.zero);


		this.datePickerContainer = document.getElementById('dlm-date-range-picker');
		// We parse it so that we don't make any modifications to the actual data
		// In case we will fetch data using js and the WP REST Api the this.reportsData will be an empty Object and we'll fetch data using fetchData() function
		this.reportsData = ('undefined' !== typeof dlmReportsStats) ? JSON.parse(JSON.stringify(dlmReportsStats)) : {};
		this.mostDownloaded = false;
		this.stats = false;
		this.chartType = 'day';
		this.createDataOnDate(false, false);
		this.datePicker = {
			opened: false
		};
		this.init();
	}

	/**
	 * Get all dates in set intervals
	 * Used for chart data
	 *
	 * @param startDate
	 * @param endDate
	 * @returns {*[]}
	 */
	getDates(startDate, endDate) {

		const dates = [];
		let currentDate = startDate;

		while (currentDate <= endDate) {

			dates[this.createDateElement(currentDate)] = 0;
			currentDate = this.getNextDay(currentDate);
		}

		return dates;
	}

	/**
	 * Get all dates in set intervals
	 * Used for chart data
	 *
	 * @param startDate
	 * @param endDate
	 * @returns {*[]}
	 */
	getMonths(days) {

		const dates = [];

		Object.keys(days).map(element => {

			let subString = element.substring(0, 7);

			if ('undefined' === typeof dates[subString]) {
				dates[subString] = 0;
			}
		});

		return dates;
	}

	/**
	 * Get all dates in set intervals
	 * Used for chart data
	 *
	 * @param startDate
	 * @param endDate
	 * @returns {*[]}
	 */
	getWeeks(days) {

		const dates = [];

		Object.keys(days).map(element => {
			let week;

			if (moment(element).date() > 15) {
				week = element.substring(0, 7) + '-15';
			} else {
				week = element.substring(0, 7) + '-1';
			}

			if ('undefined' === typeof dates[week]) {
				dates[week] = 0;
			}
		});

		return dates;
	}

	/**
	 * Get the next day
	 *
	 * @param currentDate
	 * @returns {Date}
	 */
	getNextDay(currentDate) {

		const date = new Date(currentDate);
		date.setDate(currentDate.getDate() + 1);
		return date;
	}

	/**
	 * Requires a Date object and resturns a string
	 * @param date
	 * @returns {string}
	 */
	createDateElement(date) {
		var MM = ((date.getMonth() + 1) < 10 ? '0' : '') +
			(date.getMonth() + 1);

		return date.getFullYear() + '-' + MM + '-' + date.getDate();
	}

	/**
	 * Filter data to send to chart based on user input start & end date
	 *
	 * @param startDateInput
	 * @param endDateInput
	 * @param dataType
	 * @returns {*}
	 */
	createDataOnDate(startDateInput, endDateInput) {

		const instance = this;

		let startDate,
			endDate,
			monthDiff,
			chartDate,
			dlmDownloads;
		instance.reportsData = ('undefined' !== typeof dlmReportsStats) ? JSON.parse(JSON.stringify(dlmReportsStats)) : {};

		if ('undefined' !== typeof startDateInput && startDateInput) {

			startDate = instance.createDateElement(new Date(startDateInput));
		} else {
			// If there are no startDateInput it means it is the first load, so get last 30 days.
			const lastMonth = new Date();
			lastMonth.setDate(lastMonth.getDate() - 30);
			startDate = instance.createDateElement(lastMonth);

		}

		if ('undefined' !== typeof endDateInput && endDateInput) {
			endDate = instance.createDateElement(new Date(endDateInput));
		} else {

			// If there is no endDateInput we'll set the endDate to tomorrow so that we can include today in our reports also.
			// Seems like this is how the datepicker works.
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);
			endDate = instance.createDateElement(tomorrow);
		}

		monthDiff = moment(endDate, 'YYYY-MM-DD').month() - moment(startDate, 'YYYY-MM-DD').month();

		if (moment(endDate, 'YYYY-MM-DD').year() !== moment(startDate, 'YYYY-MM-DD').year() || monthDiff > 6) {
			instance.chartType = 'month';
		} else if (monthDiff >= 1) {

			instance.chartType = 'weeks';

			if (1 === monthDiff && moment(endDate, 'YYYY-MM-DD').date() <= moment(startDate, 'YYYY-MM-DD').date()) {
				instance.chartType = 'day';
			}

		} else {
			instance.chartType = 'day';
		}

		// Get all dates from the startDate to the endDate
		let dayDownloads = instance.getDates(new Date(startDate), new Date(endDate));
		// Get selected months
		let monthDownloads = instance.getMonths(dayDownloads);
		// Get selected dates in 2 weeks grouping
		let weeksDownloads = instance.getWeeks(dayDownloads);

		Object.values(instance.reportsData).forEach((day, index) => {

			const downloads = JSON.parse(day.download_ids);

			if ('undefined' !== typeof dayDownloads[day.date]) {

				switch (instance.chartType) {
					case 'month':

						chartDate = day.date.substring(0, 7);
						Object.values(downloads).forEach((item, index) => {

							monthDownloads[chartDate] = ('undefined' !== typeof monthDownloads[chartDate]) ? monthDownloads[chartDate] + item.downloads : item.downloads;

						});

						dlmDownloads = monthDownloads;
						break;
					case 'weeks':

						if (moment(day.date).date() > 15) {
							chartDate = day.date.substring(0, 7) + '-15';
						} else {
							chartDate = day.date.substring(0, 7) + '-1';
						}

						Object.values(downloads).forEach((item, index) => {

							weeksDownloads[chartDate] = ('undefined' !== typeof weeksDownloads[chartDate]) ? weeksDownloads[chartDate] + item.downloads : item.downloads;

						});

						dlmDownloads = weeksDownloads;
						break;
					case 'day':
						Object.values(downloads).forEach((item, index) => {

							dayDownloads[day.date] = dayDownloads[day.date] + item.downloads;

						});

						dlmDownloads = dayDownloads;
						break;
				}

			} else {
				delete instance.reportsData[index];
			}

		});

		// Get number of days, used in summary for daily average downloads
		const daysLength = Object.keys(dayDownloads).length;

		// Find the start of the donwloads object
		let start = Object.keys(dayDownloads).findIndex((element) => {
			return startDate === element;
		});

		// Find the end of the downloads object
		let end = Object.keys(dayDownloads).findIndex((element) => {
			return endDate === element;
		});

		if (-1 === start && -1 === end) {

			instance.stats = {
				chartStats: Object.assign({}, dlmDownloads),
				summaryStats: false,
				daysLength: daysLength
			};
			return;
		}

		if (-1 === start) {
			start = 0;
		}

		if (-1 === end) {
			end = daysLength;
		}

		instance.stats = {
			chartStats: Object.assign({}, dlmDownloads),
			summaryStats: instance.reportsData,
			daysLength: daysLength
		};

	}

	/**
	 * Let's create our chart.
	 * 
	 * @param {*} data 
	 * @param {*} chartId 
	 */
	dlmCreateChart(data, chartId) {

		if (data && chartId) {

			const instance = this;

			const chart = Chart.getChart('total_downloads_chart');

			if ('undefined' !== typeof chart) {
				chart.destroy();
			}

			// Set here the dataSets
			const dataSets = [{
				label: 'Downloads',
				//color: '#27ae60',
				color: 'a299ff',
				data: data,
				type: 'line',
				fill: true,
				backgroundColor: instance.chartGradient,
				pointBackgroundColor: instance.chartColors.blue.default,
				pointHoverBackgroundColor: '#fff',
				borderColor: instance.chartColors.blue.default,
				pointBorderWidth: 6,
				lineTension: 0.2,
				borderWidth: 2,
				pointRadius: 3,
				elements: {
					line: {
						borderColor: '#2ecc71',
						borderWidth: 2,
					},
					point: {
						radius: 4,
						hoverRadius: 8,
						pointStyle: 'circle'
					}
				},
			}, ];

			instance.chart = new Chart(chartId, {
				title: "",
				data: {
					datasets: dataSets
				},
				height: 450,
				is_series: 1,
				options: {
					aspectRatio: 5,
					animation: false,
					scales: {
						x: {
							grid: {
								display: false,
							},
							ticks: {
								callback: (val) => {
									return ('undefined' !== typeof instance.chartType && 'month' === instance.chartType) ? moment(Object.keys(data)[val]).format("D MMM YY") : moment(Object.keys(data)[val]).format("D MMM");
								}
							},
						},
						y: {
							ticks: {
								align: 'center',
								display: true,
								}
							},
					},
					normalized: true,
					parsing: {
						xAxisKey: 'x',
						yAxisKey: 'y'
					},
					plugins: {
						legend: {
							display: false,
						},
						tooltip: {
							// Should be deleted if we remain on external tooltip
							/* backgroundColor: '#fff',
							titleColor: instance.chartColors.blue.default,
							yAlign: "bottom",
							xAlign: "bottom",
							titleAlign: 'center',
							titleFont: {
								weight: 'bold',
								size: 18
							},
							padding: {
								left: 15,
								right: 15,
								top: 30,
								bottom: 30,
							},
							cornerRadius: 8,
							borderColor: instance.chartColors.blue.default,
							borderWidth: 1,
							displayColors: false,
							bodyColor: '#000',
							callbacks: {
								title: context => context[0].formattedValue,
								label: context => '',
								beforeLabel: context => 'Downloads',
								afterLabel: context => ('undefined' !== instance.chartType && 'month' === instance.chartType) ? moment(context.label).format("MMMM, YYYY") : moment(context.label).format("dddd, MMMM Do YYYY"),
								labelTextColor: context => instance.chartColors.blue.half,
							}, */
							enabled: false,
							external: instance.externalTooltipHandler.bind(instance,this),
						},
					},
				},
			});
		}
>>>>>>> Stashed changes
	}
	var chartId = document.getElementById('total_downloads_chart');
	this.chart = new Chart( chartId, {
		title: "",
		data: this.data,
		type: this.queryData.type,
		height: 250,
		show_dots: 0,
		x_axis_mode: "tick",
		y_axis_mode: "span",
		is_series: 1,
	} );
};

/**
 * DLM_Reports_Block_Summary
 *
 * @param c
 * @constructor
 */
var DLM_Reports_Block_Summary = function ( c ) {

	this.container = c;
	this.id = null;

	this.data = null;

	this.data = null;
	this.chart = null;

	this.setup = function () {
		this.id = jQuery( this.container ).attr( 'id' );
		this.data = new DLM_Reports_Data( this.container );
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Summary.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Summary.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Summary.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.data, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Summary.prototype.render = function () {
	if ( this.data === null ) {
		return;
	}

	var instance = this;

	jQuery.each( this.data, function ( k, v ) {
		if ( jQuery( instance.container ).find( '#' + k ) ) {
			jQuery( instance.container ).find( '#' + k ).find( 'span:first' ).html( v );
		}
	} );
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
		this.displayLoader();
		this.fetch();
	};

	this.setup();

};

DLM_Reports_Block_Table.prototype.displayLoader = function () {
	jQuery( this.container ).append( DLM_createLoaderObj() );
};

DLM_Reports_Block_Table.prototype.hideLoader = function () {
	jQuery( this.container ).find( '.dlm_reports_loader' ).remove();
};

DLM_Reports_Block_Table.prototype.fetch = function () {
	var instance = this;
	new DLM_Reports_Data_Fetch( this.id, this.data, function ( response ) {
		instance.data = response;
		instance.hideLoader();
		instance.render();
	} );
};

DLM_Reports_Block_Table.prototype.render = function () {
	if ( this.data === null || (this.data.length < 2 && 'undefined' === typeof this.data['total_downloads_browser_table']) ) {
		return;
	}

	var instance = this;

	if ( 'undefined' !== typeof this.data['total_downloads_browser_table'] ) {

		var $data = this.data['total_downloads_browser_table'];
		var navigation = '<h2 class="dlm-reports-tab-navigation nav-tab-wrapper">';
		jQuery( this.container ).html( '' );
		jQuery( this.container ).append('<div class="">');

		Object.keys( $data ).forEach( key => {

			// the table
			var table = jQuery( document.createElement( 'table' ) );
			var table_class = 'hidden';
			var link_class = '';

			if ( 'desktop' == key ) {
				table_class = '';
				link_class = 'nav-tab-active';
			}

			navigation += '<a href="#' + key + '" class="nav-tab ' + link_class + '">' + key + '</a>';

			table.attr( 'cellspacing', 0 ).attr( 'cellpadding', 0 ).attr( 'border', 0 ).attr( 'id', key ).attr( 'class', table_class );

			// setup header row
			var headerRow = document.createElement( 'tr' );

			for ( var i = 0; i < $data[key][0].length; i++ ) {

				var th = document.createElement( 'th' );
				th.innerHTML = $data[key][0][i];
				headerRow.appendChild( th );
			}

			// append header row
			table.append( headerRow );

			for ( var i = 1; i < $data[key].length; i++ ) {
				// new row
				var tr = document.createElement( 'tr' );

				// loop
				for ( var j = 0; j < $data[key][i].length; j++ ) {
					var td = document.createElement( 'td' );
					td.innerHTML = $data[key][i][j];
					tr.appendChild( td );
				}
				// append row
				table.append( tr );
			}

			// put table in container
			jQuery( this.container ).append( table );
		} );

		navigation += '</div>';

		jQuery( this.container ).prepend( navigation );

	} else {

		// the table
		var table = jQuery( document.createElement( 'table' ) );

		table.attr( 'cellspacing', 0 ).attr( 'cellpadding', 0 ).attr( 'border', 0 );

		// setup header row
		var headerRow = document.createElement( 'tr' );

		for ( var i = 0; i < this.data[0].length; i++ ) {
			var th = document.createElement( 'th' );
			th.innerHTML = this.data[0][i];
			headerRow.appendChild( th );
		}

		// append header row
		table.append( headerRow );

		for ( var i = 1; i < this.data.length; i++ ) {
			// new row
			var tr = document.createElement( 'tr' );

			// loop
			for ( var j = 0; j < this.data[i].length; j++ ) {
				var td = document.createElement( 'td' );
				td.innerHTML = this.data[i][j];
				tr.appendChild( td );
			}

			// append row
			table.append( tr );
		}

		// put table in container
		jQuery( this.container ).html( '' ).append( table );
	}

};