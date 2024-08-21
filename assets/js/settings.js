jQuery(function ($) {

	$('#setting-dlm_default_template').change(function () {
		if ($(this).val() === 'custom') {
			$('#setting-dlm_custom_template').closest('tr').show();
		} else {
			$('#setting-dlm_custom_template').closest('tr').hide();
		}
	}).change();

	$('#setting-dlm_enable_logging').change(function () {
		if ($(this).is(":checked") === true) {
			$('#setting-dlm_count_unique_ips').closest('tr').show();
		} else {
			$('#setting-dlm_count_unique_ips').closest('tr').hide();
		}
	}).change();

	$('#setting-dlm_logging_ip_type').change(function () {
		if ($(this).val() === 'full') {
			$('#setting-dlm_count_unique_ips').closest('tr').show();
		} else {
			$('#setting-dlm_count_unique_ips').closest('tr').hide();
		}
	}).change();

	$(document).ready(function () {

		// load lazy-select elements
		$.each($('.dlm-lazy-select'), function () {

			var lazy_select_el = $(this);

			// add AJAX loader
			$('<span>').addClass('dlm-lazy-select-loader').append(
				$('<img>').attr('src', dlm_settings_vars.img_path + 'ajax-loader.gif')
			).insertAfter(lazy_select_el);

			// load data
			$.post(ajaxurl, {
				action: 'dlm_settings_lazy_select',
				nonce : dlm_settings_vars.lazy_select_nonce,
				option: lazy_select_el.attr('name')
			}, function (response) {

				// remove current option(s)
				lazy_select_el.find('option').remove();

				// set new options
				if (response) {
					var selected = lazy_select_el.data('selected');
					for (var i = 0; i < response.length; i++) {
						var opt = $('<option>').attr('value', response[i].key).html(response[i].lbl);
						if (selected === response[i].key) {
							opt.attr('selected', 'selected');
						}
						lazy_select_el.append(opt);
					}
				}

				// remove ajax loader
				lazy_select_el.parent().find('.dlm-lazy-select-loader').remove();

			});
		});

		$('tr.dlm_group_setting').on('click', '.postbox-header', (event) => {
			event.preventDefault();
			event.stopPropagation();
			$(event.currentTarget).parent().toggleClass('closed');
		});

		dlm_shop_settings();
		dlm_shop_settings_change();
	});

	// Hide show shop settings based on shop enabled/disabled
	function dlm_shop_settings() {
		const shop_button = $('#setting-dlm_shop_enabled'),
			  settings    = $('.dlm-admin-settings.shop table tr:not( [data-setting="dlm_shop_enabled"] )'),
			  tabs        = $('.dlm-admin-settings.shop .dlm-settings-sub-nav li:not(:first-child)');

		if (!dlm_settings_vars.shop_enabled) {
			settings.hide();
			tabs.hide();
		}
	}

	// Hide show shop settings based on shop enabled/disabled change
	function dlm_shop_settings_change() {
		const shop_button = $('#setting-dlm_shop_enabled'),
			  settings    = $('.dlm-admin-settings.shop table tr:not( [data-setting="dlm_shop_enabled"] )'),
			  tabs        = $('.dlm-admin-settings.shop .dlm-settings-sub-nav li:not(:first-child)');
		;

		// Hide show shop settings based on shop enabled/disabled change
		shop_button.on('change', function () {
			let shop_value = $(this).is(':checked');
			if (!shop_value) {
				settings.hide();
				tabs.hide();
			} else {
				settings.show();
				tabs.show();
			}
			dlm_ajax_save_shop(shop_value);
		});
	}

	// Save the Enable Shop setting
	function dlm_ajax_save_shop(value) {
		// Save the Enable Shop settings using AJAX
		$.post(ajaxurl, {
			action: 'dlm_enable_shop',
			nonce : dlm_settings_vars.nonce,
			value : value
		}, function (response) {
		});
	}
});