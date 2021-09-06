<?php
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/checkout.css?<?=time()?>" rel="stylesheet">
<div class="container">
<!--    <div class="row">-->
<!--        <h2 class="w-100 mt-5 mb-4">Contact us</h2>-->
<!--    </div>-->
    <div class="my-md-5 my-0">
        <div class="row p-3 border border-bottom-0">
            <div class="progress-container d-flex justify-content-center">
                <div class="progress-container d-inline-block text-center">
                    <div class="progress-icon progress-first m-auto active">
                        1
                    </div>
                    <span class="d-block">Details</span>
                </div>
                <hr>
                <div class="progress-container d-inline-block text-center">
                    <div class="progress-icon progress-first m-auto">
                        2
                    </div>
                    <span class="d-block">Review</span>
                </div>
                <hr>
                <div class="progress-container d-inline-block text-center">
                    <div class="progress-icon progress-first m-auto">
                        3
                    </div>
                    <span class="d-block">Finish</span>
                </div>
            </div>
        </div>
        <div class="row bg-light border p-4">
            <div class="col-12">
                <div class="row">
                    <div class="col-md-7 col-lg-8 pe-0 pe-md-5">
                        <form>
                            <div><h4 class="mt-md-2 mt-4">Billing details</h4></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First name</label>
                                    <input type="text" name="billing_name" class="form-control" id="firstName" placeholder="" value="" required="">
                                    <div class="invalid-feedback">
                                        Valid first name is required.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" name="billing_surname" class="form-control" id="lastName" placeholder="" value="" required="">
                                    <div class="invalid-feedback">
                                        Valid last name is required.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email<span class="text-muted">(Optional)</span></label>
                                    <input type="email" name="billing_surname" class="form-control" id="email" placeholder="you@example.com">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address for shipping updates.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" name="billing_address" class="form-control" id="address" placeholder="1234 Main St"
                                           required="">
                                    <div class="invalid-feedback">
                                        Please enter your shipping address.
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" name="billing_country" class="form-control" id="country" placeholder="" value="" required="">
                                    <div class="invalid-feedback">
                                        Valid country is required.
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label for="state" class="form-label">State / County</label>
                                    <input type="text" name="billing_state" class="form-control" id="state" placeholder="" value="" required="">
                                    <div class="invalid-feedback">
                                        Vali state is required.
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <label for="zip" class="form-label">Zip / Postal code</label>
                                    <input type="text" name="billing_zip" class="form-control" id="zip" placeholder="" required="">
                                    <div class="invalid-feedback">
                                        Zip code required.
                                    </div>
                                </div>
                            </div>
                            
                            <div><h4 class="mt-5">Payment method</h4></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="bg-white p-2 border rounded">
                                        <div class="form-check">
                                            <input id="credit" name="paymentMethod" type="radio" class="form-check-input"
                                                   checked="" required="">
                                            <label class="form-check-label" for="credit">Credit card</label>
                                            <div class="cc d-inline-block float-end">
                                                <i class="fab fa-cc-visa fs-4"></i>
                                                <i class="fa fa-cc-mastercard text-warning fs-4"></i>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="cc-name" class="form-label">Name on card</label>
                                    <input type="text" class="form-control" id="cc-name" placeholder="" required="">
                                    <small class="text-muted">Full name as displayed on card</small>
                                    <div class="invalid-feedback">
                                        Name on card is required
                                    </div>
                                </div>
        
                                <div class="col-md-6">
                                    <label for="cc-number" class="form-label">Credit card number</label>
                                    <input type="text" class="form-control" id="cc-number" placeholder="" required="">
                                    <div class="invalid-feedback">
                                        Credit card number is required
                                    </div>
                                </div>
        
                                <div class="col-md-3">
                                    <label for="cc-expiration" class="form-label">Expiration</label>
                                    <input type="text" class="form-control" id="cc-expiration" placeholder=""
                                           required="">
                                    <div class="invalid-feedback">
                                        Expiration date required
                                    </div>
                                </div>
        
                                <div class="col-md-3">
                                    <label for="cc-cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cc-cvv" placeholder="" required="">
                                    <div class="invalid-feedback">
                                        Security code required
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-5">
                                    <div class="bg-white p-2 border rounded">
                                        <div class="form-check">
                                            <input id="paypal" name="paymentMethod" type="radio" class="form-check-input" required="">
                                            <label class="form-check-label" for="paypal">Paypal</label>
                                            <div class="cc d-inline-block float-end">
                                                <i class="fab fa-cc-paypal fs-4 text-primary"></i>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5 col-lg-4 order-first order-md-last">
                        <div class="border bg-white p-3">
                            <h5 class="d-inline-block pe-1"><i class="fas fa-shopping-cart fs-6" aria-hidden="true"></i> Cart summary </h5>
                            <div class="view-cart d-inline-block">
                                (<a class="text-decoration-none" href="cart.php"><i class="far fa-eye"></i> View cart</a>)
                            </div>
                            <div class="cart-item border-bottom text-muted p-2 row">
                                <div class="col-8 item-name d-inline-block"><span class="item-amount">2 x </span>Cleaning tissues Acme CL02</div>
                                <div class="col-4 item-price text-end">16.00$</div>
                            </div>
                            <div class="cart-item border-bottom text-muted p-2 row">
                                <div class="col-8 item-name d-inline-block"><span class="item-amount">2 x </span>Inakustik 004528002 screen-clean</div>
                                <div class="col-4 item-price text-end">4.00$</div>
                            </div>
                            <div class="total text-end pt-2">
                                <span class="d-inline-blokc">Total: </span>
                                <span class="d-inline-block fw-bold">20.00$</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
