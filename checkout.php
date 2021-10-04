<?php
include_once 'head.php';
include_once 'header.php';

$paymentEnabled = false;
$checkoutStage = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($checkoutStage === 1) {
        if (isset($_POST['billing_name'], $_POST['billing_surname'], $_POST['billing_email'], $_POST['billing_address'], $_POST['billing_country'], $_POST['billing_city'], $_POST['billing_zip'])) {
            if (!empty($_POST['billing_name']) && !empty($_POST['billing_surname']) && !empty($_POST['billing_email']) && !empty($_POST['billing_address']) && !empty($_POST['billing_country']) && !empty($_POST['billing_city']) && !empty($_POST['billing_zip'])) {
                if (strlen($_POST['billing_name']) < 256 && strlen($_POST['billing_surname']) < 256 && strlen($_POST['billing_email']) < 256 && strlen($_POST['billing_address']) < 256 && strlen($_POST['billing_country']) < 256 && strlen($_POST['billing_city']) < 256 && strlen($_POST['billing_zip']) < 256) {
                    $checkoutStage = 2;
                }
            }
        }
    } else if ($checkoutStage === 2) {
        include_once 'ajax/get_cart.php';
        $checkoutStage = 3;
        //var_dump($cartItems);
    }
}

function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

?>
<script type="text/javascript" src="./js/checkout.js?v=3"></script>
<script type="text/javascript">
    $( document ).ready(function() {
        getCartSummary();
    });
