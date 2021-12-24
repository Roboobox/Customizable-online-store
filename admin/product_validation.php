<?php
function validateProductForm($createForm = True) {
    $formErrors = array();
    // Check CSRF token
    if (!empty($_POST) && (!isset($_SESSION['user_token']) || !hash_equals($_SESSION['user_token'], $_POST['token'] ?? ''))) {
        $formErrors['general'] = 'Something went wrong, try again later!';
        return $formErrors;
    }
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
        $formErrors['prodPrice'] = 'Product price must be a positive number!';
    }

    // Product description validation
    if (isset($_POST['prodDesc']) && strlen($_POST['prodDesc']) > 65535) {
        $formErrors['prodDesc'] = 'Product description cannot exceed 65,535 characters!';
    }

    // Product specification validation
    if (isset($_POST['specsLabel'])) {
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
    }

    // Product image validation
    // If validation is not for product create form then check for at least one image uploaded is not needed
    $uploadedImages = $_FILES['prodImg'];
    $uploadedCount = count($uploadedImages['name']);
    if ($uploadedCount > 0) {
        for ($i = 0; $i < $uploadedCount; $i++) {
            if ($uploadedImages["type"][$i] != "image/png" && $uploadedImages["type"][$i] != "image/jpg" && $uploadedImages["type"][$i] != "image/jpeg") {
                if ($uploadedImages['error'][$i] == 4 && !$createForm) {
                    continue;
                }
                $formErrors['prodImg'] = 'Product image is required and can only have JPG or PNG file format!';
                $formErrors['test'] = $_FILES['prodImg'];
                break;
            }
        }
    } else if ($createForm) {
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