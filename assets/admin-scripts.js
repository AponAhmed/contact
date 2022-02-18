jQuery(function ($) {
    //console.log($);
    $(document).ready(function () {
        $("#gApiCcofigForm").on('submit', function (e) {
            e.preventDefault();
            console.log('Authorizing...');
            $(".authButton").html("Authorizing...");
            var data = {
                'action': 'gApiC_config',
                'jsonData': jQuery('#jsonData').val(),
            };
            // We can also pass the url value separately from ajaxurl for front end AJAX implementations
            jQuery.post(ajax_object.ajax_url, data, function (response) {
                console.log(response);
                if (response == 1) {
                    $(".authButton").html("<span class=\"dashicons dashicons-yes-alt\" style=\"padding: 4px 0;\"></span> Authorized");
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    $(".authButton").html("<span class=\"dashicons dashicons-dismiss\" style=\"padding: 4px 0;\"></span> Failed");
                    setTimeout(function () {
                        $(".authButton").html("Try Again");
                    }, 1000);
                }
            });


        })
    });
})