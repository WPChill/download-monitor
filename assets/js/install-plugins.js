(function( wp, $ ) {
  	'use strict';
  	if ( ! wp ) {
    	return;
  	}
  
  	function activatePlugin( url ) {
	    $.ajax( {
	      	async: true,
	      	type: 'GET',
	      	dataType: 'html',
	      	url: url,
	      	success: function() {
	        	location.reload();
	      	}
	    });
	}

	// Install plugins actions
	$('a.dlm-install-plugin-link').on('click', (event) => {

		event.preventDefault();
		const current = $(event.currentTarget);
		const plugin_slug = current.data('slug');
		const plugin_action = current.data('action');
		const element = current.parents('tr[data-setting]').attr('data-setting');
		const activate_url = current.data('activation_url');

		// Now let's disable the button and show the action text
		current.attr('disabled', true);


		if ( 'install' === plugin_action ) {

			current.after('<span class="dlm-install-plugin-actions">' + dlm_install_plugins_vars.install_plugin + '</span>');

			const args = {
				slug: plugin_slug,
				success: (response) => {
					current.next('span').remove();
					current.after('<span>' + dlm_install_plugins_vars.activate_plugin + '</span>');
					activatePlugin( response.data.activateUrl );
				},
				error: (response) => {  
					current.next('span').remove();
					current.after('<span>' + dlm_install_plugins_vars.no_install + ' ' + response.data.errorMessage + '</span>');
				}	
			}

			wp.updates.installPlugin(args);
		} else if ( 'activate' === plugin_action ) {
			current.after('<span class="dlm-install-plugin-actions">' + dlm_install_plugins_vars.activate_plugin + '</span>');
			activatePlugin( activate_url );
		}

	});

	$( document ).on( 'wp-plugin-install-success', function( response, data ) {
		if ( 'modula-best-grid-gallery' == data.slug ) {
			event.preventDefault();
			activatePlugin( data.activateUrl );
		}
	} );
})( window.wp, jQuery );