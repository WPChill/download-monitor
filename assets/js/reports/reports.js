/**
 * DLM Reports script
 */
jQuery(function ($) {

	// Let's initiate the reports.
	const reports = new DLM_Reports();

	// Set spinner so that users know there is something going on.
	dlmReportsInstance.setSpinner(jQuery('.total_downloads_chart-wrapper'));
	dlmReportsInstance.setSpinner(jQuery('#users_download_log'));
	dlmReportsInstance.setSpinner(jQuery('#total_downloads_table_wrapper2'));

	// Let's get the available Downloads
	dlmReportsInstance.fetchDownloadsCPT();
});

/**
 * DLM Reports class
 */
class DLM_Reports {
	dlmReportsStats = [];
	dlmUsersStats   = {
		logs : [],
		users: []
	};
	currentFilters  = [];
	tempDownloads   = null;
	templates       = {};
	totalDownloads  = 0;
	perPage         = dlmReportsPerPage;
	downloads = [];
	topDownloadsOrder = 'count';

	/**
	 * The constructor for our class
	 */
	constructor() {
		dlmReportsInstance                = this;
		dlmReportsInstance.chartContainer = document.getElementById('total_downloads_chart');
		const ctx                         = dlmReportsInstance.chartContainer.getContext("2d");

		/**
		 * Gradient for the chart
		 */
		dlmReportsInstance.chartColors = {
			purple        : {
				default  : "rgba(149, 76, 233, 1)",
				threesome: "rgba(149, 76, 233, 0.75)",
				half     : "rgba(149, 76, 233, 0.5)",
				quarter  : "rgba(149, 76, 233, 0.5)",
				zero     : "rgba(149, 76, 233, 0.05)"
			}, blue       : {
				default  : "rgba(67, 56, 202, 1)",
				threesome: "rgba(67, 56, 202, 0.75)",
				half     : "rgba(67, 56, 202, 0.5)",
				quarter  : "rgba(67, 56, 202, 0.25)",
				zero     : "rgba(67, 56, 202, 0.05)"
			}, green      : {
				default  : "rgba(00, 255, 00, 1)",
				threesome: "rgba(00, 255, 00, 0.75)",
				half     : "rgba(00, 255, 00, 0.5)",
				quarter  : "rgba(00, 255, 00, 0.25)",
				zero     : "rgba(67, 56, 202, 0.05)",
			}, royalBlue  : {
				default  : "rgba(65, 105, 225, 1)",
				threesome: "rgba(65, 105, 225, 0.75)",
				half     : "rgba(65, 105, 225, 0.5)",
				quarter  : "rgba(65, 105, 225, 0.25)",
				zero     : "rgba(65, 105, 225, 0.05)",
			}, persianBlue: {
				default  : "rgba(28, 57, 187, 1)",
				threesome: "rgba(28, 57, 187, 0.75)",
				half     : "rgba(28, 57, 187, 0.5)",
				quarter  : "rgba(28, 57, 187, 0.25)",
				zero     : "rgba(28, 57, 187, 0.05)",
			}, darkCyan   : {
				default  : "rgba(0,129,167, 1)",
				threesome: "rgba(0,129,167, 0.75)",
				half     : "rgba(0,129,167, 0.5)",
				quarter  : "rgba(0,129,167, 0.25)",
				zero     : "rgba(0,129,167, 0.05)",
			}, strongCyan : {
				default  : "rgba(0, 175, 185, 1)",
				threesome: "rgba(0, 175, 185, 0.75)",
				half     : "rgba(0, 175, 185, 0.5)",
				quarter  : "rgba(0, 175, 185, 0.25)",
				zero     : "rgba(0, 175, 185, 0.05)",
			}
		};
		dlmReportsInstance.chartGradient = ctx.createLinearGradient(0, 25, 0, 300);
		dlmReportsInstance.chartGradient.addColorStop(0, dlmReportsInstance.chartColors.darkCyan.half);
		dlmReportsInstance.chartGradient.addColorStop(0.45, dlmReportsInstance.chartColors.darkCyan.quarter);
		dlmReportsInstance.chartGradient.addColorStop(1, dlmReportsInstance.chartColors.darkCyan.zero);
		dlmReportsInstance.datePickerContainer = document.getElementById('dlm-date-range-picker');
		dlmReportsInstance.dataSets            = [];
		let date                               = new Date();
		dlmReportsInstance.dates               = {
			downloads: {
				start_date: new Date(date.setMonth(date.getMonth() - 1)),
				end_date  : new Date()
			}
		};
		dlmReportsInstance.chartDataObject     = {};
	}

	/**
	 * Fetch our needed data from REST API. This includes the init() function because we need our data to be present and the moment of the initialization
	 *
	 * @param offset Offset used for database query
	 * @param limit Limit used for database query
	 */
	async fetchReportsData(offset = 0, limit = 1000) {

		const pageWrapper          = jQuery('div[data-id="general_info"]');

		// Let's see if these are pretty permalinks or plain
		let fetchingLink = dlmDownloadReportsAPI + '&offset=' + offset + '&limit=' + limit;
		// Fetch our data
		const fetchedDownloadsData = await fetch(fetchingLink);

		if (!fetchedDownloadsData.ok) {
			const errorText     = document.createElement('div');
			errorText.className = "dlm-loading-data";

			const t1                                    = document.createTextNode('Seems like we bumped into an error! '),
				  t2                                    = document.createTextNode('Data fetching returned a status text of : ' + fetchedData.statusText),
				  p1 = document.createElement('h1'), p2 = document.createElement('h3');
			p1.appendChild(t1);
			p2.appendChild(t2);
			errorText.appendChild(p1);
			errorText.appendChild(p2);
			pageWrapper.find('.dlm-loading-data').remove();
			pageWrapper.append(errorText);
			throw new Error('Something went wrong! Reports response did not come OK - ' + fetchedData.statusText);
		}

		dlmReportsInstance.mostDownloaded  = false;
		dlmReportsInstance.stats           = false;
		dlmReportsInstance.chartType       = 'day';

		let response                          = await fetchedDownloadsData.json();
		dlmReportsInstance.dlmReportsStats = dlmReportsInstance.dlmReportsStats.concat(response.stats);

		if (true === response.done) {
			// Set the "all time" reports if URL has dlm_time. Used when coming from Dashboard widget
			if (window.location.href.indexOf('dlm_time') > 0) {
				dlmReportsInstance.dates.downloads.start_date = (Object.keys(dlmReportsInstance.dlmReportsStats).length > 0) ? new Date(dlmReportsInstance.dlmReportsStats[0].date) : new Date();
				dlmReportsInstance.dates.downloads.end_date   = new Date();
				jQuery('#dlm-date-range-picker .date-range-info').html(dlmReportsInstance.dates.downloads.start_date.toLocaleDateString(undefined, {
					year: 'numeric', month: 'short', day: '2-digit'
				}) + ' - ' + dlmReportsInstance.dates.downloads.end_date.toLocaleDateString(undefined, {
					year: 'numeric', month: 'short', day: '2-digit'
				}));
			}
			dlmReportsInstance.createDataOnDate(dlmReportsInstance.dates.downloads.start_date, dlmReportsInstance.dates.downloads.end_date);

			dlmReportsInstance.datePicker = {
				opened: false
			};

			jQuery(document).trigger('dlm_downloads_report_fetched', [
				dlmReportsInstance,
				dlmReportsInstance.dlmReportsStats
			]);

			dlmReportsInstance.stopSpinner(jQuery('.total_downloads_chart-wrapper'));
			dlmReportsInstance.init();
		} else {
			dlmReportsInstance.fetchReportsData(response.offset);
		}
	}

	/**
	 * The request for users reports
	 *
	 * @param offset
	 * @param limit
	 * @returns {Promise<void>}
	 */
	async fetchUsersReportsData(offset = 0, limit = dlmPHPinfo['retrieved_rows']) {

		const wrapper         = jQuery('div[data-id="user_reports"]');

		// Let's see if these are pretty permalinks or plain
		let fetchingLink = dlmUserReportsAPI + '&offset=' + offset + '&limit=' + limit;

		const fetchedUserData = await fetch(fetchingLink);

		if (!fetchedUserData.ok) {
			throw new Error('Something went wrong! Reports response did not come OK - ' + fetchedUserData.statusText);
		}

		let response                          = await fetchedUserData.json();
		dlmReportsInstance.dlmUsersStats.logs = dlmReportsInstance.dlmUsersStats.logs.concat(response.logs);

		if (true === response.done) {
			dlmReportsInstance.userDownloads = ('undefined' !== typeof dlmReportsInstance.dlmUsersStats.logs) ? JSON.parse(JSON.stringify(dlmReportsInstance.dlmUsersStats.logs)) : {};
			wrapper.find('.dlm-loading-data').remove();
			dlmReportsInstance.userReportsTab();
			dlmReportsInstance.setTopDownloads();
			dlmReportsInstance.stopSpinner(jQuery('#total_downloads_table_wrapper2'));
		} else {
			dlmReportsInstance.fetchUsersReportsData(response.offset);
		}
	}

