// Make AJAX call to get cart summary HTML
function getCartSummary() {
    $.ajax({
        url: "ajax/get_cart.php",
        method: "POST",
        dataType: "json",
        data: {'cart_summary_html' : true, 'output' : true},
        success: function (data) {
            $('.cart-summary').html(data['cart_summary']);
            $('.order-review-sum').html(data['cart_total']);
        },
        error: function() {
        }
    });
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