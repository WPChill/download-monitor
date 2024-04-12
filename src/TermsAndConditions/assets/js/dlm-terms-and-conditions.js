jQuery(function ($) {

	if ($('.tc_accepted').closest('form').length > 0) { // Backwards compatibility
		$('.tc_accepted').change(function () {
			var btn = $(this).closest('form').find('.dlm-tc-submit');
			if (this.checked) {
				btn.removeAttr('disabled');
			} else {
				btn.attr('disabled', 'disabled');
			}
		});

		if ('undefined' !== typeof dlmXHR) {
			$('input.dlm-tc-submit').parents('form').on('submit', function (e) {
				e.preventDefault();

				const form      = $(this),
					  buttonObj = form.find('input.dlm-tc-submit'),
					  accepted  = $('input[name="tc_accepted"]').is(':checked') ? '1' : '0';
				var button;

				var href   = form.attr('action');
				const link = form.find('a.dlm-tlc__download-xhr');

				if (link.length > 0) {
					button = link.get(0);
				} else {
					form.append('<a href="' + href + '" class="dlm-tlc__download-xhr">');
					button = form.find('a.dlm-tlc__download-xhr').get(0);
				}

				var searchParams = new URLSearchParams(href);
				searchParams.set("tc_accepted", accepted);

				var triggerObject = {
					button   : button,
					href     : decodeURIComponent(searchParams.toString()),
					buttonObj: buttonObj

				};

				dlmXHRinstance.retrieveBlob(triggerObject);
				jQuery(document).on('dlm_download_complete', function () {
					form.find('img.dlm-xhr-loading-gif').remove();
				});
			});
		}
	} else { // New version
		process_dlm_tc_form();
	}

	function process_dlm_tc_form( form = false){

		if( ! form ){
			form  = $('.dlm_tc_form');
		}

		if ('undefined' == typeof form || form.length <= 0) {
			return;
		}

		form.each(function() {
			var btn   = jQuery( this ).find('a[href^="' + dlmXHRGlobalLinks + '"]');
			var check = jQuery( this ).find('.tc_accepted');
			var href  = btn.attr('href');
			let newHref;
			
			if ( href.indexOf('tc_accepted') > 0 ) {
				href = href.replace(/[&\?]tc_accepted=[0-1]/g, '');
			}
			
			if (check.is(':checked')) {
				if (href.indexOf('?') > -1) {
					newHref = href + '&tc_accepted=1';
				} else {
					newHref = href + '?tc_accepted=1';
				}
				btn.attr('href', newHref);
				btn.removeAttr('disabled');
				btn.css('pointer-events', 'all');
			} else {
				btn.attr('href', href);
				btn.attr('disabled', 'disabled');
				btn.css('pointer-events', 'none');
			}

			check.change(function () {
				const btn = $(this).closest('.dlm_tc_form').find('a[href^="' + dlmXHRGlobalLinks + '"]');
				let href  = btn.attr('href'),
					newHref;
	
				if ( href.indexOf('tc_accepted') > 0 ) {
					href = href.replace(/[&\?]tc_accepted=[0-1]/g, '');
				}
	
				if (href.indexOf('?') > -1) {
					newHref = href + '&tc_accepted=1';
				} else {
					newHref = href + '?tc_accepted=1';
				}
	
				if (this.checked) {
					btn.attr('href', newHref);
					btn.removeAttr('disabled');
					btn.css('pointer-events', 'all');
				} else {
					btn.attr('href', href);
					btn.attr('disabled', 'disabled');
					btn.css('pointer-events', 'none');
				}
			});
		});

		return form;
	}

	// Modal Form
	jQuery(document).on( 'dlm_terms_conditions_modal', function(e, response, data){
		
		var form = $('body').find('#dlm_terms_conditions_form .dlm_tc_form');
		process_dlm_tc_form( form );

	});

});