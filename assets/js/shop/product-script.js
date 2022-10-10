jQuery( function ( $ ) {
	 // Copy shortcode functionality
     $('.copy-dlm-shortcode').click( (e) => {
        e.preventDefault();

         const target        = $(e.currentTarget);
         const dlm_shortcode = target.find('input');
         dlm_shortcode.trigger("focus");
         dlm_shortcode.trigger("select");
         document.execCommand('copy');
         $(this).next('span').text($(this).data('item') + ' copied');
         $('.copy-dlm-button').not($(this)).parent().find('span').text('');
         dlm_shortcode.trigger("blur");

        target.find('.wpchill-tooltip-content span').text(dlm_product_overview.shortcode_copied);
        setTimeout(() => {
            target.find('.wpchill-tooltip-content span').text(dlm_product_overview.copy_shortcode);
        }, 1000);
    });
});