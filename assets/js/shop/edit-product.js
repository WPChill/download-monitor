jQuery( function ( $ ) {
	$('.dlm-select-ext').select2();

	// Copy button functionality
	$('.copy-dlm-button').click(function(e) {
		e.preventDefault();
		var dlm_input = $(this).parent().find('input');
		dlm_input.focus();
		dlm_input.select();
		document.execCommand('copy');
		$(this).next('span').text( $(this).data('item') + ' copied');
		$('.copy-dlm-button').not($(this)).parent().find('span').text('');
	});
});