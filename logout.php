<?php
session_start();

$userToken = $_GET['token'] ?? '';
// Check if user CSRF token matches and destroy the session
if (hash_equals($_SESSION['user_token'], $userToken)) {
    session_destroy();
    header('Location: index.php');
    exit;
}