jQuery(document).ready(function () {
	jQuery("#save-salt-shaker-settings").on(
		"click",
		["#schedualed_salt_changer", "#schedualed_salt_value"],
		function (e) {
			jQuery("#saving_spinner").css("visibility", "visible");
			jQuery("#change_salts_now").prop("disabled", "disabled");
			jQuery.post(
				ajaxurl,
				{
					action: "save_salt_schd",
					interval: jQuery("#schedualed_salt_value").val(),
					enabled: jQuery("#schedualed_salt_changer").is(":checked"),
					_ssnonce_scheduled: jQuery("#_ssnonce_scheduled").val()
				},
				function (response) {
					jQuery("#saving_spinner").css("visibility", "hidden");
					setTimeout(function () {
						jQuery("#change_salts_now").prop("disabled", "");
						location.reload();
					}, 3000);
				}
			);
		}
	);

	jQuery("#change_salts_now").click(function (e) {
		jQuery("#saving_spinner").css("visibility", "visible");
		jQuery("#change_salts_now").prop("disabled", "disabled");
		jQuery.post(
			ajaxurl,
			{
				action: "change_salts_now",
				_ssnonce_now: jQuery("#_ssnonce_now").val()
			},
			function (response) {
				jQuery("#saving_spinner").css("visibility", "hidden");
				jQuery(".keys_updated_message").show();
				setTimeout(function () {
					jQuery("#change_salts_now").prop("disabled", "");
					location.reload();
				}, 3000);
			}
		);
	});
});
