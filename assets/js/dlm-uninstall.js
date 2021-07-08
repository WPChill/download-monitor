jQuery(document).ready(function ($) {

	var uninstall = $("a.uninstall-dlm"),
	    formContainer = $('#dlm-uninstall-form');

	formContainer.on('click', '#delete_all', function () {
		if ( $('#delete_all').is(':checked') ) {
			$('#delete_options').prop('checked', true);
			$('#delete_transients').prop('checked', true);
			$('#delete_cpt').prop('checked', true);
			$('#delete_set_tables').prop('checked', true);
		} else {
			$('#delete_options').prop('checked', false);
			$('#delete_transients').prop('checked', false);
			$('#delete_cpt').prop('checked', false);
			$('#delete_set_tables').prop('checked', false);
		}
	});

	$(uninstall).on("click", function () {

		$('body').toggleClass('dlm-uninstall-form-active');
		formContainer.fadeIn();

		formContainer.on('click', '#dlm-uninstall-submit-form', function (e) {
			formContainer.addClass('toggle-spinner');
			var selectedOptions = {
				delete_options: ($('#delete_options').is(':checked')) ? 1 : 0,
				delete_transients: ($('#delete_transients').is(':checked')) ? 1 : 0,
				delete_cpt: ($('#delete_cpt').is(':checked')) ? 1 : 0,
				delete_set_tables: ($('#delete_set_tables').is(':checked')) ? 1 : 0,
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