<?php
header('Content-type: application/json');
session_start();
include_once "../conn.php";

$userToken = $_POST['token'] ?? '';
// Check if user is logged in and using correct token or not logged in
if (isset($_SESSION['user_id'], $_SESSION['user_token']) && hash_equals($_SESSION['user_token'], $userToken)) {
    // Check if user owns the cart
    $stmt = $conn->prepare("SELECT user_id FROM `cart` WHERE is_active = :active AND id = :cartId");
    $stmt->bindValue(':active', 1);
    $stmt->bindParam(':cartId', $_POST['cart_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        // Delete cart item
        $cartQuery = "DELETE FROM `cart_item` WHERE cart_id = :cartId AND product_id = :productId";
        $stmt = $conn->prepare($cartQuery);
        $stmt->bindParam(':cartId', $_POST['cart_id']);
        $stmt->bindParam(':productId', $_POST['product_id']);
        $stmt->execute();

        // Check if item was deleted
        if($stmt->rowCount() > 0) {
            echo json_encode(array('status' => 'success'));
            exit();
        }
        echo json_encode(array('status' => 'error'));
        exit();
    }
    // User does not have permission to this cart
    echo json_encode(array('status' => 'Permission denied'));
    exit;
}
else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productId = $_POST['product_id'];
    // Check if product exists in cart and delete it from session
    if (array_key_exists($productId, $_SESSION['cart'])) {
        unset($_SESSION['cart'][$productId]);
        echo json_encode(array('status' => 'success'));
        exit();
    }
}

echo json_encode(array('status' => 'error'));
