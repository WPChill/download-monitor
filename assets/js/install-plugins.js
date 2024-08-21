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

			current.text(dlm_install_plugins_vars.install_plugin);
			const args = {
				slug    : plugin_slug, success: (response) => {
					current.text(dlm_install_plugins_vars.activate_plugin);
					activatePlugin(response.activateUrl);
				}, error: (response) => {
					current.text(dlm_install_plugins_vars.no_install + ' ' + response.errorMessage);
				}
			}

			wp.updates.installPlugin(args);
		} else if ('activate' === plugin_action) {
			current.text(dlm_install_plugins_vars.activate_plugin);
			activatePlugin(activate_url);
		}
	});
})(window.wp, jQuery);