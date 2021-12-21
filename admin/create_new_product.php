<?php
// Check if post variables set
if (isset($_POST['prodName'], $_POST['prodCat'], $_POST['prodPrice'], $_FILES['prodImg'], $_POST['prodInventory'])) {
    // Includes function for product validation
    include_once "product_validation.php";
    $formErrors = validateProductForm();
    // If there are no form errors after validation
    if (empty($formErrors)) {
        // Remove whitespaces from start and end of strings
        $_POST['prodName'] = trim($_POST['prodName']);
        $_POST['prodCat'] = trim($_POST['prodCat']);
        if (isset($_POST['prodDesc']) && !empty($_POST['prodDesc'])) {
            $_POST['prodDesc'] = trim($_POST['prodDesc']);
        }
        $conn->beginTransaction();
        try {
            // Get user product category and check if it already exists
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

            // Insert product inventory amount
            $stmt = $conn->prepare("INSERT INTO product_inventory (quantity) VALUES (:prodInventory)");
            $stmt->bindParam(':prodInventory', $_POST['prodInventory']);
            $stmt->execute();
            // If query successful then get inserted inventory's ID
            if ($stmt->rowCount() > 0) {
                $invId = $conn->lastInsertId();
            } else {
                throw new Exception('Failed to get inventory');
            }

            // Prepare product insertion SQL and bind values
            $prodSql = "INSERT INTO product (name, description, price, category_id, inventory_id) VALUES (:prodName, :prodDesc, :prodPrice, :prodCatId, :prodInvId)";
            $stmt = $conn->prepare($prodSql);
            $stmt->bindParam(':prodName', $_POST['prodName']);
            // Bind description if it is set
            if (isset($_POST['prodDesc']) && !empty($_POST['prodDesc'])) {
                $stmt->bindParam(':prodDesc', $_POST['prodDesc']);
            } else {
                $stmt->bindValue(':prodDesc', null, PDO::PARAM_NULL);
            }
            $stmt->bindParam(':prodPrice', $_POST['prodPrice']);
            $stmt->bindParam(':prodCatId', $catId);
            $stmt->bindParam(':prodInvId', $invId);
            $stmt->execute();
            // If product inserted successfully, start product image and specification insertion
            if ($stmt->rowCount() > 0) {
                // Get inserted product ID
                $prodId = $conn->lastInsertId();

                // Get user uploaded photos
                $uploadedImages = $_FILES['prodImg'];
                $uploadedCount = count($uploadedImages['name']);
                for ($i = 0; $i < $uploadedCount; $i++) {
                    // Get new filename and path for photo
                    $dir = "images/";
                    $fileName = $prodId . '_' . $i . '_' . md5($uploadedImages["name"][$i]) . '.' . pathinfo($uploadedImages["name"][$i], PATHINFO_EXTENSION);
                    $path = $dir . $fileName;
                    // If photo successfully saved then insert path into database
                    if (move_uploaded_file($uploadedImages['tmp_name'][$i], $path)) {
                        $stmt = $conn->prepare("INSERT INTO product_photo (photo_path, product_id) VALUES (:fileName, :prodId)");
                        $stmt->bindParam(':fileName', $fileName);
                        $stmt->bindParam(':prodId', $prodId);
                        if (!$stmt->execute()) {
                            throw new Exception('Failed to save image');
                        }
                    } else {
                        throw new Exception('Failed to save image');
                    }
                }

                // Check if product specifications set and are not empty
                if (isset($_POST['specsLabel'], $_POST['specsValue']) && !empty($_POST['specsLabel']) && !empty($_POST['specsValue'])) {
                    $specCount = count($_POST['specsLabel']);
                    for ($i = 0; $i < $specCount; $i++) {
                        // If both specification values are set then insert them into database
                        if (!empty($_POST['specsLabel'][$i]) && !empty($_POST['specsValue'][$i])) {
                            $stmt = $conn->prepare("INSERT INTO product_specification (label, info, product_id) VALUES (:specLabel, :specValue, :prodId)");
                            $stmt->bindValue(':specLabel', trim($_POST['specsLabel'][$i]));
                            $stmt->bindValue(':specValue', trim($_POST['specsValue'][$i]));
                            $stmt->bindParam(':prodId', $prodId);
                            if (!$stmt->execute()) {
                                throw new Exception('Failed to insert specification');
                            }
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
    // Return validation results
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Product created successfully!';
    }
}