<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
include_once 'head.php';
include_once 'header.php';
require_once('objects/Order.php');
// Get user orders
$stmt = $conn->prepare("SELECT id, total, created_at, order_name, status FROM `order` WHERE user_id = :userId");
$stmt->bindParam(':userId', $_SESSION['user_id']);
$stmt->execute();
$orderRows = $stmt->fetchAll();
// Save order objects
$orders = [];
foreach ($orderRows as $row) {
    $order = new Order();
    $order->getOrderSummaryFromRow($row);
    $orders[] = $order;
}

?>
<link href="css/purchase_history.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Order history</h2>
    </div>
    <div class="row">
        <div class="order-container">
            <?php
            if (!empty($orders)) {
                foreach ($orders as $order) {?>
                    <div class="order row row-cols-md-4 row-cols-2 bg-white border border-bottom-0 p-4 d-flex justify-content-between flex-wrap" data-id="<?=htmlspecialchars($order->id)?>" onclick="location.href='order.php?id=<?=htmlspecialchars($order->id)?>'">
                        <div class="order-number order-info col">
                            <div class="text-muted order-label">Order number:</div>
                            <div class="fw-bold">#<?=htmlspecialchars($order->id)?></div>
                        </div>
                        <div class="order-info col">
                            <div class="text-muted order-label">Date:</div>
                            <div class="fw-bold text-muted"><?=htmlspecialchars($order->getCreatedAt())?></div>
                        </div>
                        <div class="order-info col">
                            <div class="text-muted order-label">Total:</div>
                            <div class="fw-bold"><?=htmlspecialchars($order->total)?> €</div>
                        </div>
                        <div class="order-info col">
                            <div class="text-muted order-label">Status:</div>
                            <div class="order-status-container yellow-label"><?=htmlspecialchars($order->status)?></div>
                        </div>
                    </div>
            <?php
                }
            } else {?>
                <div class="border p-4 d-flex justify-content-between flex-wrap"">
                    <div class="m-auto fs-5">No orders have been placed!</div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
