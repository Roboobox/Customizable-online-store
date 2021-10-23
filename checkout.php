<?php
session_start();
// TODO: check if something is in cart to not access 'checkout.php'
//if ($_SESSION['cart_price'] == '0.00' && empty($_SESSION['cart'])) {
//    header('Location: cart.php');
//    exit;
//}
include_once 'head.php';
include_once 'header.php';

$paymentEnabled = false;
$checkoutStage = 1;
$checkoutError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_stage'])) {
    if ($_POST['checkout_stage'] == 1) {
        if (isset($_POST['billing_name'], $_POST['billing_surname'], $_POST['billing_email'], $_POST['billing_address'], $_POST['billing_country'], $_POST['billing_city'], $_POST['billing_phone'])) {
            if (!empty($_POST['billing_name']) && !empty($_POST['billing_surname']) && !empty($_POST['billing_email']) && !empty($_POST['billing_address']) && !empty($_POST['billing_country']) && !empty($_POST['billing_city']) && !empty($_POST['billing_phone'])) {
                if (strlen($_POST['billing_name']) < 256 && strlen($_POST['billing_surname']) < 256 && strlen($_POST['billing_email']) < 256 && strlen($_POST['billing_address']) < 256 && strlen($_POST['billing_country']) < 256 && strlen($_POST['billing_city']) < 256 && strlen($_POST['billing_phone']) < 256) {
                    $checkoutStage = 2;
                    $_SESSION['order_data'] = array('name'=>$_POST['billing_name'], 'surname'=>$_POST['billing_surname'], 'email'=>$_POST['billing_email'], 'address'=>$_POST['billing_address'], 'country'=>$_POST['billing_country'], 'city'=>$_POST['billing_city'], 'phone'=>$_POST['billing_phone'], 'type'=>$_POST['shipping_type']);
                }
            }
        }
    } else if ($_POST['checkout_stage'] == 2 && isset($_SESSION['order_data'])) {
        include_once "conn.php";
        // Save shipping information in database
        $stmt = $conn->prepare("INSERT INTO shipping (shipping_type, address, city, country, postal_code) VALUES (:shippingType, :address, :city, :country, :postalCode)");
        $stmt->bindParam(':shippingType', $_SESSION['order_data']['type']);
        $stmt->bindParam(':address', $_SESSION['order_data']['address']);
        $stmt->bindParam(':city', $_SESSION['order_data']['city']);
        $stmt->bindParam(':country', $_SESSION['order_data']['country']);
        $stmt->bindParam(':postalCode', $_SESSION['order_data']['phone']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            // Get saved shipping information id
            $shippingId = $conn->lastInsertId();
            
            // Save order information in database
            $stmt = $conn->prepare("INSERT INTO `order` (total, order_name, order_surname, order_email, order_phonenr, status, user_id, shipping_id) VALUES (:total, :name, :surname, :email, :phoneNr, :status, :userId, :shippingId)");
            $stmt->bindParam(':total', $_SESSION['cart_price']);
            $stmt->bindParam(':name', $_SESSION['order_data']['name']);
            $stmt->bindParam(':surname', $_SESSION['order_data']['surname']);
            $stmt->bindParam(':phoneNr', $_SESSION['order_data']['phone']);
            $stmt->bindParam(':email', $_SESSION['order_data']['email']);
            $stmt->bindValue(':status', 'New order');

            // Save user id
            if (isset($_SESSION['user_id'])) {
                $stmt->bindParam(':userId', $_SESSION['user_id']);
            } else {
                // Set user id as null because user is not logged in
                $stmt->bindValue(':userId', null, PDO::PARAM_NULL);
            }

            // Bind shipping ID and execute order insertion query
            $stmt->bindParam(':shippingId', $shippingId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Get new order id
                $orderId = $conn->lastInsertId();
                // Prepare sql for inserting order items
                $orderItemSql = "INSERT INTO order_item (product_id, product_price, quantity, order_id) VALUES ";

                // Save order items and set user cart as not active anymore
                if (isset($_SESSION['user_id'])) {
                    // If user is logged in then get cart items and product data from users cart in database
                    $cartItemStmt = $conn->prepare("SELECT CI.product_id, CI.quantity, IFNULL((P.price - P.price * (D.discount_percent / 100)), P.price) AS price FROM cart_item CI
                                                LEFT JOIN `product` P ON CI.product_id = P.id
                                                LEFT JOIN `product_discount` D on D.product_id = CI.product_id
                                                WHERE CI.cart_id = (SELECT id FROM cart WHERE user_id = :userId AND is_active = :active)");
                    $cartItemStmt->bindParam(':userId', $_SESSION['user_id']);
                    $cartItemStmt->bindValue(':active', 1);
                    $cartItemStmt->execute();
                    $cartItems = $cartItemStmt->fetchAll();

                    // Update user cart as not active anymore
                    $cartStmt = $conn->prepare("UPDATE cart SET is_active = 0 WHERE user_id = :userId AND is_active = :active");
                    $cartStmt->bindParam(':userId', $_SESSION['user_id']);
                    $cartStmt->bindValue(':active', 1);
                    $cartStmt->execute();
                } else if (!empty($_SESSION['cart'])) {
                    // If user is not logged in then get cart from session and product data from database
                    $cart = $_SESSION['cart'];
                    // Create template for product ids
                    $inQuery = implode(',', array_fill(0, count($cart), '?'));
                    $productStmt = $conn->prepare('SELECT P.id, IFNULL((P.price - P.price * (D.discount_percent / 100)), P.price) AS price FROM product P LEFT JOIN `product_discount` D on D.product_id = P.id WHERE P.id IN ('.$inQuery.')');

                    // Bind product ids to prepared template
                    $i = 1;
                    foreach ($cart as $id => $quantity) {
                        $productStmt->bindValue($i, $id);
                        $i++;
                    }
                    $productStmt->execute();
                    // Get results from query
                    $orderItems = $productStmt->fetchAll();
                    $cartItems = [];

                    // Create array of cart items from order items
                    foreach ($orderItems as $item) {
                        $cartItems[] = array('product_id' => $item['id'], 'quantity' => $cart[$item['id']], 'price' => $item['price']);
                    }
                    // Clear user cart by unsetting session variable
                    unset($_SESSION['cart']);
                }

                // If there are any cart items prepare them to be inserted as order items
                if (isset($cartItems) && !empty($cartItems)) {
                    $preparedValues = [];
                    $i = 0;
                    // Creating insert template for order items
                    foreach ($cartItems as $item) {
                        if ($i != 0) $orderItemSql .= ",";
                        $orderItemSql .= "(:prodId".$i.", :price".$i.", :quantity".$i.", :orderId)";
                        $preparedValues[$i] = array($item['product_id'], $item['quantity'], $item['price']);
                        $i++;
                    }
                    $orderItemStmt = $conn->prepare($orderItemSql);
                    // Filling insert template for order items
                    foreach ($preparedValues as $i => $values) {
                        $orderItemStmt->bindParam(':prodId'.$i, $values[0]);
                        $orderItemStmt->bindParam(':price'.$i, $values[2]);
                        $orderItemStmt->bindParam(':quantity'.$i, $values[1]);
                    }
                    // Bind order id to order item insert
                    $orderItemStmt->bindParam(':orderId', $orderId);
                    $orderItemStmt->execute();
                }
            }

            $checkoutStage = 3;
        } else {
            $checkoutError = true;
        }
        // Unset order information from session
        unset($_SESSION['order_data']);
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
                                    <div class="text-muted">Phone number:</div>
                                    <span class="fw-bold"><?=clean_input($_POST['billing_phone'])?></span>
                                </div>
                                <div class="order-summary-row row my-2">
                                    <div class="text-muted">Shipping:</div>
                                    <span class="fw-bold">Recieve at <?=clean_input($_POST['shipping_type'])?></span>
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
                                        <input type="hidden" name="checkout_stage" value="2"/>
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
                                    <input type="text" name="billing_name" value="<?=$_SESSION['user_data']['name'] ?? ''?>" class="form-control" id="firstName" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid first name is required.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" name="billing_surname" value="<?=$_SESSION['user_data']['surname'] ?? ''?>" class="form-control" id="lastName" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        Valid last name is required.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="billing_email" value="<?=$_SESSION['user_data']['email'] ?? ''?>" class="form-control" id="email" placeholder="you@example.com" required maxlength="255">
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
                                <div class="col-lg-4">
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
                                <div class="col-lg-4">
                                    <label for="phone" class="form-label">Phone number</label>
                                    <input type="tel" name="billing_phone" class="form-control" id="phone" placeholder="" required maxlength="31" minlength="1">
                                    <div class="invalid-feedback">
                                        Phone number required.
                                    </div>
                                </div>
                            </div>
                            
                            <div><h4 class="mt-4">Shipping details</h4></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" value="address" type="radio" name="shipping_type" id="shippingTypeAddress" checked>
                                        <label class="form-check-label" for="shippingTypeAddress">
                                            Recieve at address<br>
                                            <span class="text-muted">Shipping fees will not be included in order total and will be arranged after placing the order</span>
                                        </label>
                                    </div>
                                    <?php
                                    if ($storeSettings['store_address'] !== NULL) {
                                    ?>
                                    <div class="form-check my-2">
                                        <input class="form-check-input" value="store" type="radio" name="shipping_type" id="shippingTypeStore">
                                        <label class="form-check-label" for="shippingTypeStore">
                                            Recieve at store<br>
                                            <span class="text-muted">Store address: <?=$storeSettings['store_address']?></span>
                                        </label>
                                    </div>
                                    <?php
                                    }
                                    ?>
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
                            <input type="hidden" name="checkout_stage" value="1"/>
                            <button type="submit" class="mt-4 btn btn-primary float-end checkout-continue fs-5 fw-bold">Continue <i class="fas fa-arrow-right"></i></button>
                        </form>
                        <?php
                        } else if ($checkoutStage == 3) {
                            if (!$checkoutError) {
                        ?>
                                <div class="fs-4 text-center my-4 text-success"><i class="far fa-check-circle"></i> Order successfully placed!</div><?php
                            } else {?>
                                <div class="fs-4 text-center my-4 text-danger"><i class="far fa-times-circle"></i> Something went wrong, try again later!</div>
                            <?php
                            }
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
                                    <span class="d-inline-block">Total: </span>
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
