// Templates for adding a new input field for product specification input
var valueTemplate = '<input name="specsValue[]" type="text" class="form-control my-2"/>';
var labelTemplate = '<input name="specsLabel[]" type="text" class="form-control my-2"/>';

$( document ).ready(function() {
    // Show product creation tab as default
    $('#product_create').show();
    // Event for clicking on Add new (specification) in product creation tab
    $('#add_new_spec').click(function(){
        $('#inputProductSpecificationsLabel').append(labelTemplate);
        $('#inputProductSpecificationsValue').append(valueTemplate);
    });

    // Event for changing selected order status in orders tab
    $('.order-status-select').change(function(){
        // Save order id and new status in variables
        let orderId = $(this).data('id');
        let orderStatus = $(this).val();
        $('#store_orders .feedback').removeClass('feedback-fade-in').removeClass('bg-danger').removeClass('text-update-success').addClass('feedback-hide');
        // Perform ajax call to update_order.php with order id and new status
        $.ajax({
            url: "admin/update_order.php",
            method: "POST",
            dataType: "json",
            data: {
                'id': orderId,
                'status': orderStatus,
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
});