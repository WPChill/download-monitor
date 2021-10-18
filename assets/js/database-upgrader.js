(function ( $ ) {
	"use strict";


	/**
	 * Modula Importer
	 *
	 * @type {{init: init, runAjaxs: runAjaxs, ajaxTimeout: null, counts: number, processAjax: processAjax, ajaxRequests: [], completed: number, updateImported: updateImported, ajaxStarted: number}}
	 */
	var dlmDBUpgrader = {
		counts      : 0,
		completed   : 0,
		ajaxRequests: [],
		ajaxStarted : 1,
		ajaxTimeout : null,
		ajax        : ajaxurl,
		entries : 0,
		requestsNumber : 0,

		init: function () {

			console.log(dlm_upgrader);

			var opts = {
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

			$( 'html,body' ).on( 'click', '#dlm-upgrade-db', function ( e ) {
				e.preventDefault();

				dlmDBUpgrader.completed = 0;

				dlmDBUpgrader.processAjax();

			} );

		},

		processAjax: function () {

			if ( dlmDBUpgrader.entries > 0 ) {
				dlmDBUpgrader.requestsNumber = parseInt( Math.ceil( dlmDBUpgrader.entries / 10000 ) );
			}
			console.log(dlmDBUpgrader.requestsNumber);
			return;

			dlmDBUpgrader.counts += 1;

			var opts = {
				url     : dlmDBUpgrader.ajax,
				type    : 'post',
				async   : true,
				cache   : false,
				dataType: 'json',
				data    : {
					action  : 'dlm_upgrade_db',
					nonce   : dlmDBUpgrader.nonce,
					offset  : dlmDBUpgrader.counts,
					imported: true
				},
				success : function ( response ) {

					dlmDBUpgrader.ajaxStarted = dlmDBUpgrader.ajaxStarted - 1;

					if ( !response.success ) {
						status.find( 'span' ).not( '.importing-status' ).text( response.message );

						dlmDBUpgrader.completed = dlmDBUpgrader.completed + 1;
						return;
					}

					dlmDBUpgrader.completed = dlmDBUpgrader.completed + 1;

				}
			};

			dlmDBUpgrader.ajaxRequests.push( opts );

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

	$( document ).ready( function () {
		// Init importer
		dlmDBUpgrader.init();
	} );

})( jQuery );