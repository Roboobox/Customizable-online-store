<?php

// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['discountProduct'], $_POST['discountPercent'], $_POST['discountStart'], $_POST['discountEnd'])
    && !empty($_POST['discountProduct']) && !empty($_POST['discountPercent']) && !empty($_POST['discountStart']) && !empty($_POST['discountEnd'])
) {
    include_once "../conn.php";
    $stmt = $conn->prepare('INSERT INTO product_discount (discount_percent, is_active, starting_at, ending_at, product_id) VALUES (:percent, :active, :start, :end, :productId)');
    $stmt->bindParam(':percent', $_POST['discountPercent']);
    $stmt->bindValue(':active', 1, PDO::PARAM_INT);
    $stmt->bindParam(':start', $_POST['discountStart']);
    $stmt->bindParam(':end', $_POST['discountEnd']);
    $stmt->bindParam(':productId', $_POST['discountProduct']);
    $stmt->execute();
    header('Location: ../admin_dash.php?p=product_discounts');
    exit;
}