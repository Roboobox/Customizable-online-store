<?php
header('Content-type: application/json');
session_start();
include_once "../conn.php";
$response_array = array();
try {
    if (isset($_POST['cart_product_id'], $_POST['cart_quantity']) && is_numeric($_POST['cart_product_id']) && is_numeric($_POST['cart_quantity'])) {
        $productId = (int)$_POST['cart_product_id'];
        $quantity = (int)$_POST['cart_quantity'];
        // TODO : Remove inventory checking because it is checked at cart
        // TODO : Add more exceptions
        // Get product inventory quantity which is being added to cart
        $productInventorySql = "SELECT quantity From product_inventory WHERE id = :productId";
//        $productSql = "
//        SELECT P.*, PI.quantity FROM product P
//        LEFT JOIN product_inventory PI on P.inventory_id = PI.id
//        WHERE P.id = :productId
//    ";
        $stmtInventory = $conn->prepare($productInventorySql);
        $stmtInventory->bindParam(':productId', $productId);
        $stmtInventory->execute();

        if ($quantity > 0 && $stmtInventory->rowCount() > 0) {
            $inventory = $stmtInventory->fetch();

            if ($inventory['quantity'] > 0) {

                // Use database if user is logged in
                if (isset($_SESSION['user_id'])) {
                    // Get user cart if it exists
                    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = :userId AND is_active = :cartActive");
                    $stmt->bindParam(':userId', $_SESSION['user_id']);
                    $stmt->bindValue(':cartActive', 1);
                    $stmt->execute();
                    $userCart = $stmt->fetch();

                    $isItemInCart = false;

                    if ($userCart) {
                        // If the cart exists, select items of the cart
                        $userCartId = $userCart['id'];

                        $cartSql = "SELECT * FROM `cart_item` WHERE cart_id = :cartId";
                        $stmt = $conn->prepare($cartSql);
                        $stmt->bindParam(':cartId', $userCartId);
                        $stmt->execute();

                        $userCartItems = $stmt->fetchAll();
                        // Check if there are any items in the cart
                        if (!empty($userCartItems)) {
                            // Check if product that is being added to the cart already is in the cart
                            foreach ($userCartItems as $item) {
                                if ($item['product_id'] == $productId) {
                                    // If product is in the cart, then just use UPDATE to increase quantity
                                    $stmt = $conn->prepare("UPDATE `cart_item` SET quantity = quantity + :productQuantity WHERE cart_id = :cartId AND product_id = :productId");
                                    $stmt->bindParam(':productQuantity', $quantity);
                                    $stmt->bindParam(':cartId', $item['cart_id']);
                                    $stmt->bindParam(':productId', $productId);
                                    $stmt->execute();
                                    $isItemInCart = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        // If the cart doesn't exist, make a new cart for this user
                        $stmt = $conn->prepare("INSERT INTO `cart` (is_active, user_id) VALUES (:cartActive, :userId)");
                        $stmt->bindValue(':cartActive', 1);
                        $stmt->bindParam(':userId', $_SESSION['user_id']);
                        $stmt->execute();
                        // Get id of the new cart
                        $userCartId = $conn->lastInsertId();
                    }

                    // If the product is not already in the cart, INSERT a new cart item in user's cart with this product
                    if (!$isItemInCart) {
                        $stmt = $conn->prepare("INSERT INTO `cart_item` (cart_id, product_id, quantity) VALUES (:cartId, :productId, :productQuantity)");
                        $stmt->bindParam(':cartId', $userCartId);
                        $stmt->bindParam(':productId', $productId);
                        $stmt->bindParam(':productQuantity', $quantity);
                        $stmt->execute();
                    }

                } else {
                    // If user is not logged in then use SESSION to save cart items

                    if (isset($_SESSION['cart'])) {
                        if (array_key_exists($productId, $_SESSION['cart'])) {
                            $_SESSION['cart'][$productId] += $quantity;
                        } else {
                            $_SESSION['cart'][$productId] = $quantity;
                        }
                    } else {
                        $_SESSION['cart'] = array($productId => $quantity);
                    }
                }


            }
        }
        $response_array['status'] = 'success';
        
    }
}
catch (Exception $e) {
    $response_array['satus'] = 'error';
}

echo json_encode($response_array);