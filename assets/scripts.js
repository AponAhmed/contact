
var total;

function getRandom() {
    return Math.ceil(Math.random() * 20)
}

function createSum() {
    var randomNum1 = getRandom(),
            randomNum2 = getRandom();
    total = randomNum1 + randomNum2;
    jQuery("#question").text(randomNum1 + " + " + randomNum2 + "=");
    jQuery('#success, #fail').hide();
    jQuery('#message').show()
}

function checkInput() {
    var input = jQuery("#ans").val(),
            slideSpeed = 200,
            hasInput = !!input,
            valid = hasInput & input == total;
    if (valid) {
        jQuery(".question").css("border-color", "green")
    } else {
        jQuery(".question").css("border-color", "red")
    }
    jQuery('button[type=submit]').prop('disabled', !valid)
}

function contactForm_init() {
    jQuery("#reff").val(document.referrer);
    createSum();
    jQuery("#ans").keyup(checkInput);
    jQuery("#ans").change(checkInput);
    var loader = "<span class='spinLoader'>";
    var loaderBig = "<span class='bodyLoader'>";
    jQuery("#contactForm").submit(function (e) {
        jQuery("#submitBtn").attr('type', 'button');//To Prevent Resend when already Processing
        e.preventDefault();
        var data = {
            action: "contactActionAjax",
            data: jQuery("#contactForm").serialize(),
        };
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(contactAjaxObj.ajaxurl, data, function (response) {
            var obj = JSON.parse(response);
            if (obj.error === false) {
                jQuery('#contactForm')[0].reset();
                jQuery(".contactMsg").html(obj.message).css("color", "green");
            } else {
                jQuery(".contactMsg").html(obj.message).css("color", "red");
                jQuery("#submitBtn").attr('type', 'submit');
            }
        });
    })
}