jQuery( function ( $ ) {
	 // Copy shortcode functionality
     $('.copy-dlm-shortcode').click( (e) => {
        e.preventDefault();

        const target = $(e.currentTarget);
        const dlm_shortcode = target.find('input');
        navigator.clipboard.writeText(dlm_shortcode.val());
        target.find('.wpchill-tooltip-content span').text(dlm_product_overview.shortcode_copied);
        setTimeout(() => {
            target.find('.wpchill-tooltip-content span').text(dlm_product_overview.copy_shortcode);
        }, 1000);
    });
});