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
        $('#store_orders .feedback').addClass('no-transition').css('opacity', 0)
        let orderId = $(this).data('id');
        let orderStatus = $(this).val();
        $.ajax({
            url: "admin/update_order.php",
            method: "POST",
            dataType: "json",
            data: {
                'id': orderId,
                'status': orderStatus,
            },
            success: function (data) {
                $('#store_orders .feedback').removeClass('no-transition')
                console.log(data);
                if (data['status'] == 'success') {
                    $('#store_orders .order_success').text('Order #' + orderId + ' status updated!').css('opacity', 1);
                } else {
                    $('#store_orders .order_error').css('opacity', 1);
                }
            },
            error: function()
            {
                console.log('e');
                $('#store_orders .order_error').css('opacity', 1);
            }
        });
    });
    // $('#btn_product_create').click(function(){
    //     $('.list-group-item').removeClass('active');
    //     $(this).addClass('active');
    //     $('section').hide();
    //     $('#product_create').show();
    // });
    // $('#btn_product_delete').click(function(){
    //     $('.list-group-item').removeClass('active');
    //     $(this).addClass('active');
    //     $('section').hide();
    //     $('#product_delete').show();
    // });
    // $('#btn_store_orders').click(function(){
    //     $('.list-group-item').removeClass('active');
    //     $(this).addClass('active');
    //     $('section').hide();
    //     $('#store_orders').show();
    // });
    // $('#btn_store_settings').click(function(){
    //     $('.list-group-item').removeClass('active');
    //     $(this).addClass('active');
    //     $('section').hide();
    //     $('#store_settings').show();
    // });
    // $('#btn_contact_messages').click(function(){
    //     $('.list-group-item').removeClass('active');
    //     $(this).addClass('active');
    //     $('section').hide();
    //     $('#contact_messages').show();
    // });
});