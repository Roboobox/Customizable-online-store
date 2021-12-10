const MAX_QUANTITY = 100000;
var cartId;
var isCartLoading = false;

// Gets cart data
function getCart() {
    showLoadingOverlay();
    // Perform ajax call to get_cart.php for cart HTML
    $.ajax({
        url: "ajax/get_cart.php",
        method: "POST",
        dataType: "json",
        data: {'cart_html' : true, 'output': true},
        success: function (data) {
            // Set html and update header cart info
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

// Set events for elements created after AJAX call
function setEvents() {
    // Event for clicking continue button in cart page
    $('.cart-continue').click(function () {
        if (!isCartLoading) {
            location.href = 'checkout.php';
        } 
    });
    // Event for clicking quantity reduction button (minus)
    $('.quantity-picker-container .minus').click(function () {
        var inputField = $(this).parent().children('input[type=number]');
        // Check if quantity is at least above 1
        if (inputField.val() > 1) {
            inputField[0].stepDown();
            updateCart(inputField.data('product'), inputField.val());
        }
    });
    // Event for clicking quantity addition button (plus)
    $('.quantity-picker-container .plus').click(function () {
        var inputField = $(this).parent().children('input[type=number]');
        if (inputField.val() < MAX_QUANTITY) {
            inputField[0].stepUp();
            updateCart(inputField.data('product'), inputField.val());
        }
    });
    // Event for clicking cart item remove button
    $('.btn-cart-remove').click(function () {
        deleteCartItem($(this).data('product'), $(this).data('token'), $(this).data('cart'));
    });

    // Event for inputting a new value in cart items quantity field
    $('.quantity-picker-container input[type=number]').change(function () {
        let inputField = $(this);
        let quantity = inputField.val();
        // Check if quantity is not below one or higher than max
        if (quantity < 1) {
            inputField.val(1);
        }
        else if (quantity > MAX_QUANTITY) {
            inputField.val(MAX_QUANTITY);
        }
        updateCart(inputField.data('product'), inputField.val());
    });
}

// Make AJAX call to update cart items quantity in database
function updateCart(productId, productQuantity) {
    if (productQuantity > 0) {
        showLoadingOverlay();
        $.ajax({
            url: "ajax/update_cart.php",
            method: "POST",
            dataType: "json",
            data: {'product_id' : productId, 'quantity' : productQuantity},
            success: function (data) {
                // Update displayed cart
                if (data['status'] === 'success') {
                    getCart();
                } else {
                    hideLoadingOverlay();
                }
            },
            error: function () {
                hideLoadingOverlay();
            }
        });
    }
}

// Make AJAX call to remove cart item from users cart in database
function deleteCartItem(productId, userToken, cartId) {
    showLoadingOverlay();
    $.ajax({
        url: "ajax/cart_remove_item.php",
        method: "POST",
        dataType: "json",
        data: {'product_id' : productId, 'token' : userToken, 'cart_id' : cartId},
        success: function (data) {
            // Update displayed cart
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

