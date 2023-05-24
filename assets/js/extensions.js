jQuery(function ($) {

	$.each($('.extension_license a'), function (k, v) {
		$(v).click(function () {
			var wrap = $(v).closest('.extension_license');
			const button = $(this);
			// Stop pointer events after click.
			button.css('pointer-events', 'none');

			var ex_ac = (
							'inactive' == $(wrap).find('#status').val()
						) ? 'activate' : 'deactivate';

			$(wrap).find('.dlm_license_error').remove();

			$.post(ajaxurl, {
				action          : 'dlm_extension',
				nonce           : $('#dlm-ajax-nonce').val(),
				product_id      : $(wrap).find('#product_id').val(),
				key             : $(wrap).find('#key').val(),
				email           : $(wrap).find('#email').val(),
				extension_action: ex_ac,
				action_trigger  : '-ext-license'
			}, function (response) {
				if (response.result == 'failed') {
					$(wrap).prepend($("<div>").addClass("dlm_license_error").html(response.message));
				} else {
					if ('activate' == ex_ac) {
						$(wrap).find('.license-status').addClass('active').html('ACTIVE');
						$(wrap).find('.button').html('Deactivate');
						$(wrap).find('#status').val('active');
						$(wrap).find('#key').attr('disabled', true);
						$(wrap).find('#email').attr('disabled', true);
					} else {
						$(wrap).find('.license-status').removeClass('active').html('INACTIVE');
						$(wrap).find('.button').html('Activate');
						$(wrap).find('#status').val('inactive');
						$(wrap).find('#key').attr('disabled', false);
						$(wrap).find('#email').attr('disabled', false);
					}
				}
				// Redo pointer events after ajax done.
				button.css('pointer-events', 'auto');
			});

		});
	});

	$('#dlm-master-license-btn').on('click', (e) => {
		e.preventDefault();

		const target       = $(e.target),
			  parent       = target.parents('.dlm-master-license'),
			  license      = $('#dlm-master-license').val(),
			  emailAddress = $('#dlm-master-license-email').val(),
			  nonce        = parent.find('input[type="hidden"]').val(),
			  ex_ac        = target.data('action'),
			  extensions   = parent.parent().find('.extension_license'),
			  actionText   = (ex_ac == 'activate') ? extensions_vars.activate : extensions_vars.deactivate;

		// Stop pointer events after click.
		target.css('pointer-events', 'none');
		// If no license present return.
		if ('' === license) {
			// Redo pointer events.
			target.css('pointer-events', 'auto');
			parent.find('.dlm-master-license-response').remove();
			parent.append('<div class="dlm-master-license-response">' + extensions_vars.missing_license + '</div>');
			return;
		}

		if ( '' === emailAddress ) {
			// Redo pointer events.
			target.css('pointer-events', 'auto');
			parent.find('.dlm-master-license-response').remove();
			parent.append('<div class="dlm-master-license-response">' + extensions_vars.missing_email + '</div>');
			return;
		}

		target.attr('disabled', 'disabled');
		parent.append('<div class="dlm-master-license-response">' + actionText + '</div>');
		$.post(ajaxurl, {
			action          : 'dlm_master_license',
			nonce           : nonce,
			key             : license,
			email           : emailAddress,
			extension_action: ex_ac,
			action_trigger  : '-master-license'
		}, function (response) {
			target.removeAttr('disabled');
			if (response.result == 'failed') {
				parent.find('.dlm-master-license-response').remove();
				parent.append('<div class="dlm-master-license-response">' + response.message + '</div>');
			} else {
				window.location.href = window.location.href;
			}
			// Redo pointer events after ajax done.
			target.css('pointer-events', 'auto');
		});
	});

	$('#dlm-forgot-license').on('click', (e) => {
		e.preventDefault();

		const target       = $(e.target),
			  nonce        = target.data('nonce'),
			  parent       = target.parents('.dlm-master-license'),
			  emailInput   = $('#dlm-master-license-email'),
			  emailAddress = emailInput.val();

		target.css('pointer-events', 'none');
		$('.dlm-master-license-response').remove();
		parent.append('<div class="dlm-master-license-response">' + extensions_vars.reaching_server + '</div>');

		if (!emailAddress || '' === emailAddress) {
			parent.find('.dlm-master-license-response').remove();
			parent.append('<div class="dlm-master-license-response">' + extensions_vars.missing_email + '</div>');
			emailInput.prop('required', true);
			target.css('pointer-events', 'auto');
			return;
		}

		$.post(ajaxurl, {
			action: 'dlm_forgot_license',
			nonce : nonce,
			email : emailAddress,
		}, function (response) {
			target.removeAttr('disabled');

			if ('undefined' === typeof response.result) {
				parent.find('.dlm-master-license-response').remove();
				parent.append('<div class="dlm-master-license-response">' + extensions_vars.forget_license_error + '</div>');
			} else {
				emailInput.prop('required', false);
				parent.find('.dlm-master-license-response').remove();
				parent.append('<div class="dlm-master-license-response">' + response.message + '</div>');
				target.css('pointer-events', 'auto');
			}
		});
		target.css('pointer-events', 'none');
	})
});