var formError = false;

$( document ).ready(function() {
    $('#contactForm').submit(function( event ){
        event.preventDefault();
        resetFormMessages();

        let messageField = $('#message_textarea');
        let emailField = $('#email_input');
        let messageError = $('#message_error');
        let emailError = $('#email_error');
        let emailInput = emailField.val();
        let messageInput = messageField.val();

        if (messageInput.length === 0) {
            console.log('here')
            showError(messageError, messageField, 'Message is required!');
        } else if (messageInput.length > 65535) {
            showError(messageError, messageField, 'Message cannot exceed 65,535 characters!');
        }

        if (emailInput.length === 0) {
            showError(emailError, emailField, 'Email is required!');
        } else if (emailInput.length > 255) {
            showError(emailError, emailField, 'Email cannot exceed 255 characters!');
        } else if (!(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput))) {
          //Simple email regex validation, advanced validation at server side
          showError(emailError, emailField, 'Email address is not valid!')
        }

        if (!formError) {
            showLoading();
            $.ajax({
                url: "ajax/send_message.php",
                method: "POST",
                dataType: "json",
                data: {'email': emailInput, 'message': messageInput},
                success: function (data) {
                    if (data['status'] === 'success') {
                        if ('email' in data) {
                            showError(emailError, emailField, data['email']);
                        } else if ('message' in data) {
                            showError(messageError, messageField, data['message']);
                        } else {
                            $('#form_success').text('Message successfully sent!').show();
                            $('.form-control').val('');
                        }
                    } else {
                        $('#general_error').text("Something went wrong, try again later!").show();
                    }
                    hideLoading();
                },
                error: function () {
                    $('#general_error').text("Something went wrong, try again later!").show();
                    hideLoading();
                }
            });
        }
    });
});

function showError(errorField, inputField, errorMessage) {
    errorField.text(errorMessage).show();
    inputField.addClass('is-invalid');
    formError = true;
}

function resetFormMessages() {
    $('.invalid-feedback').text('').hide();
    $('.form-control').removeClass('is-invalid');
    $('#form_success').text('').hide();
    formError = false;
}

function showLoading() {
    $('.btn-contact-send').prop('disabled', true);
    $('#button_text').hide();
    $('#loading').removeClass('d-none');
}

function hideLoading() {
    $('.btn-contact-send').prop('disabled', false);
    $('#loading').addClass('d-none');
    $('#button_text').show();
}
