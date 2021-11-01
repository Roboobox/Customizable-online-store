<?php

// Check if user is admin
//session_start();
//if ($_SESSION['user_role'] != 1) {
//    header('Location: index.php');
//    exit;
//}

if (isset($_POST['discountProduct'], $_POST['discountPercent'], $_POST['discountStart'], $_POST['discountEnd'])) {
    $formErrors = validateForm();
    if (empty($formErrors)) {
        //include_once "../conn.php";
        // Check if product exists
        $stmtProduct = $conn->prepare("SELECT id FROM product WHERE id = :productId");
        $stmtProduct->bindParam(':productId', $_POST['discountProduct']);
        $stmtProduct->execute();

        if ($stmtProduct->rowCount() == 1) {
            $stmt = $conn->prepare('INSERT INTO product_discount (discount_percent, is_active, starting_at, ending_at, product_id) VALUES (:percent, :active, :start, :end, :productId)');
            $stmt->bindParam(':percent', $_POST['discountPercent']);
            $stmt->bindValue(':active', 1, PDO::PARAM_INT);
            $stmt->bindParam(':start', $_POST['discountStart']);
            $stmt->bindParam(':end', $_POST['discountEnd']);
            $stmt->bindParam(':productId', $_POST['discountProduct']);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $formErrors['general'] = 'Something went wrong, try again later!';
            }
        } else {
            $formErrors['general'] = 'Something went wrong, try again later!';
        }
    }
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Discount added successfully!';
    }
//    header('Location: ../admin_dash.php?p=product_discounts');
//    exit;
}

function validateForm(): array {
    $formErrors = array();
    // Product validation
    $product = $_POST['discountProduct'];
    if (empty($product)) {
        $formErrors['discountProduct'] = 'Product must be selected!';
    }

    // Discount percent validation
    $discPercent = $_POST['discountPercent'];
    $intPercent = (int)$discPercent;
    if (empty($discPercent) || !((string)($intPercent) == $discPercent && $intPercent >= 1 && $intPercent <= 100)) {
        $formErrors['discountPercent'] = 'Discount percent must be an integer from 1 to 100!';
    }

    // Discount start datetime validation
    $discStart = $_POST['discountStart'];
    if (empty($discStart) || DateTime::createFromFormat('Y-m-d\TH:i', $discStart) === false) {
        $formErrors['discountStart'] = 'Discount start must be a valid date and time!';
    }

    // Discount end datetime validation
    $discEnd = $_POST['discountEnd'];
    if (!empty($discEnd)) {
        $startDate = DateTime::createFromFormat('Y-m-d\TH:i', $discStart);
        $endDate = DateTime::createFromFormat('Y-m-d\TH:i', $discEnd);
        if (!$startDate || !$endDate || $startDate >= $endDate) {
            $formErrors['discountEnd'] = 'Discount end must be a valid date and time and must be after discount start time!';
        }
    } else {
        $formErrors['discountEnd'] = 'Discount end must be a valid date and time and must be after discount start time!';
    }

    return $formErrors;
}