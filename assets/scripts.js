
var total;
var iconHtm;

function getRandom() {
    return Math.ceil(Math.random() * 20)
}

class WriteUs {
    constructor() {
        this.n1 = this.getRandom();
        this.n2 = this.getRandom();
        this.total = this.n1 + this.n2;
        this.sendable = false;
        this.body = document.querySelector('body');

        // Define the class properties here
        this.formElement = null;
        this.nameInput = null;
        this.emailInput = null;
        this.subjectInput = null;
        this.messageTextarea = null;
        this.questionLabel = null;
        this.answerInput = null;
        this.submitButton = null;
        this.contactMsg = null;

        this.dom = document.createElement('div');
        this.dom.classList.add('write-us');
        this.dom.classList.add('floated-contact-form-wrap');

        // Call the method to build the DOM elements
        this.buildDOM();

    }

    metch() {
        if (this.answerInput.value != this.total) {
            this.answerInput.parentNode.style.border = '1px solid #f00';
            this.sendable = false;
        } else {
            this.answerInput.parentNode.removeAttribute('style');
            this.sendable = true;
            this.contactMsg.innerHTML = "";
        }
    }

    handleSubmit(event) {
        this.contactMsg.innerHTML = "";
        event.preventDefault();
        if (!this.sendable) {
            this.contactMsg.innerHTML = "Please Enter valid information";
            this.contactMsg.style.color = "red";
            return;
        }
        let loader = "<span class='spinLoader'><i></i><i></i><i></i><i></i><i></i><i></i></span>";
        //this.submitButton.after(loader);
        this.submitButton.innerHTML = " Sending...";
        this.submitButton.insertAdjacentHTML('afterend', loader);
        // Get the form field values
        const formData = {
            name: this.nameInput.value,
            email: this.emailInput.value,
            subject: this.subjectInput.value,
            message: this.messageTextarea.value,
            reff: window.location.href
        };

        // formData.message += `\n\n Reference: ${window.location.href}`;
        var data = {
            action: "contactActionAjax",
            data: formData,
        };
        jQuery.post(contactAjaxObj.ajaxurl, data, (response) => {
            jQuery(".spinLoader").remove();
            var obj = JSON.parse(response);
            this.contactMsg.innerHTML = obj.message;
            if (obj.error === false) {
                this.formElement.reset();
                this.contactMsg.style.color = "green"
                this.submitButton.innerHTML = " Sent !";
                setTimeout(() => {
                    this.handleClose();
                }, 2000);
            } else {
                this.contactMsg.style.color = "red";
                this.submitButton.innerHTML = "Try Again";
            }
        });
    }