</script>
<link href="css/checkout.css?<?=time()?>" rel="stylesheet">
<div class="container">
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
                    <div class="progress-icon progress-first m-auto <?=($checkoutStage > 1) ? 'active' : ''?>">
                        2
                    </div>
                    <span class="d-block">Review</span>
                </div>
                <hr>
                <div class="progress-container d-inline-block text-center">
                    <div class="progress-icon progress-first m-auto <?=($checkoutStage > 2) ? 'active' : ''?>">
                        3
                    </div>
                    <span class="d-block">Finish</span>
                </div>
            </div>
        </div>
        <div class="row bg-light border p-4">
            <div class="col-12">
                <div class="row">
                    <div class="m-auto col-md-7 col-lg-8 pe-0 pe-md-5">
                        <?php if ($checkoutStage === 2) {
                        ?>
                        <div class="border bg-white p-3">
                            <div><h4 class="mt-md-2 mt-4">Order summary</h4></div>
                            <div class="col">
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Name:</div>
                                    <span class="fw-bold"><?=clean_input($_POST['billing_name'] . ' ' . clean_input($_POST['billing_surname']))?></span>
                                </div>
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Email:</div>
                                    <span class="fw-bold"><?=clean_input($_POST['billing_email'])?></span>
                                </div>
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Address:</div>
                                    <span class="fw-bold"><?=clean_input($_POST['billing_address']) . ', ' . clean_input($_POST['billing_country']) . ', ' . clean_input($_POST['billing_city'])?></span>
                                </div>
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Zip / Postal code:</div>
                                    <span class="fw-bold"><?=clean_input($_POST['billing_zip'])?></span>
                                </div>
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Payment method:</div>
                                    <span class="fw-bold"><?=($paymentEnabled === true) ? clean_input($_POST['paymentMethod']) : 'Bank transfer'?></span>
                                </div>
                                <div class="order-summary-row-payment rounded row my-3 p-2 mx-3 bg-light fs-5">
                                    <div class="col d-flex align-items-center justify-content-center">
                                        <div class="text-muted fw-bold text-center">Payment amount: </div>
                                    </div>
                                    <div class="col d-flex align-items-center justify-content-center">
                                        <div class="fw-bold text-muted text-center order-review-sum"><?=$_SESSION['cart_price']?> €</div>
                                    </div>
                                </div>
                                <div class="order-summary-row-confirm row my-2 mx-2">
                                    <form action="checkout.php" method="POST">
                                        <button type="submit" class="btn btn-primary checkout-continue fs-5 fw-bold">Place an order <i class="fas fa-check"></i></button>
                                    </form>
                                </div></div>
                        </div>
                        <?php
                        }
                        else if ($checkoutStage === 1) {
                        ?>
                        <form method="POST" action="checkout.php" class="needs-validation" novalidate>
                            <div><h4 class="mt-md-2 mt-4">Billing details</h4></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First name</label>
                                    <input type="text" name="billing_name" class="form-control" id="firstName" placeholder="" value="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid first name is required.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" name="billing_surname" class="form-control" id="lastName" placeholder="" value="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid last name is required.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="billing_email" class="form-control" id="email" placeholder="you@example.com" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" name="billing_address" class="form-control" id="address" maxlength="255" required>
                                    <div class="invalid-feedback">
                                        Please enter your shipping address.
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" name="billing_country" class="form-control" id="country" placeholder="" value="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid country is required.
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" name="billing_city" class="form-control" id="city" placeholder="" value="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid city is required.
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <label for="zip" class="form-label">Zip / Postal code</label>
                                    <input type="text" name="billing_zip" class="form-control" id="zip" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Zip code required.
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            if ($paymentEnabled === true) {
                            ?>
                            <div><h4 class="d-none mt-5">Payment method</h4></div>
                            <div class="d-none row g-3">
                                <div class="col-12">
                                    <div class="bg-white p-2 border rounded">
                                        <div class="form-check">
                                            <input disabled id="credit" value="Credit card" name="paymentMethod" type="radio" class="form-check-input">
                                            <label class="form-check-label" for="credit">Credit card</label>
                                            <div class="cc d-inline-block float-end">
                                                <i class="fab fa-cc-visa fs-4"></i>
                                                <i class="fa fa-cc-mastercard text-warning fs-4"></i>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="cc-options row my-3">
                                    <div class="col-md-6">
                                        <label for="cc-name" class="form-label">Name on card</label>
                                        <input type="text" class="form-control" id="cc-name" placeholder="">
                                        <small class="text-muted">Full name as displayed on card</small>
                                        <div class="invalid-feedback">
                                            Name on card is required
                                        </div>
                                    </div>
            
                                    <div class="col-md-6">
                                        <label for="cc-number" class="form-label">Credit card number</label>
                                        <input type="text" class="form-control" id="cc-number" placeholder="">
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
                                        <input type="text" class="form-control" id="cc-cvv" placeholder=""">
                                        <div class="invalid-feedback">
                                            Security code required
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-2">
                                    <div class="bg-white p-2 border rounded">
                                        <div class="form-check">
                                            <input disabled id="paypal" value="Paypal" name="paymentMethod" type="radio" class="form-check-input">
                                            <label class="form-check-label" for="paypal">Paypal</label>
                                            <div class="cc d-inline-block float-end">
                                                <i class="fab fa-cc-paypal fs-4 text-primary"></i>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-2">
                                    <div class="bg-white p-2 border rounded">
                                        <div class="form-check">
                                            <input id="invoice" name="paymentMethod" type="radio" value="Bank transfer" class="form-check-input" checked>
                                            <label class="form-check-label" for="invoice">Bank transfer</label>
                                            <div class="cc d-inline-block float-end">
                                                <i class="fas fa-file-invoice-dollar fs-4 text-muted"></i>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                            ?>
                            <button type="submit" class="mt-4 btn btn-primary float-end checkout-continue fs-5 fw-bold">Continue <i class="fas fa-arrow-right"></i></button>
                        </form>
                        <?php
                        } else if ($checkoutStage == 3) {
                        ?>
                        <div>Test</div>
                        <?php
                        }
                        ?>
                    </div>
                    <?php if ($checkoutStage === 1) {?>
                    <div class="col-md-5 col-lg-4 order-first order-md-last">
                        <div class="border bg-white p-3">
                            <h5 class="d-inline-block pe-1"><i class="fas fa-shopping-cart fs-6" aria-hidden="true"></i> Cart summary </h5>
                            <div class="view-cart d-inline-block">
                                (<a class="text-decoration-none" href="cart.php"><i class="far fa-eye"></i> View cart</a>)
                            </div>
                            <div class="cart-summary">
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
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'footer.php'?>
