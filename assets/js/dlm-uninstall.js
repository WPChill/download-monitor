jQuery(document).ready(function ($) {

	var uninstall = $("a.uninstall-dlm"),
	    formContainer = $('#dlm-uninstall-form');

	formContainer.on('click', '#delete_all', function () {
		if ( formContainer.parent().find('#delete_all').is(':checked') ) {
			formContainer.parent().find('#delete_options').prop('checked', true);
			formContainer.parent().find('#delete_transients').prop('checked', true);
			formContainer.parent().find('#delete_cpt').prop('checked', true);
			formContainer.parent().find('#delete_set_tables').prop('checked', true);
		} else {
			formContainer.parent().find('#delete_options').prop('checked', false);
			formContainer.parent().find('#delete_transients').prop('checked', false);
			formContainer.parent().find('#delete_cpt').prop('checked', false);
			formContainer.parent().find('#delete_set_tables').prop('checked', false);
		}
	});

	$(uninstall).on("click", function () {

		$('body').toggleClass('dlm-uninstall-form-active');
		formContainer.fadeIn();

		formContainer.on('click', '#dlm-uninstall-submit-form', function (e) {
			formContainer.addClass('toggle-spinner');
			var selectedOptions = {
				delete_options: (formContainer.parent().find('#delete_options').is(':checked')) ? 1 : 0,
				delete_transients: (formContainer.parent().find('#delete_transients').is(':checked')) ? 1 : 0,
				delete_cpt: (formContainer.parent().find('#delete_cpt').is(':checked')) ? 1 : 0,
				delete_set_tables: (formContainer.parent().find('#delete_set_tables').is(':checked')) ? 1 : 0,
			};

			var data = {
				'action': 'dlm_uninstall_plugin',
				'security': wpDLMUninstall.nonce,
				'dataType': "json",
				'options': selectedOptions
			};

			$.post(
				ajaxurl,
				data,
				function (response) {
					// Redirect to plugins page
					window.location.href = wpDLMUninstall.redirect_url;
				}
			);
		});

		// If we click outside the form, the form will close
		// Stop propagation from form
		formContainer.on('click', function (e) {
			e.stopPropagation();
		});

		$('.dlm-uninstall-form-wrapper, .close-uninstall-form').on('click', function (e) {
			e.stopPropagation();
			formContainer.fadeOut();
			$('body').removeClass('dlm-uninstall-form-active');
		});

		$(document).on("keyup", function (e) {
			if ( e.key === "Escape" ) {
				formContainer.fadeOut();
				$('body').removeClass('dlm-uninstall-form-active');
			}
		});
	});
});