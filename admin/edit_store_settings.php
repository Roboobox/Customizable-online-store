<?php
// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['storeEmail'], $_POST['storeAddress'], $_POST['storePhone'], $_POST['storePrimaryClr'], $_POST['storeSaleClr'], $_POST['storePositiveClr'], $_POST['storeAbout'])) {
    include_once "../conn.php";
    $formErrors = validateForm();
    // If there are no form errors after validation
    if (empty($formErrors)) {
        try {
            // Make prepared update statement
            $stmt = $conn->prepare("UPDATE `store_setting` SET store_email = :email, store_address = :address, store_phonenr = :phoneNr, primary_color = :primaryColor, sale_color = :saleColor, positive_color = :positiveColor, about_text = :aboutText");
            $updateCounter = 0;

            $formPostValues = [':email' => 'storeEmail', ':address' => 'storeAddress', ':phoneNr' => 'storePhone', ':primaryColor' => 'storePrimaryClr', ':saleColor' => 'storeSaleClr', ':positiveColor' => 'storePositiveClr', ':aboutText' => 'storeAbout'];

            foreach ($formPostValues as $paramValue => $postValue) {
                // Bind other values to prepared statement
                if (!empty($_POST[$postValue])) {
                    $stmt->bindParam($paramValue, $_POST[$postValue]);
                    $updateCounter++;
                } else {
                    $stmt->bindValue($paramValue, null, PDO::PARAM_NULL);
                    $updateCounter++;
                }
            }
            // If there are any successfully bound update values then execute
            if ($updateCounter > 0) {
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update store settings values');
                }
            }

            // Update store logo separately if update is needed
            if ($_FILES['storeLogo']['size'] > 0) {
                // Get previous logo path
                $logoQuery = $conn->query("SELECT logo_path FROM store_setting");
                if ($logoQuery->rowCount() == 0) {
                    throw new Exception('Failed to get logo path from database');
                }
                $logoPath = $logoQuery->fetch()['logo_path'];
                // Delete previous logo except if it is default logo
                if ($logoPath != 'logo.png') {
                    $filePath = '../test_images/' . $logoPath;
                    unlink($filePath);
                }
                // Determine new logo path and save it
                $dir = '../test_images/';
                $newLogoFilename = 'logo_' . md5($_FILES['storeLogo']['name']) . '.' . pathinfo($_FILES['storeLogo']['name'], PATHINFO_EXTENSION);
                $newLogoPath = $dir . $newLogoFilename;
                // If file saved successfully, save path to database
                if (move_uploaded_file($_FILES['storeLogo']['tmp_name'], $newLogoPath)) {
                    $stmt = $conn->prepare("UPDATE `store_setting` SET logo_path = :logoPath");
                    $stmt->bindParam(':logoPath', $newLogoFilename);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to insert new logo');
                    }
                }
            }
        }
        catch (Exception $e) {
            $formErrors['general'] = 'Something went wrong, try again later!';
        }
    }
    // Return validation results
    $_SESSION['formErrors'] = $formErrors;
    if (empty($formErrors)) {
        $_SESSION['formSuccess'] = 'Store settings saved successfully!';
    }
}

function validateForm(): array {
    $formErrors = array();
    // Email validation
    $storeEmail = $_POST['storeEmail'];
    if (!empty($storeEmail)) {
        if (strlen($storeEmail) > 254) {
            $formErrors['storeEmail'] = 'Store email address cannot exceed 254 characters!';
        } else if (!filter_var($storeEmail, FILTER_VALIDATE_EMAIL)) {
            $formErrors['storeEmail'] = 'Store email address is not a valid email address!';
        }
    }

    // Address validation
    $storeAddress = $_POST['storeAddress'];
    if (!empty($storeAddress) && strlen($storeAddress) > 255) {
        $formErrors['storeErrors'] = 'Store address cannot exceed 255 characters!';
    }

    // Phone number validation
    $storePhoneNr = $_POST['storePhone'];
    if (!empty($storePhoneNr) && !preg_match('/^[0-9]{1,31}$/', $storePhoneNr)) {
        $formErrors['storePhone'] = 'Store phone number can only contain digits and cannot exceed 31 digits!';
    }

    // Store logo validation
    $storeLogo = $_FILES['storeLogo'];
    if (!empty($storeLogo) && !empty($storeLogo['name'])) {
        if ($storeLogo['type'] != "image/png" && $storeLogo['type'] != "image/jpg" && $storeLogo['type'] != "image/jpeg") {
            $formErrors['storeLogo'] = 'Store logo can only have JPG or PNG file format!';
        }
    }

    // About us validation
    $storeAbout = $_POST['storeAbout'];
    if (!empty($storeAbout) && strlen($storeAbout) > 65535) {
        $formErrors['storeAbout'] = 'About us information cannot exceed 65,535 characters!';
    }

    return $formErrors;
}

header('location: ../admin_dash.php?p=store_settings');
exit;