	/**
	 * The request for user data
	 * @returns {Promise<void>}
	 */
	async fetchUserData( offset = 0, limit = 5000 ) {

		// Let's see if these are pretty permalinks or plain
		let fetchingLink = dlmUserDataAPI + '&offset=' + offset + '&limit=' + limit;

		const fetchedUserData = await fetch(fetchingLink);

		if (!fetchedUserData.ok) {
			throw new Error('Something went wrong! Reports response did not come OK - ' + fetchedUserData.statusText);
		}

		let response                           = await fetchedUserData.json();
		dlmReportsInstance.dlmUsersStats.users = dlmReportsInstance.dlmUsersStats.users.concat(response.logs);

		if (true === response.done) {

			// Get the data used for the chart. We get it so that the users are completed and we won't possibly overload the server
			dlmReportsInstance.fetchReportsData();

		} else {
			
			dlmReportsInstance.fetchUserData(response.offset);
		}


	}

	/**
	 * Init our methods
	 */
	init() {

		dlmReportsInstance.tabNagivation();
		dlmReportsInstance.overViewTab();
		dlmReportsInstance.togglePageSettings();

		dlmReportsInstance.fetchUsersReportsData();
		// Trigger action so others can hook into this
		jQuery(document).trigger('dlm_reports_init', [dlmReportsInstance]);
		dlmReportsInstance.eventsFunctions();
	}

	/**
	 * The overview functionality
	 */
	overViewTab() {

		dlmReportsInstance.dlmCreateChart(dlmReportsInstance.stats.chartStats, dlmReportsInstance.chartContainer);
		dlmReportsInstance.dlmDownloadsSummary();
		dlmReportsInstance.datePickerContainer.addEventListener('click', dlmReportsInstance.toggleDatepicker.bind(this));
		dlmReportsInstance.setTodayDownloads();
		dlmReportsInstance.handleTopDownloads();

		jQuery(document).on('click', 'body', function (event) {

			event.stopPropagation();

			if (jQuery(dlmReportsInstance.datePickerContainer).find('#dlm_date_range_picker').length > 0) {
				dlmReportsInstance.hideDatepicker(jQuery(dlmReportsInstance.datePickerContainer), {target: 'dlm-date-range-picker'});
			}
		});
	}

	/**
	 * The user reports functiuonality
	 */
	userReportsTab() {

		if (0 === Object.values(dlmReportsInstance.dlmUsersStats).length) {
			return;
		}
		dlmReportsInstance.logsDataByDate(dlmReportsInstance.dates.downloads.start_date, dlmReportsInstance.dates.downloads.end_date);
		dlmReportsInstance.handleUserDownloads();
		dlmReportsInstance.filterDownloads();
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

		const dates     = {};
		let currentDate = startDate;

		while (currentDate.getTime() < endDate.getTime()) {
			dates[this.createDateElement(currentDate)] = 0;
			currentDate                                = this.getNextDay(currentDate);
		}
		// Add another day to get the last day
		dates[this.createDateElement(currentDate)] = 0;
		currentDate                                = this.getNextDay(currentDate);

		return dates;
	}

	/**
	 * Get all months in set intervals
	 * Used for chart data
	 *
	 * @returns {*[]}
	 * @param days
	 */
	getMonths(days) {

		const dates = {};

		Object.keys(days).map(element => {

			let subString = element.substring(0, 7);

			if ('undefined' === typeof dates[subString]) {
				dates[subString] = 0;
			}
		});

		return dates;
	}

	/**
	 * Get all 2 months in set intervals
	 * Used for chart data
	 *
	 * @returns {*[]}
	 * @param days
	 */
	getDoubleMonths(days) {

		const dates = {}, firstDay = Object.keys(days)[0],
			  lastDay              = Object.keys(days)[Object.keys(days).length - 1];

		let i = 0, month = firstDay.substring(0, 7), lastMonth = lastDay.substring(0, 7);

		Object.keys(days).map(element => {

			let subString = element.substring(0, 7);

			if (month !== subString && lastMonth !== subString) {
				month = subString;
				i++;
			}

			if ('undefined' === typeof dates[subString] && 0 === i % 2) {
				dates[subString] = 0;
			}

		});

		return dates;
	}

	/**
	 * Get all 2 weeks in set intervals
	 * Used for chart data
	 *
	 * @returns {*[]}
	 * @param days
	 */
	getWeeks(days) {

		let dates = {};
		Object.keys(days).forEach(element => {
			let week;
			if (moment(element).date() > 15) {
				week = element.substring(0, 7) + '-15';
			} else {
				week = element.substring(0, 7) + '-01';
			}

			if ('undefined' === typeof dates[week]) {
				dates[week] = 0;
			}
		});

		return dates;
	}

	/**
	 * Get all weeks in set intervals
	 * Used for chart data
	 *
	 * @returns {*[]}
	 * @param days
	 */
	getWeek(days) {

		let dates   = {},
			lastDay = Object.keys(days)[Object.keys(days).length - 1],
			i       = 0;

		Object.keys(days).map(element => {
			if ('undefined' === typeof dates[element] && 0 === i % 7) {
				dates[element] = 0;
			}
			i++;
		});

		// Also add last date if not in array
		if ('undefined' === typeof dates[lastDay]) {
			dates[lastDay] = 0;
		}
		return dates;
	}

