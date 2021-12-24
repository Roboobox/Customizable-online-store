var formError = false;

$( document ).ready(function() {
    // Event for submitting contact message form
    $('#contactForm').submit(function( event ){
        event.preventDefault();
        resetFormMessages();

        // Get fields
        let messageField = $('#message_textarea');
        let emailField = $('#email_input');
        let messageError = $('#message_error');
        let emailError = $('#email_error');
        let emailInput = emailField.val();
        let messageInput = messageField.val();

        // Check message length
        if (messageInput.length === 0) {
            showError(messageError, messageField, 'Message is required!');
        } else if (messageInput.length > 65535) {
            showError(messageError, messageField, 'Message cannot exceed 65,535 characters!');
        }

        // Check email length
        if (emailInput.length === 0) {
            showError(emailError, emailField, 'Email is required!');
        } else if (emailInput.length > 254) {
            showError(emailError, emailField, 'Email cannot exceed 254 characters!');
        } else if (!(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput))) {
          //Simple email regex validation, advanced validation at server side
          showError(emailError, emailField, 'Email address is not valid!')
        }

        // If there are no validation errors then make AJAX call to save message in database
        if (!formError) {
            showLoading();
            $.ajax({
                url: "ajax/send_message.php",
                method: "POST",
                dataType: "json",
                data: {'email': emailInput, 'message': messageInput, 'token': $('#contactForm #csrf_token').val()},
                success: function (data) {
                    if (data['status'] === 'success') {
                        // Check for server validation errors and show them
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

// Display error message
function showError(errorField, inputField, errorMessage) {
    errorField.text(errorMessage).show();
    inputField.addClass('is-invalid');
    formError = true;
}
// Remove all error and success messages
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
