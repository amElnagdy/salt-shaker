jQuery(document).ready(function(){
   
   jQuery(document).on("change", ["#schedualed_salt_changer", "#schedualed_salt_value"], function(e){
       jQuery("#saving_spinner").css("visibility", "visible");
       jQuery.post(
            ajaxurl, 
            {
                'action': 'save_salt_schd',
                'interval': jQuery("#schedualed_salt_value").val(),
                'enabled': jQuery("#schedualed_salt_changer").is(":checked")
            }, 
            function(response){
                jQuery("#saving_spinner").css("visibility", "hidden");
            }
        );
   });
   
   jQuery("#change_salts_now").click(function(e){
       jQuery("#saving_spinner").css("visibility", "visible");
       jQuery.post(
            ajaxurl, 
            {
                'action': 'change_salts_now'
            }, 
            function(response){
                jQuery("#saving_spinner").css("visibility", "hidden");
            }
        );
   });
});