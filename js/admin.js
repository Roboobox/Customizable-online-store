// tabindex counter and group counter for specification input elements
var specTabIndex = 3;
var specTabGroup = 2;

// Create input field for specification label
function createSpecLabel(value, tabIndex, groupIndex) {
    return '<input value="'+escapeTags(value)+'" data-group="'+groupIndex+'" tabindex="'+tabIndex+'" name="specsLabel[]" type="text" class="form-control mt-2"/>';
}
// Create input field for specification value
function createSpecValue(value, tabIndex, groupIndex) {
    return '<input value="'+escapeTags(value)+'" data-group="'+groupIndex+'" tabindex="'+tabIndex+'" name="specsValue[]" type="text" class="form-control mt-2"/>';
}
// Create button for specification row deletion
function createSpecDel(groupIndex) {
    return '<button type="button" data-group="'+groupIndex+'" class="btn btn-danger mt-2 w-100"><i class="fas fa-trash-alt"></i><span class="d-none">X</span></button>';
}

function escapeTags(text) {
    if (!text) return '';
    return text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

// Create click events for product create and edit forms
function setProductEvents() {
    // Event for clicking on delete specification row button, removes the input fields and button
    $('#inputSpecRemove button').unbind('click').click(function () {
        let groupId = $(this).data('group');
        $('#inputProductSpecificationsLabel input[data-group="' + groupId + '"]').remove();
        $('#inputProductSpecificationsValue input[data-group="' + groupId + '"]').remove();
        $(this).remove();
    });
    // Event for clicking on product image to delete it, highlights image and sets input as checked and vice versa
    $('#product_edit .product-image-container .product-image').unbind('click').click(function () {
        let image = $(this);
        let checkInput = image.find('input[type="checkbox"]');
        if (image.hasClass('delete')) {
            image.removeClass('delete');
            checkInput.prop('checked', false);
        } else {
            image.addClass('delete');
            checkInput.prop('checked', true);
        }
    });
}

function clearEditProductFeedback() {
    $('#product_edit input').removeClass('is-invalid');
    $('#product_edit .invalid-feedback').text('');
    $('#product_edit .edit-error').removeClass('feedback-fade-in').addClass('d-none').text('');
    $('#product_edit .edit-success').removeClass('feedback-fade-in').addClass('d-none').text('');
    $('#product_edit .spec-error').addClass('d-none').text('');
}

$( document ).ready(function() {
    // Show product creation tab as default
    $('#product_create').show();
    setProductEvents();
    // Event for clicking on Add new (specification) in product creation tab
    $('#add_new_spec').click(function(){
        $('#inputProductSpecificationsLabel').append(createSpecLabel('', specTabIndex, specTabGroup));
        $('#inputProductSpecificationsValue').append(createSpecValue('', specTabIndex, specTabGroup));
        $('#inputSpecRemove').append(createSpecDel(specTabGroup));
        specTabIndex += 2;
        specTabGroup++;
        // Set events on created input fields
        setProductEvents();
    });

    // Event for changing selected order status in orders tab
    $('.order-status-select').change(function(){
        // Save order id and new status in variables
        let orderId = $(this).data('id');
        let orderStatus = $(this).val();
        let token = $('#store_orders #csrf_token').val();
        $('#store_orders .feedback').removeClass('feedback-fade-in').removeClass('bg-danger').removeClass('text-update-success').addClass('feedback-hide');
        // Perform ajax call to update_order.php with order id and new status
        $.ajax({
            url: "admin/update_order.php",
            method: "POST",
            dataType: "json",
            data: {
                'id': orderId,
                'status': orderStatus,
                'token' : token,
            },
            success: function (data) {
                // Show resulting feedback
                if (data['status'] == 'success') {
                    $('#store_orders .feedback').text('Order #' + orderId + ' status updated!').removeClass('feedback-hide').addClass('feedback-fade-in').addClass('text-update-success');
                } else {
                    $('#store_orders .feedback').text('Order status failed to update!').removeClass('feedback-hide').addClass('feedback-fade-in').addClass('bg-danger');
                }
            },
            error: function()
            {
                $('#store_orders .feedback').text('Order status failed to update!').removeClass('feedback-hide').addClass('feedback-fade-in').addClass('bg-danger');
            }
        });
    });

    $('#product_edit .product-edit-select').change(function () {
        // Clear feedback and input fields
        clearEditProductFeedback();
        $('#product_edit input:not([name="prodEdit"]):not(#csrf_token)').val('');
        // Get selected product
        let productId = $('#product_edit .product-edit-select option:selected').val();
        if (productId === '') {
            $('#edit_product_container').addClass('d-none');
        } else {
            // Ajax request to get product data and fill input fields
            $.ajax({
                url: "admin/get_edit_product.php",
                method: "POST",
                dataType: "json",
                data: {
                    'id': productId
                },
                success: function (data) {
                    if (data['status'] == 'success' && data['product'] !== undefined) {
                        // Fill input fields with product data
                        if (data['product']['name']) $('#inputProductName').val(data['product']['name']);
                        if (data['product']['category']) $('#inputProductCategory').val(data['product']['category']);
                        if (data['product']['price']) $('#inputProductPrice').val(data['product']['price']);
                        if (data['product']['description']) $('#inputProductDescription').text(data['product']['description']);
                        if (data['product']['inventory']) $('#inputProductInventory').val(data['product']['inventory']);
                        // Display current product photos
                        if (data['product']['photos']) {
                            let photoHtml = '';
                            for (let photo of data['product']['photos']) {
                                photoHtml += '<div class="product-image border rounded p-2 pe-4 d-inline-block me-2 mb-2">';
                                photoHtml += '<img alt="Product image" src="images/' + photo['photo_path'] + '" height="100px">';
                                photoHtml += '<div class="delete-image position-absolute p-0 text-danger"><i class="far fa-trash-alt"></i></div>';
                                photoHtml += '<input class="d-none" name="imgDelete[]" value="' + photo['id'] + '" type="checkbox"/></div></div>';
                            }
                            $('.product-image-container').html(photoHtml);
                        }
                        if (data['product']['specifications']) {
                            // Clear specification inputs
                            $('#inputProductSpecificationsLabel input').remove();
                            $('#inputProductSpecificationsValue input').remove();
                            $('#inputSpecRemove button').remove();
                            let tabIndex = 1;
                            let groupIndex = 1;
                            // Create new input fields for products specifications
                            for (let spec of data['product']['specifications']) {
                                $('#inputProductSpecificationsLabel').append(createSpecLabel(spec['label'], tabIndex, groupIndex));
                                $('#inputProductSpecificationsValue').append(createSpecValue(spec['info'], tabIndex + 1, groupIndex));
                                $('#inputSpecRemove').append(createSpecDel(groupIndex));
                                tabIndex += 2;
                                groupIndex++;
                            }
                            specTabIndex = tabIndex;
                            specTabGroup = groupIndex;
                        }
                        // Set events on input elements
                        setProductEvents();
                        $('#edit_product_container').removeClass('d-none');
                    } else {
                        $('#product_edit .edit-error').removeClass('d-none').addClass('feedback-fade-in').text('Something went wrong, try again later!');
                        $('#edit_product_container').addClass('d-none');
                    }
                },
                error: function () {
                    $('#product_edit .edit-error').removeClass('d-none').addClass('feedback-fade-in').text('Something went wrong, try again later!');
                    $('#edit_product_container').addClass('d-none');
                }
            });
        }
    });

    // Event for clicking on product edit form save(submit) button
    $('#product_edit form').submit(function (event) {
        event.preventDefault();
        // Clear all feedback and show loading icon in button
        clearEditProductFeedback();
        $('#edit_submit span').addClass('d-none');
        $('#edit_submit i').removeClass('d-none');
        // Ajax request to send form data and update form
        $.ajax({
            url: "admin/edit_product.php",
            method: "POST",
            dataType: "json",
            contentType: false,
            processData: false,
            data: new FormData($(this)[0]),
            success: function (data) {
                if (data['formErrors'] !== undefined) {
                    // Show error messages
                    let firstError = undefined;
                    for (let error in data['formErrors']) {
                        let errorInput = $('[name^="'+error+'"]');
                        // Save first encountered error input field
                        if (firstError === undefined) firstError = errorInput
                        // After finding input search next elements till first invalid-feeback element is found where error message can be shown
                        if (error !== 'specs') {
                            errorInput.addClass('is-invalid').nextAll('div.invalid-feedback').first().text(data['formErrors'][error]);
                        } else {
                            $('#product_edit .spec-error').removeClass('d-none').text(data['formErrors'][error]);
                        }
                    }
                    if (firstError !== undefined) {
                        // Scroll to first error input field
                        $([document.documentElement, document.body]).animate({
                            scrollTop: firstError.offset().top - $('.nav-bot').outerHeight() - 50
                        }, 100);
                    }
                } else if (data['status'] !== undefined && data['status'] === 'success') {
                    // Update product name and fire change event on product selector to refresh form
                    let newProductName = $('#product_edit form input[name="prodName"]').val();
                    if (newProductName !== undefined) $('#product_edit .product-edit-select option:selected').text(newProductName);
                    $('#product_edit .product-edit-select').change();
                    // Scroll to form top
                    $([document.documentElement, document.body]).animate({
                        scrollTop: $("#admin_header").offset().top
                    }, 100);
                    // Show success message
                    if (data['formSuccess'] !== undefined) {
                        $('#product_edit .edit-success').removeClass('d-none').addClass('feedback-fade-in').text(data['formSuccess']);
                    }
                } else {
                    $('#product_edit .edit-error').removeClass('d-none').addClass('feedback-fade-in').text('Something went wrong, try again later!');
                    $('#edit_product_container').addClass('d-none');
                }
                // Hide loading
                $('#edit_submit span').removeClass('d-none');
                $('#edit_submit i').addClass('d-none');
            },
            error: function()
            {
                $('#product_edit .edit-error').removeClass('d-none').addClass('feedback-fade-in').text('Something went wrong, try again later!');
                $('#edit_product_container').addClass('d-none');
                // Hide loading
                $('#edit_submit span').removeClass('d-none');
                $('#edit_submit i').addClass('d-none');
            }
        });
    })
});