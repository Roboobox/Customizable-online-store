<?php
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

$tab = 'Admin';
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/admin.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Admin dashboard</h2>
    </div>
    <div class="admin-container">
        <div class="col-3">
            <ul class="list-group list-group-flush">
                <li class="list-group-item active" aria-current="true">Create product</li>
                <li class="list-group-item">Placeholder</li>
                <li class="list-group-item">Placeholder</li>
                <li class="list-group-item">Placeholder</li>
                <li class="list-group-item">Placeholder</li>
            </ul>
        </div>
        <div class="col">
            <section>
                
            </section>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
