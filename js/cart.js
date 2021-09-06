function getCart(cartItems) {
    console.log('hey');
    $.ajax({
        url: "get_cart.php",
        method: "POST",
        dataType: "json",
        data: {'cartItems' : cartItems},
        success: function (data) {
            console.log(data);
            $('.cart-rows').html(data['cart']);
            $('.cart-footer').html(data['footer']);
        },
        error: function () {

        }
    });
}