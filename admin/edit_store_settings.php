<?php
// Check if user is admin
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}
include_once '../conn.php';
// Make prepared update statement
$stmt = $conn->prepare("UPDATE `store_setting` SET store_email = :email, store_address = :address, store_phonenr = :phoneNr, primary_color = :primaryColor, sale_color = :saleColor, positive_color = :positiveColor, about_text = :aboutText");
$updateCounter = 0;

$formPostValues = [':email' => 'storeEmail', ':address' => 'storeAddress', ':phoneNr' => 'storePhone', ':primaryColor' => 'storePrimaryClr', ':saleColor' => 'storeSaleClr', ':positiveColor' => 'storePositiveClr', ':aboutText' => 'storeAbout'];

foreach ($formPostValues as $paramValue => $postValue) {
    // Bind other values to prepared statement
    if (isset($_POST[$postValue]) && !empty($_POST[$postValue])) {
        $stmt->bindParam($paramValue, $_POST[$postValue]);
        $updateCounter++;
    } else {
        $stmt->bindValue($paramValue, null, PDO::PARAM_NULL);
        $updateCounter++;
    }
}
// If there are any successfully bound update values then execute
if ($updateCounter > 0) {
    $stmt->execute();
}

// Update store logo separately if update is needed
if (isset($_FILES['storeLogo']) && $_FILES['storeLogo']['size'] > 0) {
    // Get previous logo path
    $logoQuery = $conn->query("SELECT logo_path FROM store_setting");
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
        $stmt->execute();
    }
}
header('location: ../admin_dash.php');
exit;
