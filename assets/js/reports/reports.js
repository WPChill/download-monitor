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
				default: "rgba(67, 56, 202, 1)",
				half: "rgba(67, 56, 202, 0.75)",
				quarter: "rgba(67, 56, 202, 0.5)",
				zero: "rgba(67, 56, 202, 0.05)"
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
	getDoubleMonths(days) {

		const dates = [],
			firstDay = Object.keys(days)[0],
			lastDay = Object.keys(days)[Object.keys(days).length - 1];

		let i = 0,
			month = firstDay.substring(0, 7),
			lastMonth = lastDay.substring(0, 7);

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

		return date.getFullYear() + '-' + MM + '-' + ("0" + date.getDate()).slice(-2);
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
			yearDiff,
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
		yearDiff = moment(endDate, 'YYYY-MM-DD').year() - moment(startDate, 'YYYY-MM-DD').year();

		if (yearDiff == 0 && monthDiff > -6 && monthDiff < 6) {

			if (monthDiff > 1 || monthDiff < -1) {
				instance.chartType = 'weeks';
			} else {
				instance.chartType = 'day';
			}

		} else {
			if (yearDiff = 1 && monthDiff <= 0) {
				instance.chartType = 'month';
			} else {
				instance.chartType = 'months';
			}
		}

		// Get all dates from the startDate to the endDate
		let dayDownloads = instance.getDates(new Date(startDate), new Date(endDate));
		// Get selected months
		let monthDownloads = instance.getMonths(dayDownloads);
		// Get double selected months
		let doubleMonthDownloads = instance.getDoubleMonths(dayDownloads);
		// Get selected dates in 2 weeks grouping
		let weeksDownloads = instance.getWeeks(dayDownloads);

		Object.values(instance.reportsData).forEach((day, index) => {

			const downloads = JSON.parse(day.download_ids);

			if ('undefined' !== typeof dayDownloads[day.date]) {

				switch (instance.chartType) {
					case 'months':
						chartDate = day.date.substring(0, 7);
						const chartMonth = parseInt(day.date.substring(5, 7)),
							chartYear = day.date.substring(0, 5),
							prevDate = (chartMonth - 1).length > 6 ? chartYear + (chartMonth - 1) : chartYear + '0' + (chartMonth - 1);

						Object.values(downloads).forEach((item, index) => {

							// If it does not exist we attach the downloads to the previous month
							if ('undefined' === typeof doubleMonthDownloads[chartDate]) {

								if ('undefined' !== typeof doubleMonthDownloads[prevDate]) {
									doubleMonthDownloads[prevDate] = doubleMonthDownloads[prevDate] + item.downloads;
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
				pointBorderWidth: 1,
				lineTension: 0.3,
				borderWidth: 1,
				pointRadius: 3,
				elements: {
					line: {
						borderColor: '#2ecc71',
						borderWidth: 1,
					},
					point: {
						radius: 4,
						hoverRadius: 4,
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
									let date = '';
									const dateString = Object.keys(data)[val];

									if ('undefined' !== instance.chartType && 'months' === instance.chartType) {

										const month = moment(Object.keys(data)[val]).month();

										if (11 > month) {
											date = moment(dateString).format("MMM") + ' - ' + moment(dateString).month(month + 1).format("MMM") + moment(dateString).format(", YYYY");
										} else {
											date = moment(dateString).format("MMM") + moment(dateString).format(" YYYY") + ' - ' + moment(dateString).month(month + 1).format("MMM") + moment(dateString).month(month + 1).format(", YYYY");
										}

									} else if ('undefined' !== instance.chartType && 'months' === instance.chartType) {

										date = moment(dateString).format("MMMM, YYYY");
									} else {

										date = moment(dateString).format("D MMM");
									}

									return date;
								}
							},
						},
						y: {
							grid: {
								drawBorder: false,
							}
						}
					},
					normalized: true,
					parsing: {
						xAxisKey: 'x',
						yAxisKey: 'y'
					},
					plugins: {
						tooltip: {
							enabled: false,
							external: instance.externalTooltipHandler.bind(instance, this),
						},
						// When we'll add more datasets we'll display the legend
						legend: {
							display: false
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
		let totalDownloads = 0;
		const instance = this;

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
		this.setDailyAverage((totalDownloads / parseInt(this.stats.daysLength)).toFixed(0));
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
		jQuery('.dlm-reports-block-summary li#popular span').html(mostDownloaded); // this is a string
	}

	/**
	 * Set today's downloads.
	 */
	setTodayDownloads() {

		let todayDownloads = 0;

		if (0 >= Object.keys(dlmReportsStats).length) {

			jQuery('.dlm-reports-block-summary li#today span').html(todayDownloads.toLocaleString());
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
		const instance = this;
		const wrapper = jQuery('#total_downloads_table');
		wrapper.empty();
		wrapper.parent().addClass('empty');

		if (!this.mostDownloaded || true === reset) {
			return;
		}

		let dataWrapper = document.createElement('div');
		dataWrapper.className = "dlm-reports-top-downloads";

		// Setup header row
		let headerRow = document.createElement('div');
		headerRow.className = "dlm-reports-top-downloads__header";

		// Create position row
		/*
		const posRow = document.createElement('div');
		const posRowLabel = document.createElement('label');
		posRowLabel.appendChild(document.createTextNode("#position"));
		posRow.appendChild(posRowLabel);
		headerRow.appendChild(posRow);
		*/

		// Create title row
		const titleRow = document.createElement('div');
		titleRow.className = 'dlm-reports-header-left';
		const titleRowLabel = document.createElement('label');
		titleRowLabel.appendChild(document.createTextNode("Title"));
		titleRow.appendChild(titleRowLabel);
		headerRow.appendChild(titleRow);

		// Create downloads row
		const downloadsRow = document.createElement('div');
		downloadsRow.className = 'dlm-reports-header-right';
		const downloadsRowLabel = document.createElement('label');
		downloadsRowLabel.appendChild(document.createTextNode("Downloads"));
		downloadsRow.appendChild(downloadsRowLabel);
		headerRow.appendChild(downloadsRow);

		// Append header row
		dataWrapper.append(headerRow);

		const dataResponse = JSON.parse(JSON.stringify(this.mostDownloaded)).slice(15 * parseInt(offset), 15 * (parseInt(offset + 1)));

		for (let i = 0; i < dataResponse.length; i++) {

			const line = document.createElement('div');
			line.className = "dlm-reports-top-downloads__line";
			const size = dataResponse[i].downloads * 100 / dataResponse[0].downloads;
			let overFlower  = document.createElement('span');
			overFlower.className = 'dlm-reports-top-downloads__overflower';
			overFlower.style.width = parseInt(size)+'%';	

			for (let j = 0; j < 3; j++) {
				

				let lineSection = document.createElement('div');
				lineSection.setAttribute('data-id', j); // we will need this to style each div based on its position in the "table"

				if (j === 0) {
					lineSection.innerHTML = '<span class="dlm-listing-position">' + (parseInt(15 * offset) + i + 1) + '.</span>';
				} else if (j === 1) {
					
					lineSection.innerHTML = '<a href="' + dlm_admin_url + 'post.php?post=' + dataResponse[i].id + '&action=edit" title="Click to edit download: ' + dataResponse[i].title  + '" target="_blank">' + dataResponse[i].title + '</a>';
					lineSection.prepend(overFlower);
					
				} else {
					lineSection.innerHTML = dataResponse[i].downloads.toLocaleString();
				}

				line.appendChild(lineSection);
			}
			
			

			// append row
			dataWrapper.append(line);
		}

		const htmlTarget = wrapper.parent().find('.dlm-reports-placeholder-no-data');
		wrapper.parent().removeClass('empty');
		wrapper.remove(htmlTarget).append(dataWrapper);

		// @todo: you need to hide the parent, not the actual buttons here, otherwise we're going to be left with a div that takes up vertical space but doesn't show
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
			tooltipEl.className = "dlm-canvas-tooltip";

			const tooltipWrapper = document.createElement('div');
			tooltipWrapper.className = "dlm-reports-tooltip";

			tooltipEl.appendChild(tooltipWrapper);
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
		const tooltipWidth = jQuery(tooltipEl).parent().width();

		// Hide if no tooltip
		if (tooltip.opacity === 0) {
			tooltipEl.style.opacity = 0;
			return;
		}

		// Set Text
		if (tooltip.body) {
			const titleLines = tooltip.title || [];

			const tooltipContent = document.createElement('div');
			tooltipContent.className = "dlm-reports-tooltip__header";

			titleLines.forEach(title => {
				const tooltipRow = document.createElement('div');
				tooltipRow.className = "dlm-reports-tooltip__row";

				// The title
				const downloads = document.createElement('p');
				downloads.className = "dlm-reports-tooltip__downloads";
				downloads.appendChild(document.createTextNode(tooltip.dataPoints[0].formattedValue));
				tooltipRow.appendChild(downloads);

				// Info
				const downloadsInfo = document.createElement('p');
				downloadsInfo.className = "dlm-reports-tooltip__info";
				downloadsInfo.appendChild(document.createTextNode('Downloads'));
				tooltipRow.appendChild(downloadsInfo);

				// Date
				const downloadDate = document.createElement('p');
				downloadDate.className = "dlm-reports-tooltip__date";

				let date = '';

				if ('undefined' !== plugin.chartType && 'months' === plugin.chartType) {

					const year = moment(tooltip.dataPoints[0].label).year();
					const month = moment(tooltip.dataPoints[0].label).month();

					if (11 > month) {
						date = moment(tooltip.dataPoints[0].label).format("MMMM") + ' - ' + moment(tooltip.dataPoints[0].label).month(month + 1).format("MMMM") + moment(tooltip.dataPoints[0].label).format(", YYYY");
					} else {
						date = moment(tooltip.dataPoints[0].label).format("MMMM") + moment(tooltip.dataPoints[0].label).format(" YYYY") + ' - ' + moment(tooltip.dataPoints[0].label).month(month + 1).format("MMMM") + moment(tooltip.dataPoints[0].label).month(month + 1).format(", YYYY");
					}

				} else if ('undefined' !== plugin.chartType && 'months' === plugin.chartType) {

					date = moment(tooltip.dataPoints[0].label).format("MMMM, YYYY");
				} else {

					date = moment(tooltip.dataPoints[0].label).format("MMMM Do, YY");
				}

				downloadDate.appendChild(document.createTextNode(date));
				tooltipRow.appendChild(downloadDate);

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
			offsetLeft: positionX,
			offsetTop: positionY
		} = chart.canvas;

		// Display, position, and set styles for font
		tooltipEl.style.opacity = 1;
		let margin = {
			isMargin: false,
			left: false
		};

		if (tooltip.caretX - tooltip.width < 0) {
			margin.isMargin = true;
			margin.left = true;
		}

		if (positionX + tooltip.caretX + tooltip.width > tooltipWidth) {
			margin.isMargin = true;
			margin.left = false;
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

		tooltipEl.style.top = (positionY + tooltip.caretY - tooltipEl.offsetHeight - 10) + 'px';
	}
}