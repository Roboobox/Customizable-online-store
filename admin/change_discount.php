<?php
session_start();

$userToken = $_GET['token'] ?? '';
// Check if user is admin and if user token matches
if (!hash_equals($_SESSION['user_token'], $userToken) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}
// Check if discount id is provided
if (isset($_GET['id']) || !empty($_GET['id'])) {
    $formErrors = array();
    include_once "../conn.php";
    // Get discount with provided id and check if it exists
    $stmt = $conn->prepare("SELECT is_active FROM `product_discount` WHERE id = :id");
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get discount status and update it with opposite status
        $result = $stmt->fetch()['is_active'];
        $stmt = $conn->prepare("UPDATE `product_discount` SET is_active = :isActive WHERE id = :id");
        $stmt->bindValue(':isActive', !$result);
        $stmt->bindValue(':id', $_GET['id']);
        $stmt->execute();
        // Check if query successful
        if ($stmt->rowCount() == 0) {
            $formErrors['general'] = 'Something went wrong, try again later!';
        }
    } else {
        $formErrors['general'] = 'Something went wrong, try again later!';
    }
    // Return validation results
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Status changed successfully!';
    }
}
header('Location: ../admin_dash.php?p=product_discounts');
exit;
