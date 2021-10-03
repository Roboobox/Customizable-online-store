function getCartSummary() {
    $.ajax({
        url: "ajax/get_cart.php",
        method: "POST",
        dataType: "json",
        data: {'cart_summary_html' : true},
        success: function (data) {
            $('.cart-summary').html(data['cart_summary']);
            $('.order-review-sum').html(data['cart_total']);
        },
        error: function() {
        }
    });
}

function validateCheckout() {
    
}

$( document ).ready(function() {
    $('.cc-options').hide();
    $('.form-check input[name="paymentMethod"]').change(function() {
        let inputField = $(this);
        $('.cc-options').hide();
        if (inputField.attr("id") == "credit") {
            $('.cc-options').show();
        }
    });
});

(function () {
    'use strict';

    window.addEventListener('load', function () {
        var forms = document.getElementsByClassName('needs-validation');
        
        var validation = Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();