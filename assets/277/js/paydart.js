(function() { 
    if (jQuery(".msg-by-paydart")[0]){
        document.getElementsByClassName('msg-by-paydart')[0].innerHTML = '';
    }
        jQuery(document).ready(function () {
            jQuery( 'body' ).on( 'updated_checkout', function() {
				let str = jQuery("label[for=payment_method_paydart]").html(); 
				/* let res = str.replace(/PayDart/, "");
				 jQuery("label[for=payment_method_paydart]").html(res); */
				jQuery("label[for=payment_method_paydart]").css("visibility","visible");
            });
            
        });
})();
 