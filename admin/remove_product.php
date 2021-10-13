<?php
session_start();

$userToken = $_GET['token'] ?? '';

if (!hash_equals($_SESSION['user_token'], $userToken) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id']) || !empty($_GET['id'])) {
    include_once "../conn.php";
    $stmt = $conn->prepare("SELECT P.inventory_id FROM product P WHERE id = :prodId");
    $stmt->bindParam(':prodId', $_GET['id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $stmt = $conn->prepare("DELETE FROM product_inventory WHERE id = :invId");
        $stmt->bindParam(':invId', $row['inventory_id']);
        $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM product WHERE id = :prodId");
        $stmt->bindParam(':prodId', $_GET['id']);
        $stmt->execute();
        header('Location: ../admin_dash.php?p=delete_product');
        exit;
    }
}
header('location: index.php');
exit;
