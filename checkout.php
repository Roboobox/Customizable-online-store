<?php
// TODO : Reduce item inventory on order placed
session_start();
if ((!isset($_SESSION['cart_data']['cart_id']) && !isset($_SESSION['cart'])) || $_SESSION['cart_data']['item_count'] < 1 || !isset($_SESSION['cart_data']['contents'])) {
    header('Location: cart.php');
    exit;
}

include_once "conn.php";
// Check if cart products are not deleted or do not exceed inventory quantity
// Get cart contents - product id and quantity
$productIdAndQuantity = $_SESSION['cart_data']['contents'];
// Prepare query to select cart products and inventory of products
$productIdsInQuery = implode(',', array_fill(0, count($productIdAndQuantity), '?'));
$productStmt = $conn->prepare("SELECT P.id, P.is_deleted, I.quantity FROM `product` P LEFT JOIN product_inventory I ON P.inventory_id = I.id WHERE P.id IN (" . $productIdsInQuery . ")");
$i = 1;
// Bind cart product ids to statement
foreach ($productIdAndQuantity as $id => $quantity) {
    $productStmt->bindValue(($i), $id);
    $i++;
}
// Check if query successful
if ($productStmt->execute()) {
    // Check if any of cart products is deleted or exceeds inventory quantity
    $cartProducts = $productStmt->fetchAll();
    foreach ($cartProducts as $product) {
        if ($product['is_deleted'] == 1 || $product['quantity'] < $productIdAndQuantity[$product['id']]) {
            header('Location: cart.php');
            exit;
        }
    }
} else {
    header('Location: cart.php');
    exit;
}

include_once 'head.php';
include_once 'header.php';

$paymentEnabled = false;
$checkoutStage = 1;
$checkoutError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_stage'])) {
    if ($_POST['checkout_stage'] == 1) {
        if (isset($_POST['billing_name'], $_POST['billing_surname'], $_POST['billing_email'], $_POST['billing_address'], $_POST['billing_country'], $_POST['billing_city'], $_POST['billing_phone'], $_POST['shipping_type'])) {
            $formErrors = validateForm();
            // If validation returned no errors then proceed to next checkout stage
            if (empty($formErrors)) {
                $checkoutStage = 2;
                $_SESSION['order_data'] = array('name'=>$_POST['billing_name'], 'surname'=>$_POST['billing_surname'], 'email'=>$_POST['billing_email'], 'address'=>$_POST['billing_address'], 'country'=>$_POST['billing_country'], 'city'=>$_POST['billing_city'], 'phone'=>$_POST['billing_phone'], 'type'=>$_POST['shipping_type']);
            }
        }
    } else if ($_POST['checkout_stage'] == 2 && isset($_SESSION['order_data'])) {
        // Save shipping information in database
        $stmt = $conn->prepare("INSERT INTO shipping (shipping_type, address, city, country) VALUES (:shippingType, :address, :city, :country)");
        $stmt->bindParam(':shippingType', $_SESSION['order_data']['type']);
        $stmt->bindParam(':address', $_SESSION['order_data']['address']);
        $stmt->bindParam(':city', $_SESSION['order_data']['city']);
        $stmt->bindParam(':country', $_SESSION['order_data']['country']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            // Get saved shipping information id
            $shippingId = $conn->lastInsertId();
            
            // Save order information in database
            $stmt = $conn->prepare("INSERT INTO `order` (total, order_name, order_surname, order_email, order_phonenr, status, user_id, shipping_id) VALUES (:total, :name, :surname, :email, :phoneNr, :status, :userId, :shippingId)");
            $stmt->bindParam(':total', $_SESSION['cart_data']['price']);
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
                                                LEFT JOIN `product_discount` D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
                                                WHERE CI.cart_id = (SELECT id FROM cart WHERE user_id = :userId AND is_active = :active)");
                    $cartItemStmt->bindParam(':userId', $_SESSION['user_id']);
                    $cartItemStmt->bindValue(':active', 1);
                    $cartItemStmt->execute();

                    if ($cartItemStmt->rowCount() == 0) {
                        $checkoutError = true;
                    }

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
                    $productStmt = $conn->prepare('SELECT P.id, IFNULL((P.price - P.price * (D.discount_percent / 100)), P.price) AS price FROM product P LEFT JOIN `product_discount` D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at)) WHERE P.id IN ('.$inQuery.')');
                    // Bind product ids to prepared template
                    $i = 1;
                    foreach ($cart as $id => $quantity) {
                        $productStmt->bindValue($i, $id);
                        $i++;
                    }
                    $productStmt->execute();
                    if ($productStmt->rowCount() > 0) {
                        // Get results from query
                        $orderItems = $productStmt->fetchAll();
                        $cartItems = [];

                        // Create array of cart items from order items
                        foreach ($orderItems as $item) {
                            $cartItems[] = array('product_id' => $item['id'], 'quantity' => $cart[$item['id']], 'price' => $item['price']);
                        }
                        // Clear user cart by unsetting session variable
                        unset($_SESSION['cart']);
                    } else {
                        $checkoutError = true;
                    }

                }

                // If there are any cart items prepare them to be inserted as order items
                if (isset($cartItems) && !empty($cartItems)) {
                    $preparedValues = [];
                    $i = 0;
                    // Creating insert template for order items
                    foreach ($cartItems as $item) {
                        // Save prepared values
                        if ($i != 0) $orderItemSql .= ",";
                        $orderItemSql .= "(:prodId".$i.", :price".$i.", :quantity".$i.", :orderId)";
                        $preparedValues[$i] = array($item['product_id'], $item['quantity'], $item['price']);
                        // Update product inventory quantity
                        $productQuantityStmt = $conn->prepare("UPDATE `product_inventory` SET quantity = (quantity - :itemQuantity) WHERE id = :itemProductId");
                        $productQuantityStmt->bindParam(':itemQuantity', $item['quantity'], PDO::PARAM_INT);
                        $productQuantityStmt->bindParam(':itemProductId', $item['product_id']);
                        $productQuantityStmt->execute();
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
                    if ($orderItemStmt->rowCount() == 0) {
                        $checkoutError = true;
                    }
                }
                if (!$checkoutError) {
                    $checkoutStage = 3;
                }
            } else {
                $checkoutError = true;
            }
        } else {
            $checkoutError = true;
        }
        // Unset order information from session
        unset($_SESSION['order_data']);
        // Unset cart information from session
        unset($_SESSION['cart_data']);
    }
    if ($checkoutError) {
        $formErrors['general'] = 'Something went wrong, try again later!';
    }
}

