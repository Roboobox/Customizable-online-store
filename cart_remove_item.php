<?php
session_start();
include_once "conn.php";

$userToken = $_POST['token'] ?? '';

if (isset($_SESSION['user_id']) && hash_equals($_SESSION['user_token'], $userToken)) {
    // Check if user owns the cart
    $stmt = $conn->prepare("SELECT user_id FROM `cart` WHERE is_active = :active AND id = :cartId");
    $stmt->bindValue(':active', 1);
    $stmt->bindParam(':cartId', $_POST['cart_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $cartQuery = "DELETE FROM `cart_item` WHERE cart_id = :cartId AND product_id = :productId";
        $stmt = $conn->prepare($cartQuery);
        $stmt->bindParam(':cartId', $_POST['cart_id']);
        $stmt->bindParam(':productId', $_POST['product_id']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo json_encode(array('status' => 'success'));
            //header('location:cart.php');
            exit();
        }
    }
    
    echo json_encode(array('status' => 'Permission denied'));
    exit;
    //echo 'Permission denied';
}
else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productId = $_POST['product_id'];
    if (array_key_exists($productId, $_SESSION['cart'])) {
        unset($_SESSION['cart'][$productId]);
        echo json_encode(array('status' => 'success'));
        //header('location:cart.php');
        exit();
    }
}

echo json_encode(array('status' => 'error'));
