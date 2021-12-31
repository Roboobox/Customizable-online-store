<?php
header('Content-type: application/json');
// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    echo json_encode(array('status' => 'error'));
    exit;
}
// Include function for product validation
include "product_validation.php";
// Check if post variables set
if (isset($_POST['prodEdit'], $_POST['prodName'], $_POST['prodCat'], $_POST['prodPrice'], $_POST['prodInventory'])) {
    include_once "../conn.php";
    $product = null;
    $productImages = [];
    // Get product by selected product
    $stmtProduct = $conn->prepare('SELECT P.id AS product_id, pc.id AS category_id, pi.id AS inventory_id FROM product P LEFT JOIN product_category pc on P.category_id = pc.id LEFT JOIN product_inventory pi on P.inventory_id = pi.id WHERE P.id = :prodId');
    $stmtProduct->bindParam(':prodId', $_POST['prodEdit']);
    $stmtProduct->execute();
    // Check if product exists and one product returned
    if ($stmtProduct->rowCount() === 1) {
        // Get images
        $stmtProductImages = $conn->prepare('SELECT id, photo_path FROM product_photo WHERE product_id = :prodId');
        $stmtProductImages->bindParam(':prodId', $_POST['prodEdit']);
        $stmtProductImages->execute();
        $productImages = $stmtProductImages->fetchAll();
        $product = $stmtProduct->fetch();
    } else {
        echo json_encode(array('status' => 'error'));
        exit;
    }
    $formErrors = validateProductForm(False);
    $formErrors = array_merge($formErrors, editProductValidation($stmtProduct->rowCount(), count($productImages)));
    // If there are no form errors after validation
    if (empty($formErrors)) {
        // Remove whitespaces from start and end of strings
        $_POST['prodName'] = trim($_POST['prodName']);
        $_POST['prodCat'] = trim($_POST['prodCat']);
        if (isset($_POST['prodDesc']) && !empty($_POST['prodDesc'])) {
            $_POST['prodDesc'] = trim($_POST['prodDesc']);
        }

        // Update category
        // Check if new category exists
        $catSql = "SELECT name, id FROM product_category WHERE name = :catName";
        $stmt = $conn->prepare($catSql);
        $stmt->bindParam(':catName', $_POST['prodCat']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            // If category exists then get its ID
            $catId = $stmt->fetch()['id'];
        } else {
            // If category does not exist, then insert a new category
            $stmt = $conn->prepare("INSERT INTO product_category (name) VALUES (:prodCat)");
            $stmt->bindParam(':prodCat', $_POST['prodCat']);
            $stmt->execute();
            // If query successful then get ID of inserted category
            if ($stmt->rowCount() > 0) {
                $catId = $conn->lastInsertId();
            } else {
                throw new Exception('Failed to get category');
            }
        }

        // Update product inventory
        $stmtInventory = $conn->prepare("UPDATE `product_inventory` SET quantity = :quantity WHERE id = :invId");
        $stmtInventory->bindParam(':quantity', $_POST['prodInventory']);
        $stmtInventory->bindParam(':invId', $product['inventory_id']);
        $stmtInventory->execute();

        // Update specifications
        $stmtSpecification = $conn->prepare('DELETE FROM product_specification WHERE product_id = :prodId');
        $stmtSpecification->bindParam(':prodId', $product['product_id']);
        $stmtSpecification->execute();
        $specCount = count($_POST['specsLabel'] ?? []);
        for ($i = 0; $i < $specCount; $i++) {
            // If both specification values are set then insert them into database
            if (!empty($_POST['specsLabel'][$i]) && !empty($_POST['specsValue'][$i])) {
                $stmtSpecification = $conn->prepare("INSERT INTO product_specification (label, info, product_id) VALUES (:specLabel, :specValue, :prodId)");
                $stmtSpecification->bindValue(':specLabel', trim($_POST['specsLabel'][$i]));
                $stmtSpecification->bindValue(':specValue', trim($_POST['specsValue'][$i]));
                $stmtSpecification->bindParam(':prodId', $product['product_id']);
                $stmtSpecification->execute();
            }
        }

        // Update photos
        // Get user uploaded photos
        $uploadedImages = $_FILES['prodImg'];
        $uploadedCount = count($uploadedImages['name']);
        for ($i = 0; $i < $uploadedCount; $i++) {
            // Check if no file error is not present
            if ($uploadedImages['error'][$i] != 4) {
                // Get new filename and path for photo
                $dir = "../images/";
                $fileName = $product['product_id'] . '_' . $i . '_' . md5($uploadedImages["name"][$i]) . '.' . pathinfo($uploadedImages["name"][$i], PATHINFO_EXTENSION);
                $path = $dir . $fileName;
                // If photo successfully saved then insert path into database
                if (move_uploaded_file($uploadedImages['tmp_name'][$i], $path)) {
                    $stmt = $conn->prepare("INSERT INTO product_photo (photo_path, product_id) VALUES (:fileName, :prodId)");
                    $stmt->bindParam(':fileName', $fileName);
                    $stmt->bindParam(':prodId', $product['product_id']);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to save image');
                    }
                } else {
                    throw new Exception('Failed to save image');
                }
            }
        }
        // Delete user selected to delete images
        $imgDeleteCount = count($_POST['imgDelete'] ?? []);
        if ($imgDeleteCount > 0) {
            $imgIdsInTemplate = implode(',', array_fill(0, count($_POST['imgDelete']), '?'));
            $stmtDeleteImages = $conn->prepare("DELETE FROM product_photo WHERE id IN (".$imgIdsInTemplate.")");
            $stmtDeleteImages->execute($_POST['imgDelete']);
        }

        // Update product
        $stmtProduct = $conn->prepare("UPDATE product SET name = :prodName, category_id = :ctgId, price = :prodPrice, description = :prodDesc WHERE id = :prodId");
        $stmtProduct->bindParam(':prodId', $product['product_id']);
        $stmtProduct->bindParam(':prodName', $_POST['prodName']);
        $stmtProduct->bindParam(':ctgId', $catId);
        $stmtProduct->bindParam(':prodPrice', $_POST['prodPrice']);
        if (isset($_POST['prodDesc']) && !empty($_POST['prodDesc'])) {
            $stmtProduct->bindParam(':prodDesc', $_POST['prodDesc']);
        } else {
            $stmtProduct->bindValue(':prodDesc', null, PDO::PARAM_NULL);
        }
        $stmtProduct->execute();
    }
    // Return results
    if (empty($formErrors)) {
        echo json_encode(array('status' => 'success', 'formSuccess' => 'Product updated successfully!'));
        exit;
    }
    if (array_key_exists('general', $formErrors)) {
        echo json_encode(array('status' => 'error'));
        exit;
    }
    echo json_encode(array('formErrors' => $formErrors));
    exit;
}

echo json_encode(array('status' => 'error'));

// Unique validation for edit product fields
function editProductValidation($resultProductCnt, $productImagesCnt) {
    $formErrors = array();
    // Product select validation
    $prodEdit = $_POST['prodEdit'];
    if (empty($prodEdit) || $resultProductCnt !== 1) {
        $formErrors['prodEdit'] = 'Valid product is required!';
    }

    // Product image validation
    $uploadedImages = $_FILES['prodImg'];
    $uploadedCount = count($uploadedImages['name']);
    // Check for no file uploaded error
    if ($uploadedCount == 1 && $uploadedImages['error'][0] == 4) {
        $uploadedCount = 0;
    }
    if ($uploadedCount < 1 && count($_POST['imgDelete'] ?? []) >= $productImagesCnt) {
        $formErrors['prodImg'] = 'At least one product image is required!';
    }
    return $formErrors;
}