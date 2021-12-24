<?php
include_once 'head.php';
include_once 'header.php';
?>
<script type="text/javascript" src="./js/cart.js?v=6"></script>
<script type="text/javascript">
    $( document ).ready(function() {
        getCart();
    });
</script>

<link href="css/cart.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-md-5 mt-4 mb-4">Cart</h2>
    </div>
    <div class="row">
        <div class="cart-container position-relative">
            <div class="cart-overlay position-absolute w-100 h-100 top-0 start-0"><div class="d-flex h-100 w-100 align-items-center justify-content-center"><i class="spinner-border text-muted"></div></i></div>
            <div class="cart-container">
                <div class="row bg-light p-3 d-md-flex d-none text-muted border border-bottom-0">
                    <div class="col"></div>
                    <div class="col">
                        Product
                    </div>
                    <div class="col">
                        Price
                    </div>
                    <div class="col">
                        Quantity
                    </div>
                    <div class="col">
                        Total
                    </div>
                </div>
                
                <div class="cart-rows"></div>
        
            </div>
            
            <div class="cart-footer text-end fs-4 mt-3 d-flex align-items-center justify-content-end"></div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
