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
				color: '#27ae60',
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
					},
					normalized: true,
					parsing: {
						xAxisKey: 'x',
						yAxisKey: 'y'
					},
					plugins: {
						legend: {
							display: false,
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
	}

	/**
	 * Our download summary based on the selected date range.
	 * 
	 * 
	 * @returns 
	 */
	dlmDownloadsSummary() {

		let mostDownloaded = {};
		let totalDownloads = 0;

		if (false === this.stats || false === this.stats.summaryStats || Object.keys(this.stats.summaryStats).length <= 0) {

			this.setTotalDownloads(0);
			this.setDailyAverage(0);
			this.setMostDownloaded('--');
			this.setTopDownloads(0, true);
			return;
		}

		// Lets prepare the items based on item id and not date so that we can get the most downloaded item
		this.stats.summaryStats.forEach((itemSet) => {

			itemSet = JSON.parse(itemSet.download_ids);

			Object.entries(itemSet).forEach(([key, item]) => {
				totalDownloads += item.downloads;
				mostDownloaded[key] = ('undefined' === typeof mostDownloaded[key]) ? {
					downloads: item.downloads,
					title: item.title,
					id: key
				} : {
					downloads: mostDownloaded[key]['downloads'] + item.downloads,
					title: item.title,
					id: key
				};
			});
		});

		this.mostDownloaded = Object.values(mostDownloaded).sort((a, b) => {
			return a.downloads - b.downloads;
		}).reverse();

		this.setTotalDownloads(totalDownloads);
		this.setDailyAverage((totalDownloads / parseInt(this.stats.daysLength)).toFixed(2));
		this.setMostDownloaded(this.mostDownloaded[0].title);
		this.setTopDownloads();
	}

	/**
	 * Create our date picker
	 * 
	 * 
	 * @returns 
	 */
	createDatepicker() {

		const today = new Date();
		let dd = today.getDate() - 1;
		let mm = today.getMonth() + 1; //January is 0!
		let mmm = mm - 1;
		const yyyy = today.getFullYear();

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


		var el = jQuery('<div>').addClass('dlm_rdrs_overlay');
		var startDate = jQuery('<div>').attr('id', 'dlm_date_range_picker');
		this.startDateInput = jQuery('<input>').attr('type', 'hidden').attr('id', 'dlm_start_date').attr('value', lastMonth);
		this.endDateInput = jQuery('<input>').attr('type', 'hidden').attr('id', 'dlm_end_date').attr('value', yesterday);

		el.append(startDate).append(this.startDateInput).append(this.endDateInput);

		return el;
	}

	/**
	 * Display our date picker.
	 * 
	 * 
	 * @returns 
	 */
	displayDatepicker() {

		if (this.datePicker.opened) {
			return;
		}

		this.datePicker.opened = true;
		let element = this.createDatepicker();

		const calendar_start_date = (Object.keys(dlmReportsStats).length > 0) ? new Date(dlmReportsStats[0].date) : new Date();
		const currDate = new Date();

		jQuery(this.datePickerContainer).append(element);

		const datepickerShortcuts = [];

		// Let's add shortcuts to our datepicker only if they can be managed/downloads can be viewed based on them.
		if (calendar_start_date.getTime() !== currDate.getTime()) {

			let sevenDays = new Date(),
				lastMonth = moment().month(currDate.getMonth() - 1).startOf('month')._d,
				thisMonth = new Date(currDate.getFullYear(), currDate.getMonth(), 1),
				thisYear = moment().startOf('year')._d,
				lastYear = moment().year(currDate.getFullYear() - 1).month(0).startOf('month')._d;

			sevenDays = sevenDays.setDate(sevenDays.getDate() - 6);

			if (calendar_start_date.getTime() < sevenDays) {
				datepickerShortcuts.push({
					name: 'Last 7 Days',
					dates: function () {
						return [new Date(currDate.getFullYear(), currDate.getMonth(), currDate.getDate() - 7), new Date(currDate.getFullYear(), currDate.getMonth(), currDate.getDate())];
					}
				});
			}

			if (calendar_start_date.getTime() < thisMonth) {

				datepickerShortcuts.push({
					name: 'This month',
					dates: function () {
						return [new Date(currDate.getFullYear(), currDate.getMonth(), 1), currDate];
					},
				});
			}

			if (calendar_start_date.getTime() < lastMonth.getTime()) {
				datepickerShortcuts.push({
					name: 'Last month',
					dates: function () {

						let start = lastMonth;
						let end = moment().month(currDate.getMonth() - 1).endOf('month')._d;

						if (0 === currDate.getMonth()) {
							start = moment().year(currDate.getFullYear() - 1).month(11).startOf('month')._d;
							end = moment().year(currDate.getFullYear() - 1).month(11).endOf('month')._d;
						}

						return [start, end];
					}
				});
			}

			if (calendar_start_date.getTime() < thisYear.getTime()) {
				datepickerShortcuts.push({
					name: 'This Year',
					dates: function () {

						const start = moment().startOf('year')._d;

						if (start.getTime() < calendar_start_date.getTime()) {
							return [calendar_start_date, currDate];
						}

						return [start, currDate];
					}
				});
			}

			if (calendar_start_date.getTime() < lastYear.getTime()) {
				datepickerShortcuts.push({
					name: 'Last Year',
					dates: function () {

						const start = moment().year(currDate.getFullYear() - 1).month(0).startOf('month')._d;
						const end = moment().year(currDate.getFullYear() - 1).month(11).endOf('month')._d;


						return [start, end];
					}
				});
			}
		}


		datepickerShortcuts.push({
			name: 'All time',
			dates: function () {

				return [calendar_start_date, currDate];
			}
		});

		var configObject = {
			separator: ' to ',
			autoClose: true,
			setValue: function (s, s1, s2) {
				element.find('#dlm_start_date').val(s1);
				element.find('#dlm_end_date').val(s2);
			},
			inline: true,
			alwaysOpen: true,
			container: '#dlm_date_range_picker',
			// End date should be current date
			endDate: new Date(),
			// Start date should be the first info we get about downloads
			startDate: calendar_start_date,
			showShortcuts: true,
			shortcuts: null,
			customShortcuts: datepickerShortcuts,
		};

		element.dateRangePicker(configObject).on('datepicker-change', (event, obj) => {

			if (obj.date1 && obj.date2) {

				const date_s = obj.date1.toLocaleDateString(undefined, {
					year: 'numeric',
					month: 'short',
					day: '2-digit'
				});

				const date_e = obj.date2.toLocaleDateString(undefined, {
					year: 'numeric',
					month: 'short',
					day: '2-digit'
				});

				element.parent().find('span.date-range-info').text(date_s + ' to ' + date_e);
			}

			// Recreate the stats
			this.createDataOnDate(obj.date1, obj.date2);

			this.dlmCreateChart(this.stats.chartStats, this.chartContainer);

			this.dlmDownloadsSummary();

			element.data('dateRangePicker').close();
		});

	}

	/**
	 * Hide the datepicker.
	 */
	hideDatepicker() {
		this.datePicker.opened = false;
		jQuery(this.datePickerContainer).find('#dlm_date_range_picker').remove();
	}

	/**
	 * Toggle the date picker on/off.
	 */
	toggleDatepicker(event) {

		event.stopPropagation();
		if (this.datePicker.opened) {
			this.hideDatepicker();
		} else {
			this.displayDatepicker();
		}
	}

	/**
	 * Set total downloads based on the selected date range.
	 * 
	 * 
	 * @param {*} totalDownloads 
	 */
	setTotalDownloads(totalDownloads) {
		jQuery('.dlm-reports-block-summary li#total span').html(totalDownloads);
	}

	/**
	 * Set daily average based on the selected date range.
	 * 
	 * @param {*} dailyAverage 
	 */
	setDailyAverage(dailyAverage) {
		jQuery('.dlm-reports-block-summary li#average span').html(dailyAverage);
	}

	/**
	 * Set most downloaded Download.
	 * 
	 * @param {*} mostDownloaded 
	 */
	setMostDownloaded(mostDownloaded) {
		jQuery('.dlm-reports-block-summary li#popular span').html(mostDownloaded);
	}

	/**
	 * Set today's downloads.
	 */
	setTodayDownloads() {

		let todayDownloads = 0;

		if (0 >= Object.keys(dlmReportsStats).length) {

			jQuery('.dlm-reports-block-summary li#today span').html(todayDownloads);
			return;
		}

		// We only need the last date from dlmReportsStats, as it will be the last entry from the DB in crhonological order.
		if (dlmReportsStats[dlmReportsStats.length - 1].date === this.createDateElement(new Date())) {

			todayDownloads = Object.values(JSON.parse(dlmReportsStats[dlmReportsStats.length - 1].download_ids)).reduce((prevValue, element) => {

				return prevValue + element.downloads;
			}, 0);

		}

		jQuery('.dlm-reports-block-summary li#today span').html(todayDownloads);

	}

	/**
	 * Set the top downloads.
	 * 
	 * @param {*} offset 
	 * @param {*} reset 
	 * @returns 
	 */
	setTopDownloads(offset = 0, reset = false) {
		// the table
		const wrapper = jQuery('#total_downloads_table');
		wrapper.empty();

		if (!this.mostDownloaded || true === reset) {
			return;
		}

		var table = jQuery(document.createElement('table'));

		table.attr('cellspacing', 0).attr('cellpadding', 0).attr('border', 0);

		// setup header row
		var headerRow = document.createElement('tr');
		const th0 = document.createElement('th');
		th0.innerHTML = "#position";
		headerRow.appendChild(th0);
		const th1 = document.createElement('th');
		th1.innerHTML = "ID";
		headerRow.appendChild(th1);
		const th2 = document.createElement('th');
		th2.innerHTML = "Title"
		headerRow.appendChild(th2);
		const th3 = document.createElement('th');
		th3.innerHTML = "Downloads";
		headerRow.appendChild(th3);

		// append header row
		table.append(headerRow);

		const table_data = JSON.parse(JSON.stringify(this.mostDownloaded)).slice(15 * parseInt(offset), 15 * (parseInt(offset + 1)));

		for (let i = 0; i < table_data.length; i++) {

			var tr = document.createElement('tr');

			for (let j = 0; j < 4; j++) {

				let td = document.createElement('td');

				if (j === 0) {
					td.innerHTML = '<span class="dlm-listing-position">' + (parseInt(15 * offset) + i + 1) + '.</span>';
				} else if (j === 1) {
					td.innerHTML = table_data[i].id;
				} else if (j === 2) {
					td.innerHTML = '<a href="' + dlm_admin_url + 'post.php?post=' + table_data[i].id + '&action=edit" target="_blank">' + table_data[i].title + ' <span class="dashicons dashicons-admin-generic"></span></a>';
				} else {
					td.innerHTML = table_data[i].downloads;
				}

				tr.appendChild(td);
			}

			// append row
			table.append(tr);
		}

		wrapper.append(table);

		wrapper.find('.dlm-reports-placeholder-no-data').remove();

		if (this.mostDownloaded.length > 15) {
			wrapper.parent().find('#downloads-block-navigation button').removeClass('hidden');
		} else {
			wrapper.parent().find('#downloads-block-navigation button').addClass('hidden');
		}

	}

	/**
	 * Let's create the top downloads slider. Will be based on offset
	 * 
	 * 
	 */
	handleTopDownloads() {

		const instance = this;

		jQuery('#downloads-block-navigation').on('click', 'button', function () {

			let main_parent = jQuery(this).parents('#total_downloads_table_wrapper'),
				offset = main_parent.find('#total_downloads_table').attr('data-page'),
				table = main_parent.find('#total_downloads_table table'),
				link = jQuery(this),
				nextPage = parseInt(offset) + 1,
				prevPage = (0 !== offset) ? parseInt(offset) - 1 : 0;

			main_parent.find('#total_downloads_table').css('height', table.height() + 30);
			link.attr('disabled', 'disabled');

			// Check if we click the next/load more button
			if ('load-more' === link.data('action')) {

				main_parent.find('#total_downloads_table').attr('data-page', nextPage);
				instance.setTopDownloads(nextPage);
				jQuery('#downloads-block-navigation').find('button').removeAttr('disabled');


			} else {

				if (0 !== parseInt(offset)) {

					//table.toggle({ effect: "scale", direction: "both" });
					main_parent.find('#total_downloads_table').attr('data-page', prevPage);
					// Remove on all buttons, as it will be at least page 2
					instance.setTopDownloads(prevPage);

					if (1 !== parseInt(offset)) {
						jQuery('#downloads-block-navigation').find('button').removeAttr('disabled');
					}

				}
			}
		});
	}

	tabNagivation() {
		jQuery(document).on('click', '.dlm-reports .dlm-insights-tab-navigation > li', function () {
			const listClicked = jQuery(this),
				navLists = jQuery('.dlm-reports .dlm-insights-tab-navigation > li').not(listClicked),
				contentTarget = jQuery('div.dlm-insights-tab-navigation__content[data-id="' + listClicked.attr('id') + '"]'),
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
	 * Init our methods
	 */
	init() {

		const instance = this;
		instance.dlmCreateChart(this.stats.chartStats, this.chartContainer);

		instance.dlmDownloadsSummary();

		instance.datePickerContainer.addEventListener('click', instance.toggleDatepicker.bind(this));

		instance.setTodayDownloads();

		instance.handleTopDownloads();

		instance.tabNagivation();

		jQuery(document).on('click', 'body', function (event) {

			event.stopPropagation();

			if (jQuery(instance.datePickerContainer).find('#dlm_date_range_picker').length > 0) {
				instance.hideDatepicker();
			}

		});
	}

	// fetch data from WP REST Api in case we want to change the direction from global js variable set by wp_add_inline_script
	async fetchData() {
		const fetchedData = await fetch(dlmReportsAPI);
		this.reportsData = await fetchedData.json();
	}

	// The external tooltip of the Chart
	getOrCreateTooltip(chart) {

		let tooltipEl = chart.canvas.parentNode.querySelector('div');

		if (!tooltipEl) {
			tooltipEl = document.createElement('div');
			tooltipEl.style.background = '#fff';
			tooltipEl.style.borderRadius = '3px';
			tooltipEl.style.border = 'solid 1px #0D217A';
			tooltipEl.style.color = '#0D217A';
			tooltipEl.style.opacity = 1;
			tooltipEl.style.pointerEvents = 'none';
			tooltipEl.style.position = 'absolute';
			tooltipEl.style.transform = 'translate(-50%, 0)';
			tooltipEl.style.transition = 'all .1s ease';
			tooltipEl.style.padding = '5px 15px';

			const table = document.createElement('table');
			table.style.margin = '0px';

			tooltipEl.appendChild(table);
			chart.canvas.parentNode.appendChild(tooltipEl);
		}

		return tooltipEl;
	}

	externalTooltipHandler(plugin, context) {
		
		// Tooltip Element
		const {
			chart,
			tooltip
		} = context;

		const tooltipEl = plugin.getOrCreateTooltip(chart);

		// Hide if no tooltip
		if (tooltip.opacity === 0) {
			tooltipEl.style.opacity = 0;
			return;
		}

		// Set Text
		if (tooltip.body) {
			const titleLines = tooltip.title || [];
			const bodyLines = tooltip.body.map(b => b.lines);

			const tableHead = document.createElement('thead');

			titleLines.forEach(title => {
				const tr = document.createElement('tr');
				tr.style.borderWidth = 0;

				const th = document.createElement('th');
				th.style.borderWidth = 0;

				// Let us create our tooltip content.
				// The main wrapper for content
				const textContent = document.createElement('div');
				textContent.style.color = 'red';

				// The title
				const downloads = document.createElement('p');
				downloads.style.color = '#0D217A';
				downloads.style.fontSize = '18px';
				downloads.style.margin = '0 auto';
				downloads.appendChild(document.createTextNode(title));

				// Info
				const downloadsInfo = document.createElement('p');
				downloadsInfo.style.color = 'rgba(0,0,0,0.6)';
				downloadsInfo.style.fontSize = '12px';
				downloadsInfo.style.margin = '0 auto';
				downloadsInfo.appendChild(document.createTextNode('Downloads'));

				// Date
				const downloadDate = document.createElement('p');
				downloadDate.style.color = '#0D217A';	
				downloadDate.style.fontSize = '13px';
				downloadDate.style.margin = '0 auto';
				const date = ('undefined' !== plugin.chartType && 'month' === plugin.chartType) ? moment(tooltip.dataPoints[0].label).format("MMMM, YYYY") : moment(tooltip.dataPoints[0].label).format("MMMM Do, YY");
				downloadDate.appendChild(document.createTextNode(date));

				// Create the whole content and append it
				textContent.appendChild(downloads).appendChild(downloadsInfo).appendChild(downloadDate);
				th.appendChild(textContent);
				tr.appendChild(th);
				tableHead.appendChild(tr);
			});

			const tableBody = document.createElement('tbody');
			bodyLines.forEach((body, i) => {
				const colors = tooltip.labelColors[i];

				const span = document.createElement('span');
				span.style.background = colors.backgroundColor;
				span.style.borderColor = colors.borderColor;
				span.style.borderWidth = '2px';
				span.style.marginRight = '10px';
				span.style.height = '10px';
				span.style.width = '10px';
				span.style.display = 'inline-block';

				const tr = document.createElement('tr');
				tr.style.backgroundColor = 'inherit';
				tr.style.borderWidth = 0;

				const td = document.createElement('td');
				td.style.borderWidth = 0;

				const text = document.createTextNode(body);

				td.appendChild(span);
				td.appendChild(text);
				tableBody.appendChild(tr);
			});

			const tableRoot = tooltipEl.querySelector('table');

			// Remove old children
			while (tableRoot.firstChild) {
				tableRoot.firstChild.remove();
			}

			// Add new children
			tableRoot.appendChild(tableHead);
			tableRoot.appendChild(tableBody);
		}

		const {
			offsetLeft: positionX,
			offsetTop: positionY
		} = chart.canvas;

		// Display, position, and set styles for font
		tooltipEl.style.opacity = 1;
		tooltipEl.style.left = positionX + tooltip.caretX + 'px';
		tooltipEl.style.top = (positionY + tooltip.caretY - tooltipEl.offsetHeight - 10) + 'px';
		tooltipEl.style.font = tooltip.options.bodyFont.string;
		tooltipEl.style.padding = tooltip.options.padding + 'px ' + tooltip.options.padding + 'px';
	}
}