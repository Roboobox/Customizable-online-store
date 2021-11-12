<?php
header('Content-type: application/json');
// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}
include_once '../conn.php';
$responseArray['status'] = 'success';
// Check if order id and new status is set
if (isset($_POST['id'], $_POST['status']) && !empty($_POST['id']) &&  !empty($_POST['status'])) {
    // Update order status
    $stmt = $conn->prepare("UPDATE `order` SET status = :status WHERE id = :orderId");
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':orderId', $_POST['id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode($responseArray);
        exit;
    }
}
$responseArray['status'] = 'error';
echo json_encode($responseArray);