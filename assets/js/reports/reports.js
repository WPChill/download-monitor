jQuery(function ($) {
	new DLM_Reports();
});

class DLM_Reports {

	/**
	 * The constructor for our class
	 */
	constructor() {

		this.chartContainer = document.getElementById('total_downloads_chart');
		this.datePickerContainer = document.getElementById('dlm-date-range-picker');
		// We parse it so that we don't make any modifications to the actual data
		// In case we will fetch data using js and the WP REST Api the this.reportsData will be an empty Object and we'll fetch data using fetchData() function
		this.reportsData = ('undefined' !== typeof dlmReportsStats) ? JSON.parse(JSON.stringify(dlmReportsStats)) : {};
		this.mostDownloaded = false;
		this.stats = false;
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
		return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
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

		let startDate, endDate;

		if ('undefined' !== typeof startDateInput && startDateInput) {

			startDate = this.createDateElement(new Date(startDateInput));
		} else {

			const lastMonth = new Date();
			lastMonth.setDate(lastMonth.getDate() - 30);
			startDate = this.createDateElement(lastMonth);

		}

		if ('undefined' !== typeof endDateInput && endDateInput) {

			endDate = this.createDateElement(new Date(endDateInput));
		} else {

			const yesterday = new Date();
			yesterday.setDate(yesterday.getDate());
			endDate = this.createDateElement(yesterday);
		}
		// Get all dates from the startDate to the endDate
		let dayDownloads = this.getDates(new Date(startDate), new Date(endDate));
		// Get number of days, used in summary for daily average downloads
		const daysLength = Object.keys(dayDownloads).length;
		// Find the start of the donwloads object
		let start = this.reportsData.findIndex((element) => {

			let element_date = new Date(element.date);
			element_date = this.createDateElement(element_date);

			return startDate === element_date;
		});
		// Find the end of the downloads object
		let end = this.reportsData.findIndex((element) => {

			let element_date = new Date(element.date);
			element_date = this.createDateElement(element_date);
			return endDate === element_date;

		});

		if (-1 === start && -1 === end) {

			this.stats = {
				chartStats: Object.assign({}, dayDownloads),
				summaryStats: false,
				daysLength: daysLength
			};
			return;
		}


		if (-1 === start) {
			start = 0;
		}

		if (-1 === end) {
			end = this.reportsData.length;
		}

		// We slice the end + 1 in order to retrieve the latest selected date
		const data = this.reportsData.slice(start, end + 1);

		Object.values(data).forEach((day) => {

			const downloads = JSON.parse(day.download_ids);
			let dateTime = new Date(day.date);
			const date = this.createDateElement(dateTime);

			Object.values(downloads).forEach((item, index) => {

				if ('undefined' === typeof dayDownloads[date]) {
					dayDownloads[date] = item.downloads;
				} else {
					dayDownloads[date] = dayDownloads[date] + item.downloads;
				}

			});

		});

		this.stats = {
			chartStats: Object.assign({}, dayDownloads),
			summaryStats: data,
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
					elements: {
						line: {
							borderColor: '#2ecc71',
							borderWidth: 2
						},
					},
				},
				{
					label: 'Downloads',
					color: '#27ae60',
					data: data,
					type: 'bar',
				}
			];

