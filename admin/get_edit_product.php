<?php
header('Content-type: application/json');
session_start();
$responseArray['status'] = 'error';
if (!isset($_POST['id']) || !isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    echo json_encode($responseArray);
    exit;
}
include_once "../conn.php";
// Main product selection sql
$productSql = "
        SELECT P.*, I.quantity, C.name AS category FROM `product` P
        LEFT JOIN product_inventory I ON P.inventory_id = I.id
        LEFT JOIN product_category C ON P.category_id = C.id
        WHERE P.id = :prodId AND P.is_deleted = (0)
";
$stmtProduct = $conn->prepare($productSql);
$stmtProduct->bindParam(':prodId', $_POST['id']);
$stmtProduct->execute();
// Check if one product returned
if ($stmtProduct->rowCount() !== 1) {
    echo json_encode($responseArray);
    exit;
}
$product= $stmtProduct->fetch();
// Get specifications and products
$stmtSpecifications = $conn->prepare("SELECT label, info FROM product_specification WHERE product_id=:prodId");
$stmtPhotos = $conn->prepare("SELECT id, photo_path FROM product_photo WHERE product_id=:prodId");
$stmtSpecifications->bindParam(':prodId', $_POST['id']);
$stmtPhotos->bindParam(':prodId', $_POST['id']);
$stmtSpecifications->execute();
$stmtPhotos->execute();
// Return the product data
try {
    $responseArray['product'] = array(
        'name' => $product['name'],
        'category' => $product['category'],
        'price' => $product['price'],
        'description' => $product['description'],
        'specifications' => $stmtSpecifications->fetchAll(PDO::FETCH_ASSOC),
        'inventory' => $product['quantity'],
        'photos' => $stmtPhotos->fetchAll(PDO::FETCH_ASSOC)
    );
    $responseArray['status'] = 'success';
}
catch (Exception $e) {
    echo json_encode(array('status' => 'error'));
    exit;
}
echo json_encode($responseArray);
exit;