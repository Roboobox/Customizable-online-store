<?php
session_start();
include_once "conn.php";

$responseArray = array();
$cartItems = array();
$cartItemCount = 0;

if (isset($_SESSION['user_id'])) {
    $cartIdSql = 'SELECT id FROM cart WHERE user_id = :userId AND is_active = :active';
    $stmt = $conn->prepare($cartIdSql);
    $stmt->bindParam(':userId', $_SESSION['user_id']);
    $stmt->bindValue(':active', 1);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $cartId = $row["id"];
        $cartSql = 'SELECT * FROM `cart_item` WHERE cart_id = :cartId';
        $stmt = $conn->prepare($cartSql);
        $stmt->bindParam(':cartId', $cartId);
        $stmt->execute();

        $cartItemCount = $stmt->rowCount();
        $cartItems = $stmt->fetchAll();
    }
}
else if (isset($_SESSION['cart']))
{
    $cartItemCount = count($_SESSION['cart']);
    $cartItems = $_SESSION['cart'];
}

if (isset($_POST['cart_html'])) {
    require_once('objects/Product.php');
    $productIdAndQuantity = array();
    $products = array();
    $totalCartPrice = 0.00;

    if (isset($_SESSION['user_id'])) {
        foreach ($cartItems as $item) {
            $productIdAndQuantity[(int)$item['product_id']] = (int)$item['quantity'];
        }
    } else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $productIdAndQuantity = $_SESSION['cart'];
    }

    if (!empty($productIdAndQuantity)) {
        $inQuery = implode(',', array_fill(0, count($productIdAndQuantity), '?'));
        $productSql = "
            SELECT P.*, I.quantity, D.discount_percent, C.name AS category, (SELECT photo_path FROM product_photo PP WHERE P.id = PP.product_id LIMIT 1) AS photo_path  FROM `product` P
            LEFT JOIN product_inventory I ON P.inventory_id = I.id
            LEFT JOIN product_category C ON P.category_id = C.id
            LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
            WHERE P.id IN (" . $inQuery . ")
            ORDER BY P.name
        ";
        $stmt = $conn->prepare($productSql);

        $i = 1;
        foreach ($productIdAndQuantity as $id => $quantity) {
            $stmt->bindValue(($i), $id);
            $i++;
        }
        $stmt->execute();

        $productRows = $stmt->fetchAll();
        $products = array();

        foreach ($productRows as $row) {
            $product = new Product();
            $product->getProductDataFromRow($row);
            $products[] = $product;
        }
    }

    $error = false;
    $cartHtml = '';

    if (!empty($products)) {
        foreach ($products as $cartProduct) {
            $cartHtml .= '
                <div class="row cart-item p-3 border">
                <div class="col-md text-center text-md-start">
                    <img src="test_images/' . $cartProduct->photoPath . '" height="130" width="130" class="d-inline-block" alt="Product image">
                </div>
                <div class="d-inline-block px-3 product-title col-md text-center text-md-start my-md-0 my-3">
                    <div>' . $cartProduct->name . '</div>
                </div>
                <div class="px-3 col-md text-center text-md-start">
                    <div>' . $cartProduct->discountPrice . ' €</div>
                </div>
                <div class="d-inline-block px-3 col-md text-center text-md-start">
                    <div class="quantity-container">
                        <div class="quantity-picker-container">
                            <div class="minus quantity-btn"><i class="fas fa-minus"></i></div>
                            <input data-product="' . $cartProduct->id . '" class="form-control" type="number" value="' . $productIdAndQuantity[$cartProduct->id] . '" min="1" max="100000">
                            <div class="plus quantity-btn"><i class="fas fa-plus"></i></div>
                        </div>
            ';

            if ($productIdAndQuantity[$cartProduct->id] > $product->inventoryAmount) {
                $error = true;
                $cartHtml .= '<p class="text-danger pt-2"><i class="fas fa-info-circle"></i> Selected quantity is not available</p>';
            }

            $cartHtml .= '
                        <div class="mt-3 ms-md-1 me-md-0 me-1 mb-2 mb-md-0">
                            <a onclick="return confirm(\'Are you sure you want to delete this item\')" data-product="' . $cartProduct->id . '" ' . (isset($_SESSION['user_id']) ? 'data-user="' . $_SESSION['user_id'] . '"' : '') . ' ' . (isset($_SESSION['user_token']) ? 'data-token="' . $_SESSION['user_token'] . '"' : '') . ' ' . (isset($cartId) ? 'data-cart="' . $cartId . '"' : '') . ' class="link-dark btn-cart-remove">
                                <i class="fas fa-times-circle pe-1"></i>
                                Remove
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-inline-block px-3 col-md text-center text-md-start fs-5">
                    <span class="d-inline-block d-md-none">Total:</span>
                    <div class="fw-bold d-inline-block">' . $cartProduct->getProductTotalPrice($productIdAndQuantity[$cartProduct->id]) . ' €</div>
                </div>
            </div>
            ';
            $totalCartPrice += (float)$cartProduct->getProductTotalPrice($productIdAndQuantity[$cartProduct->id]);
        }
    } else {
        $cartHtml = '<div class="row cart-item p-3 border justify-content-center fs-5">Cart is empty!</div>';
    }

    $cartFooterHtml = '
    <div class="d-inline-block">
        <span class="d-block">Total:</span>
        <span class="fw-bold">' . number_format($totalCartPrice, 2, '.', '') . ' €</span>
    </div>
    
    ';
    //onclick="location.href=\'checkout.php\'"
    $cartFooterHtml .= '<button ' . (($error || empty($products)) ? 'disabled' : '') . ' class="ms-4 btn btn-primary cart-continue fs-5 fw-bold">Continue <i class="fas fa-arrow-right"></i></button>';
    
    $responseArray['cart'] = $cartHtml;
    $responseArray['footer'] = $cartFooterHtml;
}

$responseArray['cart_items'] = $cartItems;

//if (isset($cartId)) {
//    $responseArray['id'] = $cartId;
//}

echo json_encode($responseArray);