			this.chart = new Chart(chartId, {
				title: "",
				data: {
					datasets: dataSets
				},
				height: 450,
				show_dots: 0,
				x_axis_mode: "tick",
				y_axis_mode: "span",
				is_series: 1,
				options: {
					aspectRatio: 3,
					animation: false,
					/* elements: {
						line: {
							borderColor: '#2ecc71',
							borderWidth: 2
						},
					}, */
					normalized: true,
					parsing: {
						xAxisKey: 'x',
						yAxisKey: 'y'
					},
				}
			});
		}
	}

	/**
	 * Our download summary based on the selected date range.
	 * 
	 * 
	 * @param {*} startDateInput 
	 * @param {*} endDateInput 
	 * @returns 
	 */
	dlmDownloadsSummary(startDateInput, endDateInput) {

		let mostDownloaded = {};
		let totalDownloads = 0;

		if (false === this.stats || false === this.stats.summaryStats) {

			this.setTotalDownloads(0);
			this.setDailyAverage(0);
			this.setMostDownloaded('--');
			this.setTopDownloads(0, true);
			return;
		}

		// Lets prepare the items based on item id and not date so that we can get the most downloaded item
		this.stats.summaryStats.forEach((itemSet) => {

			itemSet = JSON.parse(itemSet.download_ids);

			Object.values(itemSet).forEach((item) => {
				totalDownloads += item.downloads;
				mostDownloaded[item.id] = ('undefined' === typeof mostDownloaded[item.id]) ? {
					downloads: item.downloads,
					title: item.title,
					id: item.id
				} : {
					downloads: mostDownloaded[item.id]['downloads'] + item.downloads,
					title: item.title,
					id: item.id
				};
			});
		});

		this.mostDownloaded = Object.values(mostDownloaded).sort((a, b) => {
			return a.downloads - b.downloads;
		}).reverse();

		this.setTotalDownloads(totalDownloads);
		this.setDailyAverage(parseInt(totalDownloads / parseInt(this.stats.daysLength)));
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

		jQuery(document).on('click', el, function () {
			return false;
		});

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
		const calendar_start_date = new Date(this.reportsData[0].date);
		const currDate = new Date();

		jQuery(this.datePickerContainer).append(element);

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
			customShortcuts: [

				{
					name: 'Last 7 Days',
					dates: function () {

						return [new Date(currDate.getFullYear(), currDate.getMonth(), currDate.getDate() - 7), currDate];
					}
				},

				{
					name: 'Last 30 Days',
					dates: function () {

						return [new Date(currDate.getFullYear(), currDate.getMonth(), currDate.getDate() - 30), currDate];
					}
				},
				{
					name: 'This month',
					dates: function () {

						return [new Date(currDate.getFullYear(), currDate.getMonth(), 1), currDate];
					},
				},
				{
					name: 'Last month',
					dates: function () {

						let start = moment().month(currDate.getMonth() - 1).startOf('month')._d;
						let end = moment().month(currDate.getMonth() - 1).endOf('month')._d;

						if (0 === currDate.getMonth()) {
							start = moment().year(currDate.getFullYear() - 1).month(11).startOf('month')._d;
							end = moment().year(currDate.getFullYear() - 1).month(11).endOf('month')._d;
						}

						return [start, end];
					}
				},
				{
					name: 'This Year',
					dates: function () {

						const start = moment().startOf('year')._d;

						return [start, currDate];
					}
				},
				{
					name: 'Last Year',
					dates: function () {

						const start = moment().year(currDate.getFullYear() - 1).month(0).startOf('month')._d;
						const end = moment().year(currDate.getFullYear() - 1).month(11).endOf('month')._d;


						return [start, end];
					}
				},
				{
					name: 'All time',
					dates: function () {

						return [calendar_start_date, currDate];
					}
				},
			]
		};

		element.dateRangePicker(configObject).bind('datepicker-change', (event, obj) => {

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

			this.dlmDownloadsSummary(obj.date1, obj.date2);

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
	toggleDatepicker() {

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

		const lastDate = this.reportsData[this.reportsData.length - 1];
		let todayDownloads = 0;

		if (this.createDateElement(new Date(lastDate.date)) === this.createDateElement(new Date())) {

			Object.values(JSON.parse(lastDate.download_ids)).map(element => {

				todayDownloads += element.downloads;

				return todayDownloads;
			});

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
		th0.innerHTML = "#number";
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
					td.innerHTML = parseInt(15 * offset) + i + 1;
				} else if (j === 1) {
					td.innerHTML = table_data[i].id;
				} else if (j === 2) {
					td.innerHTML = table_data[i].title;
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

		wrapper.parent().find('#downloads-block-navigation button').removeClass('hidden');

	}

	/**
	 * Let's create the top downloads slider. Will be based on offset
	 * 
	 * 
	 * @param {*} data 
	 */
	handleTopDownloads(data) {

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

	/**
	 * Init our methods
	 */
	init() {

		this.dlmCreateChart(this.stats.chartStats, this.chartContainer);

		this.dlmDownloadsSummary(false, false);

		this.datePickerContainer.addEventListener('click', this.toggleDatepicker.bind(this));

		this.setTodayDownloads();

		this.handleTopDownloads();
	}

	// fetch data from WP REST Api in case we want to change the direction from global js variable set by wp_add_inline_script
	// need to get the fetch URL from 
	async fetchData() {
		const fetchedData = await fetch('http://localhost/dm/wp-json/download-monitor/v1/reports');
		this.reportsData = await fetchedData.json();
	}
}