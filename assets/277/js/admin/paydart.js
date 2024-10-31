(function() {

    jQuery('.woocommerce-save-button').click(function(e) {
        websiteNameValiation(false);
        var websiteName = jQuery('#woocommerce_paydart_website').val();
        if(websiteName == "OTHERS"){
            var otherWebsiiteName = jQuery("#woocommerce_paydart_otherWebsiteName").val();
            if(otherWebsiiteName == ""){
                websiteNameValiation(true);
            }
        }
        var webhookTrigger = jQuery('.webhookTrigger').text();
        if(webhookTrigger ==1){
            var environment  =jQuery('#woocommerce_paydart_environment').val();
            var mid  =jQuery('#woocommerce_paydart_merchant_id').val();   
            var webhookUrl  =jQuery('.webhook-url').text();
            
            
            jQuery('.webhook-message').html('');
            if(mid==""){
                jQuery('.webhook-message').html('<div class="paydart_response error-box">Please enter MID</div>');
                return false;
            }
            if(webhookUrl==""){
                jQuery('.webhook-message').html('<div class="paydart_response error-box">Please check webhookUrl</div>');
                return false;
            }
         
            jQuery.ajax({
                type:"POST",
                dataType: 'json',
                data:{mid:mid,environment:environment,webhookUrl:webhookUrl},
                url: "admin-ajax.php?action=setPaymentNotificationUrl",
                async:false,
                success: function(data) {
                    if (data.message == true) {
                        //jQuery('.webhook-message').html('<div class="paydart_response success-box">WebhookUrl updated successfully</div>');
                        //alert("WebhookUrl updated successfully");
                    } else {
                        //jQuery('.webhook-message').html('<div class="paydart_response error-box">'+data.message+'</div>');
                    }

                    if(data.showMsg == true){
                        alert(data.message);
                        window.open('https://dashboard.paydart.com/next/webhook-url', '_blank');
                    }
                },
                complete: function() { 
                    return true;
                 }
            });
        }

     });   

     jQuery('#woocommerce_paydart_enabled').click(function(){
        if (jQuery('#woocommerce_paydart_enabled').is(':checked')) {
            //do nothing
        }else{
            if (confirm('Are you sure you want to disable PayDart, you will no longer be able to accept payments through us?')) {
                //disable pg
            }else{
                jQuery('#woocommerce_paydart_enabled').prop("checked",true);
            }    
        }
    });

    jQuery('#woocommerce_paydart_website').change(function() {
        websiteNameValiation(false);
        var data = jQuery('#woocommerce_paydart_website').val();
        if(data == "OTHERS"){
            document.getElementById("woocommerce_paydart_otherWebsiteName").style.display = 'block';
            document.getElementById("woocommerce_paydart_otherWebsiteName").setAttribute("placeholder", "Enter website name");


        }else{
            document.getElementById("woocommerce_paydart_otherWebsiteName").style.display = 'none';
            jQuery('#woocommerce_paydart_otherWebsiteName').val("");
        }
    });

    jQuery("#woocommerce_paydart_otherWebsiteName").on("keyup", function(event) {
        var value =jQuery("#woocommerce_paydart_otherWebsiteName").val();
        var check = isAlphaNumeric(value);
        
        if(!check){
            websiteNameValiation(true);
        }else{
           websiteNameValiation(false);

        }
    });
    
    function websiteNameValiation(showMessage = false){
        if(showMessage){
            //jQuery(".otherWebsiteName-error-message").text("Please enter a valid website name");
            jQuery(".otherWebsiteName-error-message").html("Please enter a valid website name provided by <a href='https://paydart.com' target='_blank'>PayDart</a>");
            jQuery('.woocommerce-save-button').prop('disabled', true);
            document.getElementById('woocommerce_paydart_website').scrollIntoView(true);

        }else{
            jQuery(".otherWebsiteName-error-message").text("");
            jQuery('.woocommerce-save-button').prop('disabled', false);
        }
    }
    function isAlphaNumeric(str) {
      var code, i, len;
      for (i = 0, len = str.length; i < len; i++) {
        code = str.charCodeAt(i);
        if (!(code > 47 && code < 58) && // numeric (0-9)
            !(code > 64 && code < 91) && // upper alpha (A-Z)
            !(code > 96 && code < 123)) { // lower alpha (a-z)
          return false;
        }
      }
      return true;
    };

})();
