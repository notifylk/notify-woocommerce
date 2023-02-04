'use strict'

jQuery(document).ready(function ($) {
    $('form[name="checkout"], button[name="woocommerce_checkout_place_order"]').submit(function (e) {
        e.preventDefault();
        var phone_number = $('input[name="billing_phone"]').val();
        // Validate the phone number field
        if (!phone_number) {
            alert('Phone number is required');
            return false;
        }

        // Send an OTP to the phone number
        sendOTP(phone_number, function (response) {
            if (response.success) {
                var otp = prompt('Please enter the OTP:');
                // Validate the OTP
                validateOTP(otp, function (valid) {
                    if (valid) {
                        // Submit the form if the OTP is valid
                        $('form.register').off('submit').submit();
                    } else {
                        alert('Invalid OTP');
                    }
                });
            } else {
                alert('Failed to send OTP');
            }
        });
    });
});

function sendOTP(phone_number, callback) {
    var ajaxurl = '/wp-json/notifywc/v1/notifylk_wc_otp/';

    var data = {
        'action': 'send_otp',
        'phone_number': phone_number
    };

    jQuery.post(ajaxurl, data, function (response) {
        callback({ success: response.status == 'success' });
    });

}

function validateOTP(otp, callback) {
    var ajaxurl = '/wp-json/notifywc/v1/notifylk_wc_validate_otp';

    var data = {
        'action': 'validate_otp',
        'otp': otp
    };

    jQuery.post(ajaxurl, data, function (response) {
        alert(response.status)
        callback({ success: response.status == 'success' });
    });
}