	/**
	 * Get all 2 months in set intervals
	 * Used for chart data
	 *
	 * @returns {*[]}
	 * @param days
	 */
	getDoubleDays(days) {

		let dates = {}, firstDay = Object.keys(days)[0],
			lastDay              = Object.keys(days)[Object.keys(days).length - 1];

		let i = 0;
		Object.keys(days).map(element => {

			if (firstDay !== element && lastDay !== element) {
				firstDay = element;
				i++;
			}

			if ('undefined' === typeof dates[element] && 0 === i % 2) {
				dates[element] = 0;
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

		var MM = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);

		return date.getFullYear() + '-' + MM + '-' + ("0" + date.getDate()).slice(-2);
	}

	/**
	 * Get set dates by datepicker
	 *
	 * @param startDateInput
	 * @param endDateInput
	 * @returns {{endDate: *, startDate: *}}
	 */
	getSetDates(startDateInput, endDateInput) {

		let startDate, endDate;
		if ('undefined' !== typeof startDateInput && startDateInput) {

			startDate = dlmReportsInstance.createDateElement(new Date(startDateInput));
		} else {
			// If there are no startDateInput it means it is the first load, so get last 30 days.
			const lastMonth = new Date();
			lastMonth.setDate(lastMonth.getDate() - 30);
			startDate = dlmReportsInstance.createDateElement(lastMonth);

		}

		if ('undefined' !== typeof endDateInput && endDateInput) {
			let trueEnd = new Date(endDateInput);
			endDate     = dlmReportsInstance.createDateElement(trueEnd);

		} else {

			// If there is no endDateInput we'll set the endDate to tomorrow so that we can include today in our reports also.
			// Seems like this is how the datepicker works.
			const tomorrow = new Date();
			tomorrow.setDate(tomorrow.getDate() + 1);
			endDate = dlmReportsInstance.createDateElement(tomorrow);
		}
		return {startDate, endDate};
	}

	/**
	 * Filter data to send to chart based on user input start & end date
	 *
	 * @param startDateInput
	 * @param endDateInput
	 * @returns {*}
	 */
	createDataOnDate(startDateInput, endDateInput) {

		let {startDate, endDate} = {...dlmReportsInstance.getSetDates(startDateInput, endDateInput)}, dayDiff,
			monthDiff,
			yearDiff, chartDate, dlmDownloads;

		dlmReportsInstance.reportsData = ('undefined' !== typeof dlmReportsInstance.dlmReportsStats) ? JSON.parse(JSON.stringify(dlmReportsInstance.dlmReportsStats)) : {};
		monthDiff                      = moment(endDate, 'YYYY-MM-DD').month() - moment(startDate, 'YYYY-MM-DD').month();
		yearDiff                       = moment(endDate, 'YYYY-MM-DD').year() - moment(startDate, 'YYYY-MM-DD').year();
		dayDiff                        = moment(endDate).date() - moment(startDate).date();
		dlmReportsInstance.chartType   = 'day';

		if (yearDiff == 0 && monthDiff > -6 && monthDiff < 6) {
			if (monthDiff > 1 || monthDiff < -1) {
				if (2 === monthDiff) {
					dlmReportsInstance.chartType = 'week';
				} else {
					dlmReportsInstance.chartType = 'weeks';
				}
			} else {
				if (1 === monthDiff && (dayDiff > 8 || dayDiff > -14 || 0 === dayDiff)) {
					dlmReportsInstance.chartType = 'days';
				}
			}
		} else {
			if (yearDiff = 1 && monthDiff <= 0) {
				dlmReportsInstance.chartType = 'month';
			} else {
				dlmReportsInstance.chartType = 'months';
			}
		}

		// Get all dates from the startDate to the endDate
		let dayDownloads = dlmReportsInstance.getDates(new Date(startDate), new Date(endDate)),
			doubleDays, doubleMonthDownloads, weeksDownloads, weekDownloads, monthDownloads;

		// Let's initiate our dlmDownloads with something
		switch (dlmReportsInstance.chartType) {
			case 'months':
				// Get double selected months
				doubleMonthDownloads = dlmReportsInstance.getDoubleMonths(dayDownloads);
				dlmDownloads         = doubleMonthDownloads;
				break;
			case 'month':
				// Get selected months
				monthDownloads = dlmReportsInstance.getMonths(dayDownloads);
				dlmDownloads       = monthDownloads;
				break;
			case 'weeks':
				// Get selected dates in 2 weeks grouping
				weeksDownloads = dlmReportsInstance.getWeeks(dayDownloads);
				dlmDownloads   = weeksDownloads;
				break
			case 'week':
				// Get selected dates in 2 weeks grouping
				weekDownloads = dlmReportsInstance.getWeek(dayDownloads);
				dlmDownloads  = weekDownloads;
				break
			case 'days':
				// Get double days
				doubleDays   = dlmReportsInstance.getDoubleDays(dayDownloads);
				dlmDownloads = doubleDays;
				break
			case 'day':
				dlmDownloads = dayDownloads;
				break;
		}

		Object.values(dlmReportsInstance.reportsData).forEach((day, index) => {

			const downloads = JSON.parse(day.download_ids);

			if ('undefined' !== typeof dayDownloads[day.date]) {

				switch (dlmReportsInstance.chartType) {
					case 'months':
						chartDate      = day.date.substring(0, 7);
						let chartMonth = parseInt(day.date.substring(5, 7)),
							chartYear  = day.date.substring(0, 5),
							prevDateI  = (chartMonth - 1).length > 6 ? chartYear + (chartMonth - 1) : chartYear + '0' + (chartMonth - 1);

						Object.values(downloads).forEach((item, index) => {

							// If it does not exist we attach the downloads to the previous month
							if ('undefined' === typeof doubleMonthDownloads[chartDate]) {
								if ('undefined' !== typeof doubleMonthDownloads[prevDateI]) {
									doubleMonthDownloads[prevDateI] = doubleMonthDownloads[prevDateI] + item.downloads;
								}
							} else {
								doubleMonthDownloads[chartDate] = doubleMonthDownloads[chartDate] + item.downloads;
							}

						});

						dlmDownloads = doubleMonthDownloads;
						break;
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
							chartDate = day.date.substring(0, 7) + '-01';
						}

						Object.values(downloads).forEach((item, index) => {
							weeksDownloads[chartDate] = ('undefined' !== typeof weeksDownloads[chartDate]) ? weeksDownloads[chartDate] + item.downloads : item.downloads;
						});

						dlmDownloads = weeksDownloads;
						break;
					case 'week':

						chartDate = day.date;
						Object.values(downloads).forEach((item, index) => {
							// If it does not exist we attach the downloads to the previous month
							if ('undefined' === typeof weekDownloads[chartDate]) {
								for (let $i = 1; $i < 8; $i++) {
									let $currentDayOfWeek = moment(day.date).date(moment(day.date).date() - $i).format("YYYY-MM-DD");
									if ('undefined' !== typeof weekDownloads[$currentDayOfWeek]) {
										weekDownloads[$currentDayOfWeek] = weekDownloads[$currentDayOfWeek] + item.downloads;
									}
								}
							} else {
								weekDownloads[chartDate] = weekDownloads[chartDate] + item.downloads;
							}
						});

						dlmDownloads = weekDownloads;
						break;
					case 'days':

						chartDate    = day.date;
						let prevDate = moment(day.date).date(moment(day.date).date() - 1).format("YYYY-MM-DD");

						Object.values(downloads).forEach((item, index) => {
							// If it does not exist we attach the downloads to the previous month
							if ('undefined' === typeof doubleDays[chartDate]) {
								if ('undefined' !== typeof doubleDays[prevDate]) {
									doubleDays[prevDate] = doubleDays[prevDate] + item.downloads;
								}
							} else {
								doubleDays[chartDate] = doubleDays[chartDate] + item.downloads;
							}
						});

						dlmDownloads = doubleDays;
						break;
					case 'day':

						Object.values(downloads).forEach((item, index) => {
							dayDownloads[day.date] = dayDownloads[day.date] + item.downloads;
						});

						dlmDownloads = dayDownloads;
						break;
				}
			} else {
				delete dlmReportsInstance.reportsData[index];
			}
		});

		// Get number of days, used in summary for daily average downloads
		const dayKeys    = Object.keys(dayDownloads);
		const daysLength = dayKeys.length;

		// Find the start of the downloads object
		let start = dayKeys.findIndex((element) => {
			return startDate === element;
		});

		// Find the end of the downloads object
		let end = dayKeys.findIndex((element) => {
			return endDate === element;
		});

		if (-1 === start && -1 === end) {

			dlmReportsInstance.stats = {
				chartStats  : Object.assign({}, dlmDownloads),
				summaryStats: false,
				daysLength
			};
			return;
		}

		if (-1 === start) {
			start = 0;
		}

		if (-1 === end) {
			end = daysLength;
		}

		dlmReportsInstance.stats = {
			chartStats  : Object.assign({}, dlmDownloads),
			summaryStats: dlmReportsInstance.reportsData,
			daysLength
		};
	}

	/**
	 * Let's create our chart.
	 *
	 * @param {*} data
	 * @param {*} chartId
	 * @param otherData Check if data passsed in the original chart data or some other, like the compare data
	 */
	dlmCreateChart(data, chartId, otherData = false) {

		if (data && chartId) {

			let chart = Chart.getChart('total_downloads_chart');

			dlmReportsInstance.chartDataObject = {
				dataSetLabel       : 'Downloads',
				dataSetColor       : '#27ae60',
				dataSetbg          : dlmReportsInstance.chartGradient,
				dataSetPointbg     : dlmReportsInstance.chartColors.darkCyan.default,
				dataSetBorder      : dlmReportsInstance.chartColors.darkCyan.default,
				dataSetElementColor: '#2ecc71',
				lineType           : 'original',
				xAxis              : 'x',
				chartData          : data
			}

			if ('undefined' !== typeof chart) {
				chart.destroy();
			}

			// Trigger this action here in order for us to tap into dlmReportsInstance.chartDataObject
			jQuery(document).trigger('dlm_reports_before_data_sets', [
				dlmReportsInstance.chartDataObject,
				data,
				otherData
			]);

			// Unset the dataSet that will be modified
			if (dlmReportsInstance.dataSets.length > 0) {
				dlmReportsInstance.dataSets = dlmReportsInstance.dataSets.filter((element) => {

					if (dlmReportsInstance.chartDataObject.lineType === element.origin) {
						return false;
					}

					return true;
				});
			}

			dlmReportsInstance.dataSets.push(
				{
					origin                   : dlmReportsInstance.chartDataObject.lineType,
					label                    : dlmReportsInstance.chartDataObject.dataSetLabel,
					color                    : dlmReportsInstance.chartDataObject.dataSetColor,
					data                     : dlmReportsInstance.chartDataObject.chartData,
					type                     : 'line',
					fill                     : true,
					backgroundColor          : dlmReportsInstance.chartDataObject.dataSetbg,
					pointBackgroundColor     : dlmReportsInstance.chartDataObject.dataSetPointbg,
					pointHoverBackgroundColor: '#fff',
					borderColor              : dlmReportsInstance.chartDataObject.dataSetBorder,
					pointBorderWidth         : 1,
					lineTension              : 0.3,
					borderWidth              : 1,
					pointRadius              : 3,
					elements                 : {
						line    : {
							borderColor: dlmReportsInstance.chartDataObject.dataSetElementColor,
							borderWidth: 1,
						}, point: {
							radius: 4, hoverRadius: 4, pointStyle: 'circle'
						}
					},
				}
			);

			// Get the original data, in case dataSets has otherData
			let currentData = Object.values(dlmReportsInstance.dataSets).filter((element) => {
				return 'original' === element.origin;
			});

			let trueData = Object.keys(currentData[0].data);
			// Sort the dataSets so that the Downloads data will always be first
			dlmReportsInstance.dataSets.sort(function (a, b) {
				if ('original' === a.origin) {
					return -1;
				}
				return 1;
			});

			dlmReportsInstance.chart = new Chart(chartId, {
				title    : "",
				data     : {
					datasets: dlmReportsInstance.dataSets
				},
				height   : 450,
				is_series: 1,
				options  : {
					aspectRatio: 5,
					animation  : false,
					interaction: {
						mode     : 'index',
						intersect: false,
					},
					stacked    : false,
					scales     : {
						x: {
							grid : {
								display: false,
							},
							ticks: {
								callback: (val) => {

									let date       = '';
									let dateString = '';

									dateString = trueData[val];

									const lastDate     = trueData[trueData.length - 1];
									const prevLastDate = moment(lastDate).month(moment(lastDate).month() - 1).format("YYYY-MM");

									if ('undefined' !== dlmReportsInstance.chartType && 'months' === dlmReportsInstance.chartType) {

										const month = moment(trueData[val]).month();

										if (11 > month) {
											if (dateString === prevLastDate) {
												date = moment(dateString).format("MMM, YYYY");
											} else {
												date = moment(dateString).format("MMM") + ' - ' + moment(dateString).month(month + 1).format("MMM") + moment(dateString).format(", YYYY");
											}

										} else {
											if (dateString === prevLastDate || dateString === lastDate) {
												date = moment(dateString).format("MMM, YYYY");
											} else {
												date = moment(dateString).format("MMM") + moment(dateString).format(" YYYY") + ' - ' + moment(dateString).month(month + 1).format("MMM") + moment(dateString).month(month + 1).format(", YYYY");
											}

										}
									} else if ('undefined' !== dlmReportsInstance.chartType && 'months' === dlmReportsInstance.chartType) {
										date = moment(dateString).format("MMMM, YYYY");
									} else {
										date = moment(dateString).format("D MMM");
									}

									return date;
								}
							},
						},
						y: {
							grid : {
								drawBorder: false,
							},
							min  : 0,
							max  : (0 !== dlmReportsInstance.getMaxDownload()) ? ( Math.ceil(dlmReportsInstance.getMaxDownload() / 10) === 1 ? dlmReportsInstance.getMaxDownload() + 1 : Math.ceil(dlmReportsInstance.getMaxDownload() / 10) * 10 ) : 100,
							ticks: {
								stepSize: (0 !== dlmReportsInstance.getMaxDownload()) ? Math.ceil(dlmReportsInstance.getMaxDownload() / 4) : 25,
								callback: (val) => {
									return dlmReportsInstance.shortNumber(val);
								}
							}
						}
					},
					normalized : true, parsing: {
						xAxisKey: 'x', yAxisKey: 'y'
					},
					plugins    : {
						tooltip: {
							enabled : false,
							external: dlmReportsInstance.externalTooltipHandler.bind(dlmReportsInstance, this),
						},
						legend : {
							display: true
						}
					},
				}
			});
		}
	}

	/**
	 * Our download summary based on the selected date range.
	 *
	 *
	 * @returns
	 */
	dlmDownloadsSummary() {

		let mostDownloaded = {};

		if (false === dlmReportsInstance.stats || false === dlmReportsInstance.stats.summaryStats || Object.keys(dlmReportsInstance.stats.summaryStats).length <= 0) {

			this.setTotalDownloads(0);
			this.setDailyAverage(0);
			this.setMostDownloaded('--');
			return;
		}
		dlmReportsInstance.totalDownloads = 0;
		// Lets prepare the items based on item id and not date so that we can get the most downloaded item
		dlmReportsInstance.stats.summaryStats.forEach((itemSet) => {

			itemSet = JSON.parse(itemSet.download_ids);

			Object.entries(itemSet).forEach(([key, item]) => {

				dlmReportsInstance.totalDownloads += item.downloads;
				mostDownloaded[key] = ('undefined' === typeof mostDownloaded[key]) ? {
					downloads: item.downloads, id: key, title: item.title
				} : {
					downloads: mostDownloaded[key]['downloads'] + item.downloads, id: key, title: item.title
				};
			});
		});

		dlmReportsInstance.mostDownloaded = dlmReportsInstance.orderItems(Object.values(mostDownloaded), 'desc', 'downloads');

		dlmReportsInstance.setTotalDownloads(dlmReportsInstance.totalDownloads);
		dlmReportsInstance.setDailyAverage((dlmReportsInstance.totalDownloads / parseInt(dlmReportsInstance.stats.daysLength)).toFixed(0));
		if ('undefined' !== typeof dlmReportsInstance.mostDownloaded[0]) {
			const mostDownloadedItem = dlmReportsInstance.getDownloadCPT(dlmReportsInstance.mostDownloaded[0].id);

			if ('undefined' !== typeof mostDownloadedItem) {
				dlmReportsInstance.setMostDownloaded(mostDownloadedItem.title.rendered);
			} else {
				dlmReportsInstance.setMostDownloaded(dlmReportsInstance.mostDownloaded[0].title);
			}
		}

	}

	/**
	 * Create our date picker
	 *
	 *
	 * @returns
	 */
	createDatepicker(target, opener, containerID) {

		const today = new Date();
		let dd      = today.getDate() - 1;
		let mm      = today.getMonth() + 1; //January is 0!
		let mmm     = mm - 1;
		const yyyy  = today.getFullYear();

		if (dd < 10) {
			dd = '0' + dd;
		}

		if (mm < 10) {
			mm = "0" + mm;
		}

		if (mmm < 10) {
			mmm = "0" + mmm;
		}
		const yesterday = yyyy + '-' + mm + '-' + dd;
		const lastMonth = yyyy + '-' + mmm + '-' + dd;

		var el        = jQuery('<div>').addClass('dlm_rdrs_overlay');
		var startDate = jQuery('<div>').attr('id', containerID.replace('#', ''));
		switch (opener.target) {
			case 'dlm-date-range-picker':
				dlmReportsInstance.startDateInput = jQuery('<input>').attr('type', 'hidden').attr('id', 'dlm_start_date').attr('value', lastMonth);
				dlmReportsInstance.endDateInput   = jQuery('<input>').attr('type', 'hidden').attr('id', 'dlm_end_date').attr('value', yesterday);
				el.append(startDate).append(dlmReportsInstance.startDateInput).append(dlmReportsInstance.endDateInput);
				break;
			default:
				jQuery(document).trigger('dlm_create_date_picker_' + opener.target, [
					dlmReportsInstance,
					el,
					startDate,
					lastMonth,
					yesterday
				]);
				break;
		}

		return el;
	}

	/**
	 * Display our date picker.
	 *
	 *
	 * @returns
	 */
	displayDatepicker(target, opener) {

		let containerID = '';

		if (!jQuery(target)) {
			return;
		}

		containerID = '#' + jQuery(target).attr('id').replace(/-/gi, '_');
		switch (opener.target) {
			case 'dlm-date-range-picker':
				if (dlmReportsInstance.datePicker.opened) {
					return;
				}
				dlmReportsInstance.datePicker.opened = true;
				break;
			default:
				jQuery(document).trigger('dlm_display_datepicker_' + opener.target, [
					dlmReportsInstance,
					opener,
					target
				]);
				break;
		}

		let element = dlmReportsInstance.createDatepicker(target, opener, containerID);
		target.append(element);
		const calendar_start_date = (Object.keys(dlmReportsInstance.dlmReportsStats).length > 0) ? new Date(dlmReportsInstance.dlmReportsStats[0].date) : new Date();
		const currDate            = new Date();
		let datepickerShortcuts   = [];

		// JS filter to add other shortcuts to the datepicker.
		jQuery(document).trigger('dlm_datepicker_shortcuts_' + opener.target, [dlmReportsInstance, opener, target, datepickerShortcuts]);

		var configObject = {
			separator      : ' to ',
			autoClose      : true,
			getValue       : function () {

			},
			setValue       : function (s, s1, s2) {
				element.find('input[type="hidden"]').first().val(s1);
				element.find('input[type="hidden"]').last().val(s2);

			},
			inline         : true,
			alwaysOpen     : true,
			container      : containerID, // End date should be current date
			endDate        : new Date(), // Start date should be the first info we get about downloads
			startDate      : calendar_start_date,
			showShortcuts  : true,
			shortcuts      : null,
			customShortcuts: datepickerShortcuts,
		};

		element.dateRangePicker(configObject).on('datepicker-change', (event, obj) => {

			if (obj.date1 && obj.date2) {

				const date_s = obj.date1.toLocaleDateString(undefined, {
					year: 'numeric', month: 'short', day: '2-digit'
				});

				const date_e = obj.date2.toLocaleDateString(undefined, {
					year: 'numeric', month: 'short', day: '2-digit'
				});

				element.parent().find('span.date-range-info').text(date_s + ' - ' + date_e);
			}

			switch (opener.target) {
				case 'dlm-date-range-picker':
					dlmReportsInstance.dates.downloads = {
						start_date: obj.date1,
						end_date  : obj.date2
					}
					// Recreate the stats
					dlmReportsInstance.createDataOnDate(dlmReportsInstance.dates.downloads.start_date, dlmReportsInstance.dates.downloads.end_date);

					dlmReportsInstance.dlmCreateChart(dlmReportsInstance.stats.chartStats, dlmReportsInstance.chartContainer, false);
					dlmReportsInstance.dlmDownloadsSummary();
					// This needs to be set after dlmReportsInstance.dlmDownloadsSummary() because it uses a variable set by it
					if (Object.values(dlmReportsInstance.dlmUsersStats.logs).length > 0) {
						dlmReportsInstance.logsDataByDate(dlmReportsInstance.dates.downloads.start_date, dlmReportsInstance.dates.downloads.end_date);
					}
					break;
				default:
					// JS action to trigger when custom datepicker is changed.
					jQuery(document).trigger('dlm_daterangepicker_init_' + opener.target, [dlmReportsInstance, obj.date1, obj.date2]);
					break;
			}
			dlmReportsInstance.setTopDownloads();
			element.data('dateRangePicker').close();
		});

		switch (opener.target) {
			case 'dlm-date-range-picker':
				element.data('dateRangePicker').setDateRange(dlmReportsInstance.dates.downloads.start_date, dlmReportsInstance.dates.downloads.end_date);
				break;
			default:
				jQuery(document).trigger('dlm_daterangepicker_after_init_' + opener.target, [element, dlmReportsInstance]);
				break;
		}
	}

	/**
	 * Hide the datepicker.
	 */
	hideDatepicker(target, opener) {

		switch (opener.target) {
			case 'dlm-date-range-picker' :
				dlmReportsInstance.datePicker.opened = false;
				break;
			default:
				jQuery(document).trigger('dlm_hide_datepicker_' + opener.target, [
					dlmReportsInstance,
					target,
					opener
				]);
				break;
		}

		target.find('.dlm_rdrs_overlay').remove();
	}

	/**
	 * Toggle the date picker on/off.
	 */
	toggleDatepicker(event) {

		event.stopPropagation();
		const target = jQuery(event.target).parents('.dlm-reports-header-date-selector');
		let opener   = {target: target.attr('id'), object: dlmReportsInstance.datePicker};
		dlmReportsInstance.closeDatePickers(target);
		switch (opener.target) {
			case 'dlm-date-range-picker':
				if (dlmReportsInstance.datePicker.opened) {
					dlmReportsInstance.hideDatepicker(target, opener);
				} else {
					dlmReportsInstance.displayDatepicker(target, opener);
				}
				break;
			default:
				jQuery(document).trigger('dlm_toggle_datepicker_' + opener.target, [
					dlmReportsInstance,
					target,
					opener
				]);
				break;
		}
	}

	/**
	 * Set total downloads based on the selected date range.
	 *
	 *
	 * @param {*} totalDownloads
	 */
	setTotalDownloads(totalDownloads) {
		jQuery('.dlm-reports-block-summary li#total span').html(totalDownloads.toLocaleString());
	}

	/**
	 * Set daily average based on the selected date range.
	 *
	 * @param {*} dailyAverage
	 */
	setDailyAverage(dailyAverage) {
		jQuery('.dlm-reports-block-summary li#average span').html(dailyAverage.toLocaleString());
	}

	/**
	 * Set most downloaded Download.
	 *
	 * @param {*} mostDownloaded
	 */
	setMostDownloaded(mostDownloaded) {
		jQuery('.dlm-reports-block-summary li#most_popular span').html(mostDownloaded); // this is a string
	}

	/**
	 * Set today's downloads.
	 */
	setTodayDownloads() {

		let todayDownloads = 0;

		if (0 >= Object.keys(dlmReportsInstance.dlmReportsStats).length) {

			jQuery('.dlm-reports-block-summary li#today span').html(todayDownloads.toLocaleString());
			return;
		}

		// We only need the last date from dlmReportsStats, as it will be the last entry from the DB in crhonological order.
		if (dlmReportsInstance.dlmReportsStats[dlmReportsInstance.dlmReportsStats.length - 1].date === dlmReportsInstance.createDateElement(new Date())) {

			todayDownloads = Object.values(JSON.parse(dlmReportsInstance.dlmReportsStats[dlmReportsInstance.dlmReportsStats.length - 1].download_ids)).reduce((prevValue, element) => {

				return prevValue + element.downloads;
			}, 0);

		}

		jQuery('.dlm-reports-block-summary li#today span').html(todayDownloads);
	}

	/**
	 * Set the top downloads with fetched templates.
	 *
	 * @param {*} offset
	 * @param {*} reset
	 * @returns
	 */
	setTopDownloads(offset = 0, reset = false) {
		// the table
		const wrapperParent = jQuery('#total_downloads_table_wrapper2'),
			  wrapper       = jQuery('#total_downloads_table_wrapper2 .total_downloads_table__list');

		wrapper.empty();
		wrapper.parent().addClass('empty');

		if (!dlmReportsInstance.mostDownloaded || true === reset) {
			return;
		}

		const dataResponse = JSON.parse(JSON.stringify(dlmReportsInstance.mostDownloaded)).slice(dlmReportsInstance.perPage * parseInt(offset), dlmReportsInstance.perPage * (parseInt(offset + 1)));

		for (let i = 0; i < dataResponse.length; i++) {

			const $download    = dlmReportsInstance.getDownloadByID(dataResponse[i].id);
			let $downloadCPT = dlmReportsInstance.getDownloadCPT(dataResponse[i].id);
			let title = '--';

			if ( 'undefined' === typeof $downloadCPT) {
				title = dataResponse[i].title;
			} else {
				title = $downloadCPT.title.rendered
			}

			// No point on showing the download if it doesn't exist
			if ('undefined' === typeof $download) {
				return
			}

			let itemObject = {
				id             : dataResponse[i].id,
				title          : dlmReportsInstance.htmlEntities( title ),
				edit_link      : dlmAdminUrl + 'post.php?post=' + dataResponse[i].id + '&action=edit',
				total_downloads: $download.total.toLocaleString()
			};

			// Trigger used to model the itemObject passed to the template
			jQuery(document).trigger('dlm_reports_top_downloads_item_before_render', [itemObject, dlmReportsInstance, dataResponse[i], $download]);

			let item = new dlmBackBone['modelTopDownloads'](itemObject);
		}

		wrapper.parent().removeClass('empty');
		// Set top downloads number of pages
		wrapperParent.find('.dlm-reports-total-pages').html(Math.ceil(dlmReportsInstance.mostDownloaded.length / dlmReportsInstance.perPage));

		if (parseInt(dlmReportsInstance.perPage) !== parseInt(dataResponse.length)) {
			wrapperParent.find('.downloads-block-navigation button[data-action="load-more"]').attr('disabled', 'disabled');
		} else {
			wrapperParent.find('.downloads-block-navigation button[data-action="load-more"]').removeAttr('disabled');
		}

		if (dlmReportsInstance.mostDownloaded.length > dlmReportsInstance.perPage) {
			wrapperParent.find('.downloads-block-navigation button').removeClass('hidden');
		} else {
			wrapperParent.find('.downloads-block-navigation button').addClass('hidden');
		}
		dlmReportsInstance.stopSpinner(jQuery('#total_downloads_table_wrapper2'));
	}

	/**
	 * Let's create the slider navigation. Will be based on offset
	 */
	handleTopDownloads() {

		jQuery('html body').on('click', '#total_downloads_table_wrapper2 .downloads-block-navigation button', function () {

			let main_parent                               = jQuery(this).parents('#total_downloads_table_wrapper2'),
				offsetHolder                              = main_parent,
				offset                                    = main_parent.attr('data-page'),
				link                                      = jQuery(this),
				nextPage = parseInt(offset) + 1, prevPage = (0 !== offset) ? parseInt(offset) - 1 : 0,
				prevButton                                = main_parent.find('.downloads-block-navigation').find('button').first(),
				nextButton                                = main_parent.find('.downloads-block-navigation').find('button').last();

			link.attr('disabled', 'disabled');
			const handleObj = {
				data    : dlmReportsInstance.mostDownloaded,
				main_parent,
				offsetHolder,
				offset,
				link,
				nextPage,
				prevPage,
				prevButton,
				nextButton,
				doAction: dlmReportsInstance.setTopDownloads
			}

			dlmReportsInstance.handleSliderNavigation(handleObj)
		});

		jQuery('#total_downloads_table_wrapper2').find('input.dlm-reports-current-page').on('change', function () {
			dlmReportsInstance.paginationChange(jQuery(this), dlmReportsInstance.mostDownloaded, jQuery('#total_downloads_table_wrapper2'), jQuery(this).parents('#total_downloads_table_wrapper2'), dlmReportsInstance.setTopDownloads);
		});
	}

	/**
	 * Slider navigation
	 *
	 * @param handleObj
	 */
	handleSliderNavigation(handleObj) {
		const {
				  data,
				  main_parent,
				  offsetHolder,
				  offset,
				  link,
				  nextPage,
				  prevPage,
				  prevButton,
				  nextButton,
				  doAction
			  } = {...handleObj};

		let page = 1;

		// Check if we click the next/load more button
		if ('load-more' === link.data('action')) {

			offsetHolder.attr('data-page', nextPage);
			doAction(nextPage);
			// We remove the disable attribute only when there are pages to be shown
			if (Math.ceil(data.length / dlmReportsInstance.perPage) > nextPage + 1) {
				nextButton.removeAttr('disabled');
			}
			prevButton.removeAttr('disabled');
			page = parseInt(nextPage) + 1;
		} else {

			if (0 !== parseInt(offset)) {

				//table.toggle({ effect: "scale", direction: "both" });
				offsetHolder.attr('data-page', prevPage);
				doAction(prevPage);
				// Remove on all buttons, as it will be at least page 2
				if (1 !== parseInt(offset)) {
					prevButton.removeAttr('disabled');
				}
				nextButton.removeAttr('disabled');
				page = parseInt(prevPage) + 1;
			}
		}

		main_parent.find('.dlm-reports-current-page').val(page);
	}

	/**
	 * Tab navigation
	 */
	tabNagivation() {
		jQuery(document).on('click', '.dlm-reports .dlm-insights-tab-navigation > li', function () {
			const listClicked     = jQuery(this),
				  navLists        = jQuery('.dlm-reports .dlm-insights-tab-navigation > li').not(listClicked),
				  contentTarget   = jQuery('div.dlm-insights-tab-navigation__content[data-id="' + listClicked.attr('id') + '"]'),
				  contentWrappers = jQuery('div.dlm-insights-tab-navigation__content').not(contentTarget);

			if (!listClicked.hasClass('active')) {
				listClicked.addClass('active');
				navLists.removeClass('active');
				contentTarget.addClass('active');
				contentWrappers.removeClass('active');
			}
		});
	}

	/**
	 * The external tooltip of the Chart
	 *
	 * @param chart
	 * @returns {*}
	 */
	getOrCreateTooltip(chart) {

		let tooltipEl   = chart.canvas.parentNode.querySelector('div.dlm-canvas-tooltip');
		let tooltipLine = chart.canvas.parentNode.querySelector('div.dlm-reports-tooltip__line');

		if (!tooltipEl) {
			tooltipLine           = document.createElement('div');
			tooltipLine.className = "dlm-reports-tooltip__line";
		}

		if (!tooltipEl) {
			tooltipEl           = document.createElement('div');
			tooltipEl.className = "dlm-canvas-tooltip";

			const tooltipWrapper     = document.createElement('div');
			tooltipWrapper.className = "dlm-reports-tooltip";
			tooltipEl.appendChild(tooltipWrapper);

			chart.canvas.parentNode.appendChild(tooltipEl);
			chart.canvas.parentNode.appendChild(tooltipLine);
		}

		return {tooltipEl, tooltipLine};
	}

	/**
	 * The external tooltip of the Chart handler
	 *
	 * @param plugin
	 * @param context
	 */
	externalTooltipHandler(plugin, context) {

		// Tooltip Element
		const {
				  chart, tooltip
			  } = context;

		const {tooltipEl, tooltipLine} = {...plugin.getOrCreateTooltip(chart)};
		const tooltipWidth             = jQuery(tooltipEl).parent().width();

		// Hide if no tooltip
		if (tooltip.opacity === 0) {
			tooltipEl.style.opacity   = 0;
			tooltipLine.style.opacity = 0;
			return;
		}

		// Set Text
		if (tooltip.body) {
			const titleLines = tooltip.title || [];

			const tooltipContent     = document.createElement('div');
			tooltipContent.className = "dlm-reports-tooltip__header";

			titleLines.forEach(title => {
				const tooltipRow     = document.createElement('div');
				tooltipRow.className = "dlm-reports-tooltip__row";

				// Info
				const downloadsInfo     = document.createElement('p');
				downloadsInfo.className = "dlm-reports-tooltip__info";
				downloadsInfo.appendChild(document.createTextNode('Downloads'));
				tooltipRow.appendChild(downloadsInfo);

				jQuery(document).trigger('dlm_chart_tooltip_before', [
					dlmReportsInstance,
					tooltip,
					tooltipRow,
					plugin
				]);

				// Date
				const downloadDate     = document.createElement('p');
				downloadDate.className = "dlm-reports-tooltip__date";

				let date = dlmReportsInstance.setChartTooltipDate(tooltip.dataPoints[0].label, plugin, plugin.stats.chartStats);

				downloadDate.appendChild(document.createTextNode(date));
				tooltipRow.appendChild(downloadDate);

				// Downloads number
				const downloads     = document.createElement('p');
				downloads.className = "dlm-reports-tooltip__downloads";

				// Pointer for the downloads chart
				const downloadsPointer                 = document.createElement('span');
				downloadsPointer.className             = "dlm-reports-tooltip__downloads_pointer";
				downloadsPointer.style.backgroundColor = dlmReportsInstance.chartColors.darkCyan.default;
				downloads.appendChild(downloadsPointer);

				downloads.appendChild(document.createTextNode(dlmReportsInstance.shortNumber(tooltip.dataPoints[0].formattedValue)));
				tooltipRow.appendChild(downloads);

				jQuery(document).trigger('dlm_chart_tooltip_after', [
					dlmReportsInstance,
					tooltip,
					tooltipRow,
					plugin
				]);

				// Create the whole content and append it
				tooltipContent.appendChild(tooltipRow);
			});

			const tooltipWRapper = tooltipEl.querySelector('div.dlm-reports-tooltip');

			// Remove old children
			while (tooltipWRapper.firstChild) {
				tooltipWRapper.firstChild.remove();
			}

			// Add new children
			tooltipWRapper.appendChild(tooltipContent);
		}

		const {
				  offsetLeft: positionX, offsetTop: positionY
			  } = chart.canvas;

		// Display, position, and set styles for font
		tooltipEl.style.opacity   = 1;
		tooltipLine.style.opacity = 1;
		let margin                = {
			isMargin: false, left: false
		};

		if (tooltip.caretX - tooltip.width < 0) {
			margin.isMargin = true;
			margin.left     = true;
		}

		if (positionX + tooltip.caretX + tooltip.width > tooltipWidth) {
			margin.isMargin = true;
			margin.left     = false;
		}

		if (!margin.isMargin) {
			tooltipEl.style.left = positionX + tooltip.caretX + 'px';

		} else {
			if (!margin.left) {
				tooltipEl.style.left = tooltipWidth - tooltip.width + 'px';
			} else {
				tooltipEl.style.left = positionX + tooltip.width + 'px';
			}
		}
		tooltipLine.style.left = positionX + tooltip.caretX + 'px';

		tooltipEl.style.top = (positionY + tooltip.caretY - tooltipEl.offsetHeight - 10) + 'px';
	}

	/**
	 * Create user related data
	 */
	createUserRelatedData() {

		dlmReportsInstance.userRelatedData = [];

		Object.values(dlmReportsInstance.userDownloads).forEach((download, index) => {
			if ('0' !== download.user_id) {
				const insert_array = [
					download.user_id,
					download.download_id,
					download.download_date,
					download.download_status
				];
				const user_key     = 'user_' + download.user_id;
				if ('undefined' !== typeof dlmReportsInstance.userRelatedData[user_key]) {
					dlmReportsInstance.userRelatedData[user_key].push(insert_array);
				} else {
					dlmReportsInstance.userRelatedData[user_key] = [insert_array];
				}
			}
		});
	}

	/**
	 * Create logs data by date
	 *
	 * @param startDateInput
	 * @param endDateInput
	 */
	logsDataByDate(startDateInput, endDateInput) {
		let {startDate, endDate}         = {...dlmReportsInstance.getSetDates(startDateInput, endDateInput)};
		dlmReportsInstance.userDownloads = JSON.parse(JSON.stringify(dlmReportsInstance.dlmUsersStats.logs));
		let startTimestamp               = new Date(startDate)
		startTimestamp.setDate(startTimestamp.getDate() - 1);
		startTimestamp = startTimestamp.getTime();

		let endTimestamp = new Date(endDate);
		endTimestamp.setDate(endTimestamp.getDate() + 1);
		endTimestamp = endTimestamp.getTime();
		dlmReportsInstance.userDownloads = dlmReportsInstance.userDownloads.filter((element, index) => {
			let currentElement = dlmReportsInstance.createDateElement(new Date(element.download_date));
			currentElement     = new Date(currentElement).getTime();
			return (currentElement > startTimestamp && currentElement < endTimestamp);
		});

		dlmReportsInstance.createUserRelatedData();
		dlmReportsInstance.filterDownloads()
		dlmReportsInstance.setMostActiveUser();
		dlmReportsInstance.setLoggedOutDownloads();
		dlmReportsInstance.setLoggedInDownloads();
		jQuery(document).trigger('dlm_set_logs_data_by_date', [dlmReportsInstance]);
	}

	/**
	 * Set the most active user
	 */
	setMostActiveUser() {
		const user = dlmReportsInstance.getUserByID(dlmReportsInstance.getMostActiveID()[0]);
		jQuery('.dlm-reports-block-summary li#most_active_user span').html(dlmReportsInstance.userToolTipMarkup(user));
	}

	/**
	 * Get the most active user
	 */
	getMostActiveID() {
		if (Object.values(dlmReportsInstance.userRelatedData).length) {
			return Object.values(dlmReportsInstance.userRelatedData).reduce((previousValue, currentValue, currentIndex) => {

				if (parseInt(previousValue.length) > parseInt(currentValue.length) && previousValue.length > 0 && null !== dlmReportsInstance.getUserByID(previousValue[0][0])) {
					return previousValue;
				}
				if (null !== dlmReportsInstance.getUserByID(currentValue[0][0])) {
					return currentValue;
				}

				return [];

			}, []);
		}
		return 0;
	}

	/**
	 * Get user by ID
	 * @param user_id
	 */
	getUserByID(user_id) {

		if (!user_id) {
			return null;
		}
		if ('0' === user_id) {
			return {
				role:'Guest',
				display_name:'Guest',
			};
		}

		let $user = Object.values(dlmReportsInstance.dlmUsersStats.users).filter(user => {
			return parseInt(user_id) === parseInt(user.id);
		});

		if (Array.isArray($user)) {
			if (0 === $user.length) {
				return null;
			}
			return $user[0];
		}

		return $user;
	}

	/**
	 * Get number of logged in downloads
	 * @returns {number}
	 */
	getLoggedInDownloads() {

		if (Object.values(dlmReportsInstance.userRelatedData).length) {
			if (Object.values(dlmReportsInstance.userRelatedData).length > 1) {
				return Object.values(dlmReportsInstance.userRelatedData).reduce((previousValue, currentValue) => {
					return parseInt(previousValue) + parseInt(currentValue.length);
				}, 0);
			} else {
				return Object.values(dlmReportsInstance.userRelatedData)[0].length;
			}

		}
		return 0;
	}

	/**
	 * Set total logged in stats
	 */
	setLoggedInDownloads() {
		const stat = dlmReportsInstance.getLoggedInDownloads();

		jQuery('.dlm-reports-block-summary li#logged_in span,#total_downloads_summary_wrapper .dlm-reports-logged-in').html(stat.toLocaleString());
	}

	/**
	 * Get logged out number
	 *
	 * @returns {number}
	 */
	getLoggedOutDownloads() {
		const all      = dlmReportsInstance.userDownloads.length;
		const loggedIn = dlmReportsInstance.getLoggedInDownloads();

		return all - loggedIn;
	}

	/**
	 * Set total logged out stats
	 */
	setLoggedOutDownloads() {
		const stat = dlmReportsInstance.getLoggedOutDownloads();

		jQuery('.dlm-reports-block-summary li#logged_out span,#total_downloads_summary_wrapper .dlm-reports-logged-out').html(stat.toLocaleString());
	}

	/**
	 * The tooltip markup for user's extra info
	 * @param user
	 */
	userToolTipMarkup(user) {

		let html = '<div class="dlm-user-reports">';
		html += '<div class="wpchill-tooltip"><i>[?]</i>';
		html += '<div class="wpchill-tooltip-content">';

		html += '<span>User ID: ' + ((null !== user) ? user.id : '--') + '</span>';

		if ('object' !== typeof user && user.url.length) {
			html += '<span>User URL: ' + ((null !== user) ? user.url : '--') + '</span>';
		}

		html += '<span>User registration date: ' + ((null !== user) ? user.registered : '--') + '</span>';

		if (null !== user && 'undefined' !== typeof user.role && user.role.length) {
			html += '<span>User role: ' + user.role + '</span>';
		}

		html += '</div></div>';
		html += ((null !== user) ? user.display_name : '--');
		html += '</div>';

		return html;
	}

	/**
	 * Generate the user downloads table
	 */
	setUserDownloads(offset = 0, reset = false) {
		// the table
		const wrapperParent = jQuery('#users_download_log'),
			  wrapper       = jQuery('#users_download_log .user-logs__list');
		wrapper.empty();

		if (true === reset) {
			return;
		}

		let dataResponse = [];

		if (null !== dlmReportsInstance.tempDownloads) {
			dataResponse = JSON.parse(JSON.stringify(dlmReportsInstance.tempDownloads)).slice(dlmReportsInstance.perPage * parseInt(offset), dlmReportsInstance.perPage * (parseInt(offset + 1)));
		} else {
			dataResponse = JSON.parse(JSON.stringify(dlmReportsInstance.userDownloads)).slice(dlmReportsInstance.perPage * parseInt(offset), dlmReportsInstance.perPage * (parseInt(offset + 1)));
		}

		for (let i = 0; i < dataResponse.length; i++) {
			const user = dlmReportsInstance.getUserByID(dataResponse[i].user_id.toString());
			let download = dlmReportsInstance.getDownloadCPT(parseInt(dataResponse[i].download_id));
			let title = '--';

			if ('undefined' === typeof download) {
				download = dlmReportsInstance.altGetDownloadCPT(dataResponse[i].download_id.toString());
				if ('undefined' !== typeof download) {
					title = download.title;
				}
			} else {
				title = download.title.rendered;
			}

			let itemObject = {
				key               : i,
				user              : ('undefined' !== typeof user && null !== user) ? user['display_name'] : '--',
				ip                : dataResponse[i].user_ip,
				role              : (null !== user && null !== user.role ? user.role : '--'),
				download          : ('undefined' !== typeof download && null !== download) ? dlmReportsInstance.htmlEntities(title) : '--',
				valid_user        : ('0' !== dataResponse[i].user_id),
				edit_link         : ( '0' !== dataResponse[i].user_id) ? 'user-edit.php?user_id=' + dataResponse[i].user_id : '#',
				edit_download_link: ('undefined' !== typeof download && null !== download) ? dlmAdminUrl + 'post.php?post=' + download.id + '&action=edit' : '#',
				status            : ( 'redirect' === dataResponse[i].download_status ) ? 'redirected' : dataResponse[i].download_status,
				download_date     : dataResponse[i].display_date,
			}

			jQuery(document).trigger('dlm_reports_user_logs_item_before_render', [itemObject, dlmReportsInstance, dataResponse[i], user, download]);

			let item = new dlmBackBone['modelUserLogs'](itemObject);
		}

		dlmReportsInstance.stopSpinner(jQuery('#users_download_log'));
		// Set the total number of downloads pages
		wrapperParent.find('.dlm-reports-total-pages').html(Math.ceil(dlmReportsInstance.tempDownloads.length / dlmReportsInstance.perPage));

		if (parseInt(dlmReportsInstance.perPage) !== parseInt(dataResponse.length)) {
			wrapperParent.find('.user-downloads-block-navigation button[data-action="load-more"]').attr('disabled', 'disabled');
		} else {
			wrapperParent.find('.user-downloads-block-navigation button[data-action="load-more"]').removeAttr('disabled');
		}

		if (dlmReportsInstance.userDownloads.length > dlmReportsInstance.perPage) {
			wrapperParent.find('.user-downloads-block-navigation button').removeClass('hidden');
		} else {
			wrapperParent.find('.user-downloads-block-navigation button').addClass('hidden');
		}
	}

	/**
	 * Filter our downloads
	 */
	filterDownloads() {

		dlmReportsInstance.tempDownloads = JSON.parse(JSON.stringify(dlmReportsInstance.userDownloads));
		if (!dlmReportsInstance.currentFilters.length) {
			dlmReportsInstance.setUserDownloads();
			return;
		}

		dlmReportsInstance.currentFilters.forEach((filter) => {

			dlmReportsInstance.tempDownloads = dlmReportsInstance.tempDownloads.filter((element) => {
				let currFilter = filter.on;
				if ( 'redirected' === filter.on ) {
					return filter.on === element[filter.type] || 'redirect' === element[filter.type];
				}
				return filter.on === element[filter.type];

			});
		});
		dlmReportsInstance.setUserDownloads();
	}

	/**
	 * Let's create the slider navigation. Will be based on offset
	 */
	handleUserDownloads() {

		jQuery('#users_download_log').on('click', '.user-downloads-block-navigation button', function (e) {
			e.stopPropagation();

			let main_parent  = jQuery(this).parents('#users_downloads_table_wrapper'),
				offsetHolder = main_parent.find('#users_download_log'),
				offset       = offsetHolder.attr('data-page'),
				link         = jQuery(this),
				nextPage     = parseInt(offset) + 1,
				prevPage     = (0 !== offset) ? parseInt(offset) - 1 : 0,
				prevButton   = main_parent.find('.downloads-block-navigation button').first(),
				nextButton   = main_parent.find('.downloads-block-navigation button').last();

			link.attr('disabled', 'disabled');

			const handleObj = {
				data    : dlmReportsInstance.tempDownloads,
				main_parent,
				offsetHolder,
				offset,
				link,
				nextPage,
				prevPage,
				prevButton,
				nextButton,
				doAction: dlmReportsInstance.setUserDownloads

			}
			dlmReportsInstance.handleSliderNavigation(handleObj);
		});

		jQuery('#users_downloads_table_wrapper').find('input.dlm-reports-current-page').on('change', function () {
			dlmReportsInstance.paginationChange(jQuery(this), dlmReportsInstance.tempDownloads, jQuery('#users_downloads_table_wrapper'), jQuery('#users_downloads_table_wrapper').find('#users_download_log'), dlmReportsInstance.setUserDownloads);
		});
	}

	/**
	 * Page settings area show/hide
	 */
	togglePageSettings() {
		jQuery('#dlm-toggle-settings').on('click', function (e) {
			e.stopPropagation();
			jQuery(this).find('.dlm-toggle-settings__settings').toggleClass('display');
		});
		jQuery('.dlm-toggle-settings__settings').on('click', function (e) {
			e.stopPropagation();
		});
		jQuery('html,body').on('click', function () {
			jQuery(this).find('.dlm-toggle-settings__settings').removeClass('display');
		});

		jQuery(document).on('change', '.wpchill-toggle__input', function (e) {
			const $this = jQuery(this), name = $this.attr('name'), data = {
				action     : 'dlm_update_report_setting',
				name       : name,
				checked    : $this.is(':checked'),
				_ajax_nonce: dlmReportsNonce
			};

			jQuery.post(ajaxurl, data, function (response) {
				switch (name) {
					default:
						jQuery(document).trigger('dlm_settings_ajax_response', [
							dlmReportsInstance,
							$this,
							response
						]);
						break;
				}
			});
		});
	}

	/**
	 * Get maximum downloaded times in one tick from chart
	 * @returns {number}
	 */
	getMaxDownload() {
		let max = 0;
		dlmReportsInstance.dataSets.forEach((element) => {
			let i = Object.values(element.data).reduce((prev, curr) => {
				if (prev > curr) {
					return prev;
				}
				return curr;
			}, 0);
			if (max < i) {
				max = i;
			}
		});

		return parseInt(max);
	}

	/**
	 * Set display date for the Chart Tooltip
	 *
	 * @param dateInput
	 * @param plugin
	 * @param dataSet
	 * @returns {*}
	 */
	setChartTooltipDate(dateInput, plugin, dataSet) {
		let date = '';
		if ('undefined' !== plugin.chartType && 'months' === plugin.chartType) {

			const year         = moment(dateInput).year();
			const month        = moment(dateInput).month();
			const lastDate     = Object.keys(dataSet)[Object.keys(dataSet).length - 1];
			const prevLastDate = moment(lastDate).month(moment(lastDate).month() - 1).format("YYYY-MM");
			const dateString   = moment(dateInput).format("YYYY-MM");

			if (11 > month) {

				if (dateString === prevLastDate) {

					date = moment(dateString).format("MMMM, YYYY");
				} else {

					date = moment(dateInput).format("MMM") + ' - ' + moment(dateInput).month(month + 1).format("MMM") + moment(dateInput).format(", YYYY");
				}

			} else {

				if (dateString === prevLastDate || dateString === lastDate) {

					date = moment(dateString).format("MMMM, YYYY");
				} else {

					date = moment(dateInput).format("MMM") + moment(dateInput).format(" YYYY") + ' - ' + moment(dateInput).month(month + 1).format("MMM") + moment(dateInput).month(month + 1).format(", YYYY");
				}

			}

		} else if ('undefined' !== plugin.chartType && 'days' === plugin.chartType) {

			const year         = moment(dateInput).year();
			const day          = moment(dateInput).day();
			const dayMonth     = moment(dateInput).format("MMMM");
			const nextDayMonth = moment(dateInput).day(day + 1).format("MMMM");
			const lastDate     = dlmReportsInstance.dates.downloads.end_date;
			const prevLastDate = moment(lastDate).day(moment(lastDate).day() - 1).format("MMMM Do");

			if (moment(dateInput).format("MMMM Do") === moment(lastDate).format("MMMM Do") || moment(dateInput).format("MMMM Do") === prevLastDate) {
				date = moment(dateInput).format("MMMM Do, YYYY");
			} else {
				if (dayMonth === nextDayMonth) {
					date = moment(dateInput).format("MMMM Do") + ' - ' + moment(dateInput).day(day + 1).format("Do") + moment(dateInput).format(", YYYY");
				} else {
					date = moment(dateInput).format("MMM Do") + ' - ' + moment(dateInput).day(day + 1).format("MMM Do") + moment(dateInput).format(", YYYY");
				}
			}

		} else {
			date = moment(dateInput).format("MMMM Do, YY");
		}

		return date;
	}

	/**
	 * Close all date pickers except the targeted one
	 *
	 * @param target
	 */
	closeDatePickers(target) {
		jQuery('.dlm-reports-header-date-selector').not(target).each(function () {
			const opener = {target: jQuery(this).attr('id')};
			dlmReportsInstance.hideDatepicker(jQuery(this), opener);
		})
	}

	/**
	 * Return short number in the form of 1K, 22K ....
	 *
	 * @param $number
	 * @returns {string}
	 */
	shortNumber($number) {

		if ('string' === typeof $number) {
			$number = $number.replace(/,/gi, '');
		} else {
			$number = parseInt($number).toString();
		}

		if ($number.length >= 4) {
			$number = parseInt($number.substring(0, $number.length - 3)).toLocaleString() + 'k';
		}

		return $number;
	}

	/**
	 * Get download object based on ID
	 * @param $id
	 * @returns {{total: number}}
	 */
	getDownloadByID($id) {

		let download = {
			total     : 0,
		},
		currentItem = {};
		dlmReportsInstance.tempDownloads.forEach(
			function (item) {
				if ($id === item.download_id) {
					currentItem = item;
					download.total = download.total + 1;

					jQuery(document).trigger('dlm_download_by_id', [dlmReportsInstance, download, currentItem]);
				}
			}
		);

		return download;
	}

	/**
	 * Get download object based on ID
	 * @param $id
	 * @returns {{id: number, title: string}}
	 */
	getDownloadCPT($id) {
		let download = null;

		if (Array.isArray(dlmReportsInstance.downloads)) {
			download = dlmReportsInstance.downloads.filter((item) => {
				return parseInt( item.id ) === parseInt( $id );
			}, 0)[0];
		}

		jQuery(document).trigger('dlm_download_cpt', [dlmReportsInstance, download]);

		return download;
	}

	/**
	 * Set loading spinner
	 * @param target
	 */
	setSpinner(target) {
		let spinnerHTML = '<div class="dlm-reports-spinner"><span></span></div>';
		target.append(spinnerHTML);
	}

	/**
	 * Remove loading spinner
	 * @param target
	 */
	stopSpinner(target) {
		target.find('.dlm-reports-spinner').remove();
	}
	eventsFunctions() {
		jQuery('body').on('click', '.total_downloads_table_filters_total_downloads > a', function (e) {
			e.preventDefault();
			if ('count' === dlmReportsInstance.topDownloadsOrder) {
				jQuery(this).parent().find('span.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
			} else {
				jQuery(this).parent().find('span.dashicons').removeClass().addClass('dashicons dashicons-arrow-down');
			}
			dlmReportsInstance.orderOverviewItemsByTotal();
		});

		jQuery('body').on('click', '.total_downloads_table_filters_title > a', function (e) {
			e.preventDefault();
			if ('title' === dlmReportsInstance.topDownloadsOrder) {
				jQuery(this).parent().find('span.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
			} else {
				jQuery(this).parent().find('span.dashicons').removeClass().addClass('dashicons dashicons-arrow-up');
			}
			dlmReportsInstance.orderOverviewItemsByTitle();
		});

		jQuery('body').on('click', '.total_downloads_table_filters_download_date > a', function (e) {
			e.preventDefault();
			jQuery(this).parent().find('span.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
			dlmReportsInstance.orderUserReportsItemsByDate();
		});

		jQuery('body').on('change', 'select.dlm-reports-per-page', function (e) {
			dlmReportsInstance.perPage = jQuery(this).val();
			dlmReportsInstance.setTopDownloads();
			dlmReportsInstance.setUserDownloads();
			jQuery.post(
				ajaxurl,
				{
					action     : 'dlm_update_report_setting',
					name       : 'dlm-reports-per-page',
					value      : dlmReportsInstance.perPage,
					_ajax_nonce: dlmReportsNonce
				}, function (response) {
				}
			);
		});
	}
	/**
	 * Set order of items
	 *
	 * @since 4.6.1
	 */
	orderItems(items, order, key, setUploads = false) {

		items.sort((a, b) => {
			switch (order) {
				case 'asc':
					return a[key] - b[key];
				case 'desc':
					return b[key] - a[key];
				default:
					return b[key] - a[key];
			}
		});

		return items;
	}
	/**
	 * Revert the current set order of the Overview tab Top Downloads table
	 *
	 * @since 4.6.1
	 */
	orderOverviewItemsByTotal(){
		if ('count' !== dlmReportsInstance.topDownloadsOrder) {
			dlmReportsInstance.topDownloadsOrder = 'count';
			dlmReportsInstance.mostDownloaded = dlmReportsInstance.orderItems(dlmReportsInstance.mostDownloaded, 'desc', 'downloads');
		} else {
			dlmReportsInstance.mostDownloaded = dlmReportsInstance.mostDownloaded.reverse();
		}

		dlmReportsInstance.setTopDownloads();
	}
	/**
	 * Revert the current set order of the User Reports tab Logs tabel
	 *
	 * @since 4.6.1
	 */
	orderUserReportsItemsByDate(){
		dlmReportsInstance.tempDownloads = dlmReportsInstance.tempDownloads.reverse();
		dlmReportsInstance.setUserDownloads();
	}
	/**
	 * Revert the current set order of the Overview tab Top Downloads table
	 *
	 * @since 4.6.1
	 */
	orderOverviewItemsByTitle(){
		if ('title' !== dlmReportsInstance.topDownloadsOrder) {
			dlmReportsInstance.topDownloadsOrder = 'title';
			dlmReportsInstance.mostDownloaded.sort(function (a, b) {
				const downloadA = dlmReportsInstance.getDownloadCPT(a.id),
					  downloadB = dlmReportsInstance.getDownloadCPT(b.id),
					  aTitle    = ('undefined' !== typeof downloadA) ? dlmReportsInstance.getDownloadCPT(a.id).title.rendered.toLowerCase() : dlmReportsInstance.altGetDownloadCPT(a.id)['title'],
					  bTitle    = ('undefined' !== typeof downloadB) ? dlmReportsInstance.getDownloadCPT(b.id).title.rendered.toLowerCase() : dlmReportsInstance.altGetDownloadCPT(b.id)['title'];

				if (aTitle < bTitle) {
					return -1;
				}
				if (aTitle > bTitle) {
					return 1;
				}
				return 0;
			});
		} else {
			dlmReportsInstance.mostDownloaded = dlmReportsInstance.mostDownloaded.reverse();
		}

		dlmReportsInstance.setTopDownloads();
	}
	/**
	 * Pagination changing using the input type number
	 *
	 * @param input
	 * @param data
	 * @param main_parent
	 * @param offsetHolder
	 * @param action
	 */
	paginationChange( input, data, main_parent, offsetHolder, action ){

		let offset  = parseInt(input.val());

		if (0 === offset) {
			offset = 1;
		}

		if (data.length < (offset * dlmReportsInstance.perPage)) {
			offset = Math.ceil(data.length / dlmReportsInstance.perPage);
		}

		let link         = jQuery(this).next('button[data-action="load-more"]'),
			nextPage     = offset + 1,
			prevPage     = offset - 1,
			prevButton   = main_parent.find('.downloads-block-navigation button').first(),
			nextButton   = main_parent.find('.downloads-block-navigation button').last();

		link.attr('disabled', 'disabled');

		const handleObj = {
			data    : data,
			main_parent,
			offsetHolder,
			offset,
			link,
			nextPage,
			prevPage,
			prevButton,
			nextButton,
			doAction: action
		}

		dlmReportsInstance.handleSliderNavigation(handleObj);
	}
	/**
	 * HTML entities for Download's title
	 *
	 * @param string
	 * @returns {string}
	 */
	htmlEntities(string) {
		var textarea = document.getElementById("dlm_reports_decode_area");
		textarea.innerHTML = string;
		return textarea.value;

	}
	/**
	 * Get existing Downloads
	 *
	 * @returns {Promise<void>}
	 */
	async fetchDownloadsCPT() {
		const fetchedUserData = await fetch(dlmDownloadsCptApiapi);

		if (!fetchedUserData.ok) {
			throw new Error('Something went wrong! Reports response did not come OK - ' + fetchedUserData.statusText);
		}

		dlmReportsInstance.downloads = await fetchedUserData.json();

		// Fetch our users and the logs. Do this first so that we query for users we have data.
		dlmReportsInstance.fetchUserData();
	}
	/**
	 * Get download object based on ID
	 * @param $id
	 * @returns {{total: number}}
	 */
	altGetDownloadCPT($id) {

		let download = null;
		if (Array.isArray(dlmReportsInstance.mostDownloaded)) {
			download = dlmReportsInstance.mostDownloaded.filter((item) => {
				return item.id === $id;
			}, 0)[0];
		}

		jQuery(document).trigger('dlm_download_cpt', [dlmReportsInstance, download]);

		return download;
	}
}