    buildDOM() {
        // Create the form element
        this.formElement = document.createElement('form');
        this.formElement.id = 'contactForm';
        this.formElement.className = 'write-us-form';
        // Add event listener to the form submit event
        this.formElement.addEventListener('submit', this.handleSubmit.bind(this));

        // Create the name input element
        const div1 = document.createElement('div');
        div1.className = 'input-wrap gac-name-in';
        const nameLabel = document.createElement('label');
        nameLabel.textContent = 'Your Name (required)';
        this.nameInput = document.createElement('input');
        this.nameInput.name = 'name';
        this.nameInput.type = 'text';
        this.nameInput.className = 'contactFormField';
        this.nameInput.required = true;
        div1.appendChild(nameLabel);
        div1.appendChild(this.nameInput);

        // Create the email input element
        const div2 = document.createElement('div');
        div2.className = 'input-wrap gac-email-in';
        const emailLabel = document.createElement('label');
        emailLabel.textContent = 'Your Email (required)';
        this.emailInput = document.createElement('input');
        this.emailInput.name = 'email';
        this.emailInput.type = 'email';
        this.emailInput.className = 'contactFormField';
        this.emailInput.required = true;
        div2.appendChild(emailLabel);
        div2.appendChild(this.emailInput);

        // Create the subject input element
        const div3 = document.createElement('div');
        div3.className = 'input-wrap gac-subject-in';
        console.log(window.innerWidth);
        if (window.innerWidth > 580) {
            div3.classList.add('hide');
        }
        const subjectLabel = document.createElement('label');
        subjectLabel.textContent = 'Subject';
        this.subjectInput = document.createElement('input');
        this.subjectInput.name = 'subject';
        this.subjectInput.type = 'text';
        this.subjectInput.className = 'contactFormField';
        div3.appendChild(subjectLabel);
        div3.appendChild(this.subjectInput);

        // Create the message textarea element
        const div4 = document.createElement('div');
        div4.className = 'input-wrap gac-message-in';
        const messageLabel = document.createElement('label');
        messageLabel.textContent = 'Message';
        this.messageTextarea = document.createElement('textarea');
        this.messageTextarea.className = 'contactFormField';
        this.messageTextarea.name = 'message';
        this.messageTextarea.required = true;
        div4.appendChild(messageLabel);
        div4.appendChild(this.messageTextarea);

        // Create the question input element
        const divQuestion = document.createElement('div');
        divQuestion.className = 'question';
        this.questionLabel = document.createElement('label');
        this.questionLabel.id = 'question';
        this.questionLabel.textContent = this.n1 + ' + ' + this.n2 + '=';
        this.answerInput = document.createElement('input');
        this.answerInput.className = 'contactFormField';
        this.answerInput.addEventListener('keyup', this.metch.bind(this));
        divQuestion.appendChild(this.questionLabel);
        divQuestion.appendChild(this.answerInput);

        // Create the footer elements
        const divFooter = document.createElement('div');
        divFooter.className = 'contact-footer';
        this.submitButton = document.createElement('button');
        this.submitButton.type = 'submit';
        this.submitButton.id = 'submitBtn';
        this.submitButton.className = 'contactFormButton';
        this.submitButton.textContent = 'Send';
        this.contactMsg = document.createElement('span');
        this.contactMsg.className = 'contactMsg';

        // Create and add the close button
        const closeButton = document.createElement('span');
        closeButton.className = 'closeButton';
        closeButton.innerHTML = '&times;';
        // Store the references to the form element and close button
        this.closeButton = closeButton;
        // Attach close handler to the close button
        this.closeButton.addEventListener('click', this.handleClose.bind(this));

        divFooter.appendChild(this.submitButton);
        divFooter.appendChild(this.contactMsg);




        // Append all the elements to the form element
        this.formElement.appendChild(this.closeButton);
        this.formElement.appendChild(div1);
        this.formElement.appendChild(div2);
        this.formElement.appendChild(div3);
        this.formElement.appendChild(div4);
        this.formElement.appendChild(divQuestion);
        this.formElement.appendChild(divFooter);

        // Append the form element to the desired location in the DOM
        this.dom.appendChild(this.formElement);
        this.body.appendChild(this.dom);
    }

    handleClose() {
        this.dom.remove(); // Remove the form element
    }

    getRandom() {
        return Math.ceil(Math.random() * 20)
    }
}

function writeus() {
    new WriteUs();
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
    var loader = "<span class='spinLoader'><i></i><i></i><i></i><i></i><i></i><i></i></span>";
    var loaderBig = "<span class='bodyLoader'></span>";
    jQuery("#contactForm").submit(function (e) {
        jQuery("#submitBtn").attr('type', 'button');//To Prevent Resend when already Processing
        jQuery("#submitBtn").html(" Sending...");
        jQuery("#submitBtn").after(loader);
        e.preventDefault();
        var data = {
            action: "contactActionAjax",
            data: jQuery("#contactForm").serialize(),
        };
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(contactAjaxObj.ajaxurl, data, function (response) {
            jQuery(".spinLoader").remove();
            var obj = JSON.parse(response);
            if (obj.error === false) {
                jQuery('#contactForm')[0].reset();
                jQuery(".contactMsg").html(obj.message).css("color", "green");
                jQuery("#submitBtn").html(" Sent !");
                setTimeout(function () {
                    jQuery("#submitBtn").html(" Send ");
                }, 2000)
            } else {
                jQuery(".contactMsg").html(obj.message).css("color", "red");
                jQuery("#submitBtn").attr('type', 'submit');
            }
        });
    })
}

function trigFloated(_this) {
    let act = jQuery(_this);
    if (act.attr('data-action') == 'open') {
        iconHtm = act.html();
        jQuery('.floated-contact-form-wrap').addClass('open');
        act.attr('data-action', 'close');
        act.html('<span class="close-floated"></span>');
    } else {
        jQuery('.floated-contact-form-wrap').removeClass('open');
        act.attr('data-action', 'open');
        act.html(iconHtm);
    }
}