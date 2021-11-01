var valueTemplate = '<input name="specsValue[]" type="text" class="form-control my-2"/>';
var labelTemplate = '<input name="specsLabel[]" type="text" class="form-control my-2"/>';
       
$( document ).ready(function() {
    // $('section').hide();
    $('#product_create').show();
    $('#add_new_spec').click(function(){
        $('#inputProductSpecificationsLabel').append(labelTemplate);
        $('#inputProductSpecificationsValue').append(valueTemplate);
    });

    $('.order-status-select').change(function(){
        let orderId = $(this).data('id');
        let orderStatus = $(this).val();
        $('#store_orders .feedback').removeClass('feedback-fade-in').removeClass('bg-danger').removeClass('text-update-success').addClass('feedback-hide');
        $.ajax({
            url: "admin/update_order.php",
            method: "POST",
            dataType: "json",
            data: {
                'id': orderId,
                'status': orderStatus,
            },
            success: function (data) {
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