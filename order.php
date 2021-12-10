<?php
session_start();
// Check if user is logged in and request has order id
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

include_once 'conn.php';
require_once('objects/Order.php');
require_once('objects/Product.php');
// Get order by passed id
$stmt = $conn->prepare("SELECT * FROM `order` WHERE id = :orderId");
$stmt->bindParam(':orderId', $_GET['id'], PDO::PARAM_INT);
$stmt->execute();

// If order does not exist then redirect
if ($stmt->rowCount() === 0) {
    header('Location: notfound.php');
    exit;
}

// Create order object and get order data
$order = new Order();
$order->getOrderFromRow($stmt->fetch());

// Check if user owns the order or user is administrator
if ($stmt->rowCount() != 1 || ($_SESSION['user_id'] != $order->userId && $_SESSION['user_role'] != 1)) {
    header('Location: index.php');
    exit;
}

// Get order items
$orderItemStmt = $conn->prepare('SELECT P.name, OI.product_price, OI.quantity, (SELECT photo_path FROM product_photo PP WHERE P.id = PP.product_id LIMIT 1) AS photo_path
                                        FROM `order_item` OI
                                        LEFT JOIN product P ON P.id = OI.product_id 
                                        LEFT JOIN product_category C ON P.category_id = C.id
                                        WHERE OI.order_id = :orderId');
$orderItemStmt->bindParam(':orderId', $order->id);
$orderItemStmt->execute();
$orderItems = $orderItemStmt->fetchAll();

// Get order shipping data
$stmt = $conn->prepare("SELECT * FROM shipping WHERE id = :shippingId");
$stmt->bindParam(':shippingId', $order->shippingId);
$stmt->execute();
$row = $stmt->fetch();

$shipping = (object) ['type'=>$row['shipping_type'], 'address'=>$row['address'], 'city'=>$row['city'], 'country'=>$row['country']];

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
            <h4 class="w-100">Order #<?=htmlspecialchars($order->id)?></h4>
            <div class="text-muted d-inline-block">Date:</div>
            <div class="fw-bold text-muted d-inline-block"><?=htmlspecialchars($order->getCreatedAt())?></div>
            </div>
            <div class="d-inline-block ms-3">
            <div class="order-status-container yellow-label"><?=htmlspecialchars($order->status)?></div>
            </div>
        </div>
        <div class="order-content p-4">
            <div  class="d-flex flex-wrap">
                <div class="order-payment-info order-info-container">
                    <div class="order-info-label text-muted text-center mb-1 border-bottom">
                        Billing information
                    </div>
                    <ul class="list-unstyled">
                        <li>
                            <?=htmlspecialchars($order->getFullName() ?? '')?>
                        </li>
                        <li>
                            <?=htmlspecialchars($order->email ?? '')?>
                        </li>
                        <li>
                            <?=htmlspecialchars($order->phoneNr ?? '')?>
                        </li>
                        <li>Total: <?=htmlspecialchars($order->total)?> €</li>
                    </ul>
                </div>
                <div class="order-delivery-info order-info-container">
                    <div class="order-info-label text-muted text-center mb-1 border-bottom">
                        Shipping information
                    </div>
                    <ul class="list-unstyled">
                        <li>
                            <?=htmlspecialchars($shipping->country)?>
                        </li>
                        <li>
                            <?=htmlspecialchars($shipping->city)?>
                        </li>
                        <li>
                            <?=htmlspecialchars($shipping->address)?>
                        </li>
                        <li>
                            Recieve at <?=htmlspecialchars($shipping->type)?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="order-items d-block mt-3">
                <span class="fw-bold fs-4 mb-3 d-block">Items:</span>
                <?php
                foreach ($orderItems as $item) {?>
                    <div class="order-item bg-light p-3 row border-bottom">
                        <div class="col-md text-center text-md-start my-1 my-md-0">
                            <img src="images/<?=htmlspecialchars($item['photo_path'])?>" height="90" width="90" class="d-inline-block" alt="Product image">
                        </div>
                        <div class="d-inline-block px-3 product-title col-md my-1 my-md-0 w-100">
                            <span class="text-muted">Product:</span>
                            <div class="d-inline-block d-md-block"><?=htmlspecialchars($item['name'])?></div>
                        </div>
                        <div class="d-inline-block px-3 col-md my-1 my-md-0">
                            <span class="text-muted">Quantity:</span>
                            <div class="d-inline-block d-md-block"><?=htmlspecialchars($item['quantity'])?> pcs.</div>
                        </div>
                        <div class="d-inline-block px-3 col-md my-1 my-md-0">
                            <span class="text-muted">Price per piece:</span>
                            <div class="d-inline-block d-md-block"><?=htmlspecialchars($item['product_price'])?> €</div>
                        </div>
                        <div class="d-inline-block px-3 col-md my-1 my-md-0">
                            <span class="text-muted">Total:</span>
                            <div class="fw-bold d-inline-block d-md-block"><?=htmlspecialchars(number_format((float)($item['product_price'] * $item['quantity']), 2, '.', ''))?> €</div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="text-end my-2 fs-5 me-3">
                <div>Total:</div>
                <div class="fw-bold"><?=htmlspecialchars($order->total)?> €</div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
