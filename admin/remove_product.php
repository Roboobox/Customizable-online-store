<?php
session_start();

$userToken = $_GET['token'] ?? '';
// Check if user is admin and if user token matches
if (!hash_equals($_SESSION['user_token'], $userToken) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}
// Check if product id is provided
if (isset($_GET['id']) || !empty($_GET['id'])) {
    include_once "../conn.php";
    // Get product by id and check if it exists
    $stmt = $conn->prepare("SELECT id FROM product WHERE id = :prodId AND is_deleted = (0)");
    $stmt->bindParam(':prodId', $_GET['id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Update product and set it as deleted
        $prodDeleteStmt = $conn->prepare("UPDATE `product` SET is_deleted = :isDeleted WHERE id = :prodId");
        $prodDeleteStmt->bindParam(':prodId', $_GET['id']);
        $prodDeleteStmt->bindValue(':isDeleted', 1, PDO::PARAM_INT);
        if (!$prodDeleteStmt->execute()) {
            $formErrors['general'] = 'Something went wrong, try again later!';
        }
    } else {
        $formErrors['general'] = 'Something went wrong, try again later!';
    }
}
// Return validation results
$_SESSION['formErrors'] = $formErrors;
if (empty($formErrors)) {
    $_SESSION['formSuccess'] = 'Product deleted successfully!';
}
header('Location: ../admin_dash.php?p=delete_product');
exit;
