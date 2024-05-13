(function (wp, $) {
	'use strict';
	if (!wp) {
		return;
	}

	/**
	 * Activate the plugin
	 * @param url
	 */
	function activatePlugin(url) {
		$.ajax(
			{
				async   : true,
				type    : 'GET',
				dataType: 'html',
				url     : url,
				success : function () {
					location.reload();
				}
			}
		);
	}

	// Install plugins actions
	$('a.dlm-install-plugin-link').on('click', (event) => {

		event.preventDefault();
		const current       = $(event.currentTarget);
		const plugin_slug   = current.data('slug');
		const plugin_action = current.data('action');
		const element       = current.parents('tr[data-setting]').attr('data-setting');
		const activate_url  = current.data('activation_url');

		// Now let's disable the button and show the action text
		current.attr('disabled', true);

		if ('install' === plugin_action) {

			current.after('<span class="dlm-install-plugin-actions">' + dlm_install_plugins_vars.install_plugin + '</span>');
			const args = {
				slug    : plugin_slug, success: (response) => {
					current.next('span').remove();
					current.after('<span>' + dlm_install_plugins_vars.activate_plugin + '</span>');
					activatePlugin(response.activateUrl);
				}, error: (response) => {
					current.next('span').remove();
					current.after('<span>' + dlm_install_plugins_vars.no_install + ' ' + response.errorMessage + '</span>');
				}
			}

			wp.updates.installPlugin(args);
		} else if ('activate' === plugin_action) {
			current.after('<span class="dlm-install-plugin-actions">' + dlm_install_plugins_vars.activate_plugin + '</span>');
			activatePlugin(activate_url);
		}
	});

	/**
	 * Install and activate addon
	 * @param $url
	 * @param plugin_path
	 */
	function dlm_plugins_install_addon($url, plugin_path) {
		const addon = $('.dlm-plugin-install-action[data-path="' + plugin_path + '"]');
		addon.parents('.dlm-install-plugin-actions').find('button.dlm-plugin-install-action').attr('disabled', 'disabled');
		// Process the Ajax to perform the activation.

		const opts = {
			url     : ajaxurl,
			type    : 'post',
			async   : true,
			cache   : false,
			dataType: 'json',
			data    : {
				action: 'dlm-extensions-install-addons',
				nonce : dlm_install_plugins_vars.install_nonce,
				plugin: $url
			},
			success : function (response) {
				// If there is a WP Error instance, output it here and quit the script.
				if (response.error) {
					console.log(response.error);
					return;
				}
				addon.parents('.dlm-install-plugin-actions').find('button.dlm-plugin-install-action').removeAttr('disabled');
				// The Ajax request was successful, so let's update the output.
				dlm_plugins_activate_addon(plugin_path);
			},
			error   : function (xhr, textStatus, e) {
				console.log(xhr);
				console.log(textStatus);
				console.log(e);
			}
		};

		$.ajax(opts);
	}

	/**
	 * Activate the plugin
	 * @param plugin_path
	 */
	function dlm_plugins_activate_addon(plugin_path) {
		const addon = $('.dlm-plugin-install-action[data-path="' + plugin_path + '"]');
		addon.text(dlm_install_plugins_vars.activate_plugin);
		addon.parents('.dlm-install-plugin-actions').find('button.dlm-plugin-install-action').attr('disabled', 'disabled');

		jQuery.ajax(
			{
				url     : ajaxurl,
				type    : 'post',
				async   : true,
				cache   : false,
				dataType: 'json',
				data    : {
					action     : 'dlm-extensions-activate-addon',
					nonce      : dlm_install_plugins_vars.install_nonce,
					plugin_path: plugin_path,
				},
				success : function (response) {
					addon.data('action', 'installed');
					addon.text(dlm_install_plugins_vars.activated_plugin);
					dlm_plugins_activate_addon_license(plugin_path);
				},
			}
		);
	}

	/**
	 * Activate the plugin license
	 * @param plugin_path
	 */
	function dlm_plugins_activate_addon_license(plugin_path) {
		const addon = $('.dlm-plugin-install-action[data-path="' + plugin_path + '"]'), pid = addon.data('pid');
		addon.text(dlm_install_plugins_vars.activate_license);
		addon.parents('.dlm-install-plugin-actions').find('button.dlm-plugin-install-action').attr('disabled', 'disabled');

		jQuery.ajax(
			{
				url     : ajaxurl,
				type    : 'post',
				async   : true,
				cache   : false,
				dataType: 'json',
				data    : {
					action        : 'dlm-extensions-activate-addon-license',
					nonce         : dlm_install_plugins_vars.install_nonce,
					api_product_id: pid,
				},
				success : function (response) {
					addon.data('action', 'installed');
					addon.text(dlm_install_plugins_vars.activated_license);
					setTimeout(function () {
						addon.text(dlm_install_plugins_vars.active)
					}, 1500);
				},
			}
		);
	}

	$(document).ready(function () {

		// Re-enable install button if user clicks on it, needs creds but tries to install another addon instead.
		$('.dlm-plugin-install-action').on('click', function (e) {
			e.preventDefault();
			const url          = $(this).data('url'),
				  action       = $(this).data('action'),
				  text_wrapper = $(this),
				  plugin_path  = $(this).data('path');

			if ('install' === action) {
				text_wrapper.text(dlm_install_plugins_vars.install_plugin);
				dlm_plugins_install_addon(url, plugin_path);
			} else if ('activate' === action) {
				text_wrapper.text(dlm_install_plugins_vars.activate_plugin);
				dlm_plugins_activate_addon(plugin_path);
			}
		});
	});
})(window.wp, jQuery);