// Form validation function
function validateForm() {
    $formErrors = array();

    // Name validation
    $formErrors = array_merge($formErrors, simpleValidation(255, 'billing_name', 'Name'));

    // Surname validation
    $formErrors = array_merge($formErrors, simpleValidation(255, 'billing_surname', 'Surname'));

    // Email validation
    $email = $_POST['billing_email'];
    if (empty($email)) {
        $formErrors['billing_email'] = 'Email is required!';
    } else if (strlen($email) > 255) {
        $formErrors['billing_email'] = 'Email cannot exceed 254 characters!';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formErrors['billing_email'] = 'Email address is not valid!';
    }

    // Address validation
    $formErrors = array_merge($formErrors, simpleValidation(255, 'billing_address', 'Address'));

    // Phone number validation
    $phoneNr = $_POST['billing_phone'];
    // Checks if length is 1-31 characters and only digits
    if (!preg_match('/^[0-9]{1,31}$/', $phoneNr)) {
        $formErrors['billing_phone'] = 'Phone number must only be digits and cannot be less than 1 or more than 31 digits!';
    }

    // Country validation
    $formErrors = array_merge($formErrors, simpleValidation(75, 'billing_country', 'Country'));

    // City validation
    $formErrors = array_merge($formErrors, simpleValidation(255, 'billing_city', 'City'));

    // Shipping type validation
    $shipType = $_POST['shipping_type'];
    if (empty($shipType)) {
        $formErrors['shipping_type'] = 'Shipping type is required!';
    }

    return $formErrors;
}

// Simple validation to only check if field is not empty and is not longer than max length
function simpleValidation($maxLength, $fieldName, $fieldTitle) {
    $formErrors = array();
    $field = $_POST[$fieldName];
    if (empty($field)) {
        $formErrors[$fieldName] = $fieldTitle . ' is required!';
    } else if (strlen($field) > $maxLength) {
        $formErrors[$fieldName] = $fieldTitle . ' cannot exceed '. $maxLength .' characters!';
    }
    return $formErrors;
}
// Sanitize input to be displayed
function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

