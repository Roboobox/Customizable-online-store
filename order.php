<?php
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/order.css?<?=time()?>" rel="stylesheet">
<div class="container order-container">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Order overview</h2>
    </div>
    <div class="row border order-info mb-5">
        <div class="order-top px-4 py-3 bg-light d-flex">
            <div class="d-inline-block">
            <h4 class="w-100">Order nr. KJFJKEFHJ392K</h4>
            <div class="text-muted d-inline-block">Date:</div>
            <div class="fw-bold text-muted d-inline-block">02.05.2019</div>
            </div>
            <div class="d-inline-block ms-3">
            <div class="order-status-container yellow-label">In transit</div>
            </div>
        </div>
        <div class="order-content p-4">
            <div  class="d-flex flex-wrap">
                <div class="order-payment-info order-info-container">
                    <div class="order-info-label text-muted text-center mb-1 border-bottom">
                        Payment information
                    </div>
                    <ul class="list-unstyled">
                        <li>
                            Credit card payment
                        </li>
                        <li>
                            Visa
                        </li>
                        <li>
                            XXXX-XXXX-XXXX-1111
                        </li>
                        <li>Total: 20.00$</li>
                    </ul>
                </div>
                <div class="order-delivery-info order-info-container">
                    <div class="order-info-label text-muted text-center mb-1 border-bottom">
                        Delivery information
                    </div>
                    <ul class="list-unstyled">
                        <li>
                            Latvia
                        </li>
                        <li>
                            Riga
                        </li>
                        <li>
                            LV-1009
                        </li>
                        <li>
                            Pērnavas street 30
                        </li>
                        <li>
                            29498293
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="order-items d-block mt-3">
                <span class="fw-bold fs-4 mb-3 d-block">Items:</span>
                <div class="order-item bg-light p-3 row border-bottom">
                    <div class="col-md text-center text-md-start my-1 my-md-0">
                        <img src="test_images/acme5.png" height="90" width="90" class="d-inline-block" alt="...">
                    </div>
                    <div class="d-inline-block px-3 product-title col-md my-1 my-md-0 w-100">
                        <span class="text-muted">Product:</span>
                        <div class="d-inline-block d-md-block">Cleaning tissues Acme CL02</div>
                    </div>
                    <div class="d-inline-block px-3 col-md my-1 my-md-0">
                        <span class="text-muted">Quantity:</span>
                        <div class="d-inline-block d-md-block">2 pcs.</div>
                    </div>
                    <div class="d-inline-block px-3 col-md my-1 my-md-0">
                        <span class="text-muted">Price per piece:</span>
                        <div class="d-inline-block d-md-block">8.00$</div>
                    </div>
                    <div class="d-inline-block px-3 col-md my-1 my-md-0">
                        <span class="text-muted">Total:</span>
                        <div class="fw-bold d-inline-block d-md-block">16.00$</div>
                    </div>
                </div>
                
                <div class="order-item bg-light p-3 row">
                    <div class="col-md text-center text-md-start my-1 my-md-0">
                        <img src="test_images/inakustik.png" height="90" width="90" class="d-inline-block" alt="...">
                    </div>
                    <div class="d-inline-block px-3 item-info col-md my-1 my-md-0 w-100">
                        <span class="text-muted">Product:</span>
                        <div class="d-inline-block d-md-block">Inakustik 004528002 screen-clean</div>
                    </div>
                    <div class="d-inline-block px-3 item-info col-md my-1 my-md-0">
                        <span class="text-muted">Quantity:</span>
                        <div class="d-inline-block d-md-block">1 pc.</div>
                    </div>
                    <div class="d-inline-block px-3 item-info col-md my-1 my-md-0">
                        <span class="text-muted">Price per piece</span>
                        <div class="d-inline-block d-md-block">2.00$</div>
                    </div>
                    <div class="d-inline-block px-3 item-info col-md my-1 my-md-0">
                        <span class="text-muted">Total</span>
                        <div class="fw-bold d-inline-block d-md-block">4.00$</div>
                    </div>
                </div>
            </div>
            <div class="text-end my-2 fs-5 me-3">
                <div>Total:</div>
                <div class="fw-bold">20.00$</div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
