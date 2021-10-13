<?php

// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['prodName'], $_POST['prodCat'], $_POST['prodPrice'], $_FILES['prodImg'])
    && !empty($_POST['prodName']) && !empty($_POST['prodCat']) && !empty($_POST['prodPrice']) && !empty($_FILES['prodImg'])
) {
    include_once "../conn.php";
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

        $stmt = $conn->query("INSERT INTO product_inventory (quantity) VALUES (10)");
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
                $dir = "../test_images/";
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
        throw new Exception('Test exception');
        $conn->commit();
    }
    catch (Exception $e) {
        $conn->rollBack();
    }
}