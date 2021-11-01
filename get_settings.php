<?php
$stmt = $conn->query("SELECT * FROM store_setting");
$storeSettings = $stmt->fetch();
// Check logo orientation
if (isset($storeSettings['logo_path'])) {
    list($width, $height) = getimagesize('test_images/' . $storeSettings['logo_path']);
    // if height greater or equals to width then orientation is vertical
    if ($height >= $width) {
        $storeSettings['logo_orientation'] = 'vertical';
    } else {
        $storeSettings['logo_orientation'] = 'horizontal';
    }
}
?>
<style>
    :root {
        --primary-color: <?=$storeSettings['primary_color']?>;
        --sale-color: <?=$storeSettings['sale_color']?>;
        --positive-color: <?=$storeSettings['positive_color']?>;
    }
</style>
