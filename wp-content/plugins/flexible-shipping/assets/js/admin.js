function fs_removeParam(key, sourceURL) {
    var rtn = sourceURL.split("?")[0],
        param,
        params_arr = [],
        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
    if (queryString !== "") {
        params_arr = queryString.split("&");
        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
            param = params_arr[i].split("=")[0];
            if (param === key) {
                params_arr.splice(i, 1);
            }
        }
        rtn = rtn + "?" + params_arr.join("&");
    }
    return rtn;
}

function fs_trimChar(string, charToRemove) {
    while(string.charAt(0)==charToRemove) {
        string = string.substring(1);
    }

    while(string.charAt(string.length-1)==charToRemove) {
        string = string.substring(0,string.length-1);
    }

    return string;
}

/* Notice */
jQuery(function($) {
    $( document ).on( 'click', '.flexible-shipping-taxes-notice .notice-dismiss', function () {
        $.ajax( ajaxurl,
            {
                type: 'POST',
                data: {
                    action: 'flexible_shipping_taxes_notice',
                }
            } );
    } );

	$( document ).on( 'click', '#enable-fs-connect-box', function () {
		var fs_connect_checkbox = $('.enable-fs-connect-box');
		var fs_box_state;

		if ( fs_connect_checkbox.prop('checked') ){
			$('.fs-connect-integration-box').slideDown();
			fs_box_state = 1;
		} else{
			$('.fs-connect-integration-box').slideUp();
			fs_box_state = 0;
		}

		$.ajax( ajaxurl,
			{
				type: 'POST',
				data: {
					action: 'update_fs_connect_integration_setting',
					fs_box_state: fs_box_state
				}
			} );
	} );

	$( document ).on( 'click', '#flexible_shipping_rate_plugin .close-fs-rate-notice', function () {
		$( '#flexible_shipping_rate_plugin .notice-dismiss' ).click();
	} );

	$( document ).on( 'click', '#flexible_shipping_rate_plugin .fs-not-good', function () {
		$('#flexible_shipping_rate_plugin p').html( fs_admin.notice_not_good_enought );
	} );

});

/* Free shipping */
jQuery(function($) {
	function fs_toggle_free_shipping_notice() {
		$('#woocommerce_flexible_shipping_method_free_shipping_cart_notice').closest('tr').toggle($('#woocommerce_flexible_shipping_method_free_shipping').val()!=='');
	}

	$('#woocommerce_flexible_shipping_method_free_shipping').on('change',  function(){
		fs_toggle_free_shipping_notice();
	});

	fs_toggle_free_shipping_notice();
});
