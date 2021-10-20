(function ( $ ) {
	"use strict";


	/**
	 * Modula Importer
	 *
	 * @type {{init: init, runAjaxs: runAjaxs, ajaxTimeout: null, counts: number, processAjax: processAjax, ajaxRequests: [], completed: number, updateImported: updateImported, ajaxStarted: number}}
	 */
	var dlmDBUpgrader = {
		counts        : 0,
		completed     : 0,
		ajaxRequests  : [],
		ajaxStarted   : 1,
		ajaxTimeout   : null,
		ajax          : ajaxurl,
		entries       : 0,
		requestsNumber: 0,

		init: function () {

			const opts = {
				url     : dlmDBUpgrader.ajax,
				type    : 'post',
				async   : true,
				cache   : false,
				dataType: 'json',
				data    : {
					action: 'dlm_db_log_entries',
					nonce : dlm_upgrader.nonce,
				},
				success : function ( response ) {
					dlmDBUpgrader.entries = response;

				}
			};

			$.ajax( opts );

			$( document ).on( 'click', 'button#dlm-upgrade-db', function ( e ) {
				e.preventDefault();
				dlmDBUpgrader.completed = 0;

				dlmDBUpgrader.processAjax();
				ProgressBar.init();

			} );

		},

		processAjax: function () {


			if ( dlmDBUpgrader.entries > 0 ) {
				dlmDBUpgrader.requestsNumber = parseInt( Math.ceil( dlmDBUpgrader.entries / 10000 ) );
			}

			for ( let i = 0; i <= dlmDBUpgrader.requestsNumber; i++ ) {

				var opts = {
					url     : dlmDBUpgrader.ajax,
					type    : 'post',
					async   : true,
					cache   : false,
					dataType: 'json',
					data    : {
						action: 'dlm_upgrade_db',
						nonce : dlm_upgrader.nonce,
						offset: dlmDBUpgrader.counts,
					},
					success : function () {

						dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted - 1;

						dlmDBUpgrader.completed = dlmDBUpgrader.completed + 1;

						ProgressBar.progressHandler( (dlmDBUpgrader.completed * 100) / dlmDBUpgrader.requestsNumber );

					}
				};

				dlmDBUpgrader.counts += 1;

				dlmDBUpgrader.ajaxRequests.push( opts );
			}

			dlmDBUpgrader.runAjaxs();

		},

		runAjaxs: function () {
			var currentAjax;
			while ( dlmDBUpgrader.ajaxStarted < 2 && dlmDBUpgrader.ajaxRequests.length > 0 ) {
				dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted + 1;
				currentAjax = dlmDBUpgrader.ajaxRequests.shift();
				$.ajax( currentAjax );

			}

			if ( dlmDBUpgrader.ajaxRequests.length > 0 ) {

				dlmDBUpgrader.ajaxTimeout = setTimeout( function () {
					console.log( 'Delayed 1s' );
					dlmDBUpgrader.runAjaxs();
				}, 1000 );
			}

		},
	};

	const ProgressBar = {
		el   : {},
		label: {},

		init: () => {

			ProgressBar.el = jQuery( '#dlm_progress-bar' );
			ProgressBar.label = jQuery( '#dlm_progress-bar .dlm-progress-label' );

			ProgressBar.el.progressbar( {
				value: 0,
				change  : () => {
					ProgressBar.label.text( ProgressBar.el.progressbar( 'value' ) + '%' );
				},
				complete: () => {
					ProgressBar.label.text( 'Complete!' );
				}
			} );
		},
		progressHandler: ( newValue ) => {
			ProgressBar.el.progressbar( 'value', Math.ceil( newValue ) );
		}
	};

	$( document ).ready( function () {
		// Init importer
		dlmDBUpgrader.init();
	} );

})( jQuery );

