const MAX_QUANTITY = 100000;
var cartId;
var isCartLoading = false;

function getCart() {
    showLoadingOverlay();
    $.ajax({
        url: "ajax/get_cart.php",
        method: "POST",
        dataType: "json",
        data: {'cart_html' : true, 'output': true},
        success: function (data) {
            $('.cart-rows').html(data['cart']);
            $('.cart-footer').html(data['footer']);
            $('.cart-info-container .cart-count-container').text(Object.keys(data['cart_items']).length);
            $('.cart-info-container .cart-text-container').text(data['cart_total']);
            setEvents();
            hideLoadingOverlay();
        },
        error: function() {
            hideLoadingOverlay();
        }
    });
}

function setEvents() {
    $('.cart-continue').click(function () {
        if (!isCartLoading) {
            location.href = 'checkout.php';
        } 
    });
    $('.quantity-picker-container .minus').click(function () {
        var inputField = $(this).parent().children('input[type=number]');
        if (inputField.val() > 1) {
            inputField[0].stepDown();
            updateCart(inputField.data('product'), inputField.val());
        }
    });
    $('.quantity-picker-container .plus').click(function () {
        var inputField = $(this).parent().children('input[type=number]');
        if (inputField.val() < MAX_QUANTITY) {
            inputField[0].stepUp();
            updateCart(inputField.data('product'), inputField.val());
        }
    });
    
    $('.btn-cart-remove').click(function () {
        deleteCartItem($(this).data('product'), $(this).data('token'), $(this).data('cart'));
       //$(this).data('product');
    });
    
    $('.quantity-picker-container input[type=number]').change(function () {
        let inputField = $(this);
        let quantity = inputField.val();
        if (quantity < 1) {
            inputField.val(1);
        }
        else if (quantity > MAX_QUANTITY) {
            inputField.val(MAX_QUANTITY);
        }
        updateCart(inputField.data('product'), inputField.val());
    });
    
    //$('.quantity-picker-container
}

function updateCart(productId, productQuantity) {
    showLoadingOverlay();
    $.ajax({
        url: "ajax/update_cart.php",
        method: "POST",
        dataType: "json",
        data: {'product_id' : productId, 'quantity' : productQuantity},
        success: function (data) {
            if (data['status'] === 'success') {
                getCart();
            }
        },
        error: function () {
            hideLoadingOverlay();
        }
    });
}

function deleteCartItem(productId, userToken, cartId) {
    showLoadingOverlay();
    $.ajax({
        url: "ajax/cart_remove_item.php",
        method: "POST",
        dataType: "json",
        data: {'product_id' : productId, 'token' : userToken, 'cart_id' : cartId},
        success: function (data) {
            if (data['status'] === 'success') {
                getCart();
            }
        },
        error: function () {
            hideLoadingOverlay();
        }
    });
}

function showLoadingOverlay() {
    isCartLoading = true;
    $('.cart-overlay').show();
}

function hideLoadingOverlay() {
    isCartLoading = false;
    $('.cart-overlay').hide();
}

