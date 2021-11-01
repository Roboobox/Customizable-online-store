<?php
$tab = 'About';
include_once 'head.php';
include_once 'header.php';

// Remove special characters and convert new lines to <br> tags
$aboutData = nl2br(htmlspecialchars($storeSettings['about_text'] ?? ''));

if (empty($aboutData)) {
    $aboutData = 'No about us information';
}

// Turn **Title** formats to headings and add paragraphs
$aboutData = '<p>' . preg_replace("/\*{2}([^*]*)\*{2}/", '</p><h4 class="mb-1 mt-3">$1</h4><hr><p>', $aboutData). '</p>';
// Remove empty paragraphs
$aboutData = str_replace('<p></p>', '', $aboutData);


?>
<link href="css/about.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">About us</h2>
    </div>
    <div class="row main-text px-3">
        <?=$aboutData?>
    </div>
</div>

<?php include_once 'footer.php' ?>

