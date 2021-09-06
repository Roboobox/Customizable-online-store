<?php
session_start();

$userToken = $_GET['token'] ?? '';

if (hash_equals($_SESSION['user_token'], $userToken)) {
    session_destroy();
    header('Location: index.php');
    exit;
}