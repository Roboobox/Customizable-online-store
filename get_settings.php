<?php
$stmt = $conn->query("SELECT * FROM store_setting");
$storeSettings = $stmt->fetch();
?>
<style>
    :root {
        --primary-color: <?=$storeSettings['primary_color']?>;
        --sale-color: <?=$storeSettings['sale_color']?>;
        --positive-color: <?=$storeSettings['positive_color']?>;
    }
</style>
