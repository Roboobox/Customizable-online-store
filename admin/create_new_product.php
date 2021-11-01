<?php

// Check if user is admin
//session_start();
//if ($_SESSION['user_role'] != 1) {
//    header('Location: index.php');
//    exit;
//}

if (isset($_POST['prodName'], $_POST['prodCat'], $_POST['prodPrice'], $_FILES['prodImg'], $_POST['prodInventory'])) {
    $formErrors = validateForm();
    // If there are no form errors after validation
    if (empty($formErrors)) {
        //include_once "conn.php";
        $error = false;
        $conn->beginTransaction();
        try {
            $catSql = "SELECT name, id FROM product_category WHERE name = :catName";
            $stmt = $conn->prepare($catSql);
            $stmt->bindParam(':catName', $_POST['prodCat']);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $catId = $stmt->fetch()['id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO product_category (name) VALUES (:prodCat)");
                $stmt->bindParam(':prodCat', $_POST['prodCat']);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $catId = $conn->lastInsertId();
                } else {
                    throw new Exception('Failed to get category');
                }
            }

            $stmt = $conn->prepare("INSERT INTO product_inventory (quantity) VALUES (:prodInventory)");
            $stmt->bindParam(':prodInventory', $_POST['prodInventory']);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $invId = $conn->lastInsertId();
            } else {
                throw new Exception('Failed to get inventory');
            }

            //INSERT INTO product (name, description, price, category_id, inventory_id) VALUES ('Sbox Screen Cleaner 200ml CS-02', NULL, 3.20, 1, 1);
            $prodSql = "INSERT INTO product (name, description, price, category_id, inventory_id) VALUES (:prodName, :prodDesc, :prodPrice, :prodCatId, :prodInvId)";
            $stmt = $conn->prepare($prodSql);
            $stmt->bindParam(':prodName', $_POST['prodName']);
            if (isset($_POST['prodDesc']) && !empty($_POST['prodDesc'])) {
                $stmt->bindParam(':prodDesc', $_POST['prodDesc']);
            } else {
                $stmt->bindValue(':prodDesc', null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(':prodPrice', $_POST['prodPrice']);
            $stmt->bindParam(':prodCatId', $catId);
            $stmt->bindParam(':prodInvId', $invId);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $prodId = $conn->lastInsertId();
                // Product images
                $uploadedImages = $_FILES['prodImg'];
                $uploadedCount = count($uploadedImages['name']);
                for ($i = 0; $i < $uploadedCount; $i++) {
                    $dir = "test_images/";
                    $fileName = $prodId . '_' . $i . '_' . md5($uploadedImages["name"][$i]) . '.' . pathinfo($uploadedImages["name"][$i], PATHINFO_EXTENSION);
                    $path = $dir . $fileName;
                    if (move_uploaded_file($uploadedImages['tmp_name'][$i], $path)) {
                        $stmt = $conn->prepare("INSERT INTO product_photo (photo_path, product_id) VALUES (:fileName, :prodId)");
                        $stmt->bindParam(':fileName', $fileName);
                        $stmt->bindParam(':prodId', $prodId);
                        $stmt->execute();
                    } else {
                        throw new Exception('Failed to save image');
                    }
                }

                // Product specs
                if (isset($_POST['specsLabel'], $_POST['specsValue']) && !empty($_POST['specsLabel']) && !empty($_POST['specsValue'])) {
                    $specCount = count($_POST['specsLabel']);
                    for ($i = 0; $i < $specCount; $i++) {
                        if (!empty($_POST['specsLabel'][$i]) && !empty($_POST['specsValue'][$i])) {
                            $stmt = $conn->prepare("INSERT INTO product_specification (label, info, product_id) VALUES (:specLabel, :specValue, :prodId)");
                            $stmt->bindParam(':specLabel', $_POST['specsLabel'][$i]);
                            $stmt->bindParam(':specValue', $_POST['specsValue'][$i]);
                            $stmt->bindParam(':prodId', $prodId);
                            $stmt->execute();
                        }
                    }
                }
            } else {
                throw new Exception('Failed to get product');
            }
            $conn->commit();
        } catch (Exception $e) {
            $formErrors['general'] = 'Something went wrong, try again later!';
            $conn->rollBack();
        }
    }
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Product created successfully!';
    }
}

function validateForm() {
    $formErrors = array();
    // Product name validation
    $prodName = $_POST['prodName'];
    if (empty($prodName) || strlen($prodName) > 255) {
        $formErrors['prodName'] = 'Product name must be 1 to 255 characters in length!';
    }

    // Product category validation
    $prodCategory = $_POST['prodCat'];
    if (empty($prodCategory) || strlen($prodCategory) > 100) {
        $formErrors['prodCat'] = 'Product category must be 1 to 100 characters in length!';
    }

    // Product price validation
    $prodPrice = $_POST['prodPrice'];
    $floatPrice = (float)($prodPrice);
    if (empty($prodPrice) || !((string)($floatPrice) == $prodPrice && $prodPrice > 0)) {
        $formErrors['prodPrice'] = 'Product price must be a positive decimal number!';
    }

    // Product description validation
    if (isset($_POST['prodDesc']) && strlen($_POST['prodDesc']) > 65535) {
        $formErrors['prodDesc'] = 'Product description cannot exceed 65,535 characters!';
    }

    // Product specification validation
    $specCount = count($_POST['specsLabel']);
    for ($i = 0; $i < $specCount; $i++) {
        if (!empty($_POST['specsLabel'][$i]) || !empty($_POST['specsValue'][$i])) {
            if (empty($_POST['specsLabel'][$i]) || empty($_POST['specsValue'][$i])) {
                $formErrors['specs'] = 'Product specification must have both values filled!';
                break;
            }
            if (strlen($_POST['specsLabel'][$i]) > 50 || strlen($_POST['specsValue'][$i]) > 100) {
                $formErrors['specs'] = 'Product specification label cannot exceed 50 characters and value cannot exceed 100 characters!';
                break;
            }
        }
    }

    // Product image validation
    $uploadedImages = $_FILES['prodImg'];
    $uploadedCount = count($uploadedImages['name']);
    if ($uploadedCount > 0) {
        for ($i = 0; $i < $uploadedCount; $i++) {
            if ($uploadedImages["type"][$i] != "image/png" && $uploadedImages["type"][$i] != "image/jpg" && $uploadedImages["type"][$i] != "image/jpeg") {
                $formErrors['prodImg'] = 'Product image is required and can only have JPG or PNG file format!';
                break;
            }
        }
    } else {
        $formErrors['prodImg'] = 'Product image is required and can only have JPG or PNG file format!';
    }

    // Product inventory validation
    $prodInventory = $_POST['prodInventory'];
    $intInventory = (int)$prodInventory;
    if (empty($prodInventory) || !((string)($intInventory) == $prodInventory && $intInventory >= 1 && $intInventory <= 10000)) {
        $formErrors['prodInventory'] = 'Product inventory quantity must be an integer between 1 and 10000!';
    }
    return $formErrors;
}