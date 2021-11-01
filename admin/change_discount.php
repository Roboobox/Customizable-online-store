<?php
session_start();

$userToken = $_GET['token'] ?? '';

if (!hash_equals($_SESSION['user_token'], $userToken) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['id']) || !empty($_GET['id'])) {
    $formErrors = array();
    include_once "../conn.php";
    $stmt = $conn->prepare("SELECT is_active FROM `product_discount` WHERE id = :id");
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch()['is_active'];
        $stmt = $conn->prepare("UPDATE `product_discount` SET is_active = :isActive WHERE id = :id");
        $stmt->bindValue(':isActive', !$result);
        $stmt->bindValue(':id', $_GET['id']);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $formErrors['general'] = 'Something went wrong, try again later!';
        }
    } else {
        $formErrors['general'] = 'Something went wrong, try again later!';
    }
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Status changed successfully!';
    }
}
header('Location: ../admin_dash.php?p=product_discounts');
exit;