?>
<script type="text/javascript" src="./js/checkout.js?v=4"></script>
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
                                        <div class="fw-bold text-muted text-center order-review-sum"><?=htmlspecialchars($_SESSION['cart_data']['price'])?> €</div>
                                    </div>
                                </div>
                                <div class="order-summary-row-confirm row my-2 mx-2">
                                    <form action="checkout.php" method="POST" class="text-center">
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
                                    <label for="firstName" class="form-label">First name *</label>
                                    <input type="text" name="billing_name" value="<?=htmlspecialchars($_POST['billing_name'] ?? $_SESSION['user_data']['name'] ?? '')?>" class="form-control <?=isset($formErrors['billing_name']) ? 'is-invalid' : ''?>" id="firstName" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_name'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last name *</label>
                                    <input type="text" name="billing_surname" value="<?=htmlspecialchars($_POST['billing_surname'] ?? $_SESSION['user_data']['surname'] ?? '')?>" class="form-control <?=isset($formErrors['billing_surname']) ? 'is-invalid' : ''?>" id="lastName" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_surname'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" name="billing_email" value="<?=htmlspecialchars($_POST['billing_email'] ?? $_SESSION['user_data']['email'] ?? '')?>" class="form-control <?=isset($formErrors['billing_email']) ? 'is-invalid' : ''?>" id="email" placeholder="you@example.com" required maxlength="255">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_email'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Address *</label>
                                    <input type="text" name="billing_address" value="<?=htmlspecialchars($_POST['billing_address'] ?? '')?>" class="form-control  <?=isset($formErrors['billing_address']) ? 'is-invalid' : ''?>" id="address" maxlength="255" required>
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_address'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label for="country" class="form-label">Country *</label>
                                    <input type="text" name="billing_country" value="<?=htmlspecialchars($_POST['billing_country'] ?? '')?>" class="form-control <?=isset($formErrors['billing_country']) ? 'is-invalid' : ''?>" id="country" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_country'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" name="billing_city" value="<?=$_POST['billing_city'] ?? ''?>" class="form-control <?=isset($formErrors['billing_city']) ? 'is-invalid' : ''?>" id="city" placeholder="" required maxlength="255">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_city'] ?? ''?>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label for="phone" class="form-label">Phone number *</label>
                                    <input type="tel" name="billing_phone" value="<?=$_POST['billing_phone'] ?? $_SESSION['user_data']['phoneNr'] ?? ''?>" class="form-control <?=isset($formErrors['billing_phone']) ? 'is-invalid' : ''?>" id="phone" placeholder="" required maxlength="31" minlength="1">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['billing_phone'] ?? ''?>
                                    </div>
                                </div>
                            </div>
                            
                            <div><h4 class="mt-4">Shipping details *</h4></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input <?=isset($formErrors['shipping_type']) ? 'is-invalid' : ''?>" value="address" type="radio" name="shipping_type" id="shippingTypeAddress" checked>
                                        <label class="form-check-label" for="shippingTypeAddress">
                                            Recieve at address<br>
                                            <span class="text-muted">Shipping fees will not be included in order total and will be arranged after placing the order</span>
                                        </label>
                                        <div class="invalid-feedback">
                                            <?=$formErrors['shipping_type'] ?? ''?>
                                        </div>
                                    </div>
                                    <?php
                                    if ($storeSettings['store_address'] !== NULL) {
                                    ?>
                                    <div class="form-check my-2">
                                        <input class="form-check-input <?=isset($formErrors['shipping_type']) ? 'is-invalid' : ''?>" value="store" type="radio" name="shipping_type" id="shippingTypeStore">
                                        <label class="form-check-label" for="shippingTypeStore">
                                            Recieve at store<br>
                                            <span class="text-muted">Store address: <?=$storeSettings['store_address']?></span>
                                        </label>
                                        <div class="invalid-feedback">
                                            <?=$formErrors['shipping_type'] ?? ''?>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <input type="hidden" name="checkout_stage" value="1"/>
                            <div class="text-danger text-center <?=(isset($formErrors['general']) ? 'd-inline-block' : 'd-none')?>"><?=$formErrors['general'] ?? ''?></div>
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
