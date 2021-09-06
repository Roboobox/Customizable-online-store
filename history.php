<?php
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/purchase_history.css?<?=time()?>" rel="stylesheet">
<div class="container">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Order history</h2>
    </div>
    <div class="row">
        <div class="order-container">
            
            <div class="order border border-bottom-0 shadow-sm p-4 d-flex justify-content-between flex-wrap" onclick="location.href='order.php'">
                <div class="order-number order-info">
                    <div class="text-muted order-label">Order number:</div>
                    <div class="fw-bold">KJFJKEFHJ392K</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Date:</div>
                    <div class="fw-bold text-muted">25.07.2021</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Total:</div>
                    <div class="fw-bold">34.50 €</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Status:</div>
                    <div class="order-status-container yellow-label">In transit</div>
                </div>
            </div>
            
            <div class="order border shadow-sm p-4 d-flex justify-content-between flex-wrap">
                <div class="order-number order-info">
                    <div class="text-muted order-label">Order number:</div>
                    <div class="fw-bold">JKEDFH38FJ32J</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Date:</div>
                    <div class="fw-bold text-muted">02.05.2019</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Total:</div>
                    <div class="fw-bold">67.39 €</div>
                </div>
                <div class="order-info">
                    <div class="text-muted order-label">Status:</div>
                    <div class="order-status-container green-label">Completed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
