<?php
include_once "conn.php";
// Get store settings
$stmt = $conn->query("SELECT * FROM store_setting");
$storeSettings = $stmt->fetch();
// Check logo orientation
if (isset($storeSettings['logo_path'])) {
    list($width, $height) = getimagesize('images/' . $storeSettings['logo_path']);
    // if height greater or equals to width then orientation is vertical
    if ($height >= $width) {
        $storeSettings['logo_orientation'] = 'vertical';
    } else {
        $storeSettings['logo_orientation'] = 'horizontal';
    }
}

// Check if hex color is almost white or white
function isLightColor(string $hex): bool {
    // Check if hex string matches format #XXXXXX
    if (!preg_match('/#[a-zA-Z0-9]{6}/', $hex)) {
        return false;
    }
    // Remove '#' character from hex string
    $hex = ltrim($hex, '#');
    // Split hex into separate values and convert them to decimal value
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    // Check if combined value is higher than 700 (white and almost white)
    return $r + $g + $b > 700;
}
?>
<style>
    :root {
        --primary-color: <?=$storeSettings['primary_color']?>;
        --navigation-color: <?=$storeSettings['navigation_color']?>;
        --sale-color: <?=$storeSettings['sale_color']?>;
        --positive-color: <?=$storeSettings['positive_color']?>;
        --primary-element-color: <?=(isLightColor($storeSettings['primary_color']) ? '#343a40' : $storeSettings['primary_color'])?>;
        --primary-text-color: <?=(isLightColor($storeSettings['primary_color']) ? '#343a40' : '#fff')?>;
        --nav-color: <?=(isLightColor($storeSettings['primary_color']) ? '#b2b2b2' : '#ebebeb')?>;
        --positive-color-text: <?=(isLightColor($storeSettings['positive_color']) ? '#343a40' : '#fff')?>;
    }
</style>
