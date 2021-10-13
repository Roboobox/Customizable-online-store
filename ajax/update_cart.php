<?php
header('Content-type: application/json');
session_start();

if (isset($_SESSION['user_id'])) {
    include_once "../conn.php";
    $stmt = $conn->prepare("UPDATE `cart_item` SET quantity = :productQuantity WHERE cart_id = (SELECT id FROM cart WHERE user_id = :userId AND is_active = :active) AND product_id = :productId");
    $stmt->bindParam(':productQuantity', $_POST['quantity']);
    $stmt->bindParam(':userId', $_SESSION['user_id']);
    $stmt->bindParam(':productId', $_POST['product_id']);
    $stmt->bindValue(':active', 1);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode(array('status' => 'success'));
        exit;
    }
}
else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $_SESSION['cart'][$_POST['product_id']] = $_POST['quantity'];
    echo json_encode(array('status' => 'success'));
    exit;
}

echo json_encode(array('status' => 'error'));
