(function ($) {
	"use strict";


	/**
	 * Modula Importer
	 *
	 * @type {{init: init, runAjaxs: runAjaxs, ajaxTimeout: null, counts: number, upgraderResumeOffset: number, processAjax: processAjax, ajaxRequests: [], completed: number, updateImported: updateImported, ajaxStarted: number}}
	 */
	var dlmDBUpgrader = {
		// used to set the offset of the upgrader
		counts: 0,
		// offset used in case the upgrader has been ended without completion
		upgraderResumeOffset: 0,
		completed: 0,
		ajaxRequests: [],
		ajaxStarted: 1,
		ajaxTimeout: null,
		ajax: ajaxurl,
		entries: 0,
		requestsNumber: 0,

		init: function () {



			$(document).on('click', 'button#dlm-upgrade-db,a.dlm-db-upgrade-link', function (e) {
				e.preventDefault();
				$(this).prop('disabled', true);
				$('body').find('.dlm-upgrade-db-notice').addClass('started');

				const opts = {
					url: dlmDBUpgrader.ajax,
					type: 'post',
					async: true,
					cache: false,
					dataType: 'json',
					data: {
						action: 'dlm_db_log_entries',
						nonce: dlm_upgrader.nonce,
					},
					success: function (response) {

						if ('0' !== response && '0' !== response.entries) {

							// Set our number of entries.
							dlmDBUpgrader.entries = response.entries;

							// If there is an offset, set it.
							if (undefined !== typeof response.offset && 0 !== parseInt(response.offset) ) {

								dlmDBUpgrader.upgraderResumeOffset = parseInt(response.offset);

								var opts = {
									url: dlmDBUpgrader.ajax,
									type: 'post',
									async: true,
									cache: false,
									dataType: 'json',
									data: {
										action: 'dlm_upgrade_db_clear_offset',
										nonce: dlm_upgrader.nonce,
										// We need to clear the previous upgrade offset
										offset: parseInt(response.offset),
									},
									success: function () {
										console.log('previous offset cleared');									
									}
								};

								$.ajax(opts);
			
							}

						} else {
							dlmDBUpgrader.entries = 0;
						}

						dlmDBUpgrader.processAjax();
						// Initiate the progress bar with a default value, which is > 0 if there was an offset.
						// We multiply the offset by 1000000 because 10000 is the number for entries / AJAX and 100 si the % number.
						ProgressBar.init(Math.ceil((dlmDBUpgrader.upgraderResumeOffset * 1000000) / dlmDBUpgrader.entries));
					}
				};

				$.ajax(opts);
			});

		},

		processAjax: function () {

			// Make sure that we have entries
			if (dlmDBUpgrader.entries - (dlmDBUpgrader.upgraderResumeOffset * 10000) > 0) {

				// If there are fewer entries than the set limit, 10000, we should at least make 1 AJAX request
				// So set it up to 1.
				dlmDBUpgrader.requestsNumber = (dlmDBUpgrader.entries >= 10000) ? parseInt(Math.ceil(dlmDBUpgrader.entries / 10000)) : 1;

				// If offset is present then the number of AJAX requests should be the diff between all the requests and the offset
				for (let i = 0; i <= dlmDBUpgrader.requestsNumber - dlmDBUpgrader.upgraderResumeOffset; i++) {

					var opts = {
						url: dlmDBUpgrader.ajax,
						type: 'post',
						async: true,
						cache: false,
						dataType: 'json',
						data: {
							action: 'dlm_upgrade_db',
							nonce: dlm_upgrader.nonce,
							// The offset should be the count + the offset got by the transient in the case of a upgrade resume.
							offset: dlmDBUpgrader.counts + dlmDBUpgrader.upgraderResumeOffset,
						},
						success: function () {

							dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted - 1;
							dlmDBUpgrader.completed = dlmDBUpgrader.completed + 1;
							ProgressBar.progressHandler(((dlmDBUpgrader.completed + dlmDBUpgrader.upgraderResumeOffset) * 100) / dlmDBUpgrader.requestsNumber);
						}
					};

					dlmDBUpgrader.counts += 1;

					dlmDBUpgrader.ajaxRequests.push(opts);
				}
			}

			var alter_table_opts = {
				url: dlmDBUpgrader.ajax,
				type: 'post',
				async: true,
				cache: false,
				dataType: 'json',
				data: {
					action: 'dlm_alter_download_log',
					nonce: dlm_upgrader.nonce,
				},
				success: function () {

					dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted - 1;

					dlmDBUpgrader.completed = dlmDBUpgrader.completed + 1;

					if (dlmDBUpgrader.entries > 0) {
						ProgressBar.progressHandler(((dlmDBUpgrader.completed + dlmDBUpgrader.upgraderResumeOffset) * 100) / dlmDBUpgrader.requestsNumber);
					} else {
						ProgressBar.progressHandler((dlmDBUpgrader.completed + dlmDBUpgrader.upgraderResumeOffset) * 100);
					}
					jQuery('#dlm_progress-bar').parent().find('.dlm-progress-label').text('Upgrade completed!');
					jQuery('#dlm_progress-bar').addClass('completed');
				}
			};

			dlmDBUpgrader.ajaxRequests.push(alter_table_opts);

			dlmDBUpgrader.runAjaxs();

		},

		runAjaxs: function () {
			var currentAjax;
			while (dlmDBUpgrader.ajaxStarted < 2 && dlmDBUpgrader.ajaxRequests.length > 0) {
				dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted + 1;
				currentAjax = dlmDBUpgrader.ajaxRequests.shift();
				$.ajax(currentAjax);

			}

			if (dlmDBUpgrader.ajaxRequests.length > 0) {

				dlmDBUpgrader.ajaxTimeout = setTimeout(function () {
					console.log('Delayed 1s');
					dlmDBUpgrader.runAjaxs();
				}, 1000);
			}

		},
	};

	const ProgressBar = {
		el: {},
		label: {},

		init: (defaultValue = 0) => {

			ProgressBar.el = jQuery('#dlm_progress-bar');
			ProgressBar.label = jQuery('#dlm_progress-bar').parent().find('.dlm-progress-label');
			ProgressBar.label.text(Math.ceil(defaultValue) + '%');

			ProgressBar.el.progressbar({
				value: defaultValue,
				change: () => {
					ProgressBar.label.text(ProgressBar.el.progressbar('value') + '%');
				},
				complete: () => {
				}
			});
		},
		progressHandler: (newValue) => {

			ProgressBar.el.progressbar('value', Math.ceil(newValue));

		}
	};

	$(document).ready(function () {
		// Init importer
		dlmDBUpgrader.init();
	});

})(jQuery);