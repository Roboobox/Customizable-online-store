<?php
session_start();

$emailError = false;
$passwordError = false;
$formErrors = array();

if (isset($_POST['email'], $_POST['password'], $_POST['c_password'])
    && $_SERVER['REQUEST_METHOD'] === "POST"
) {
    include_once('conn.php');

    $filteredEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    $filteredPassword = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $filteredConfirmPassword = filter_input(INPUT_POST, 'c_password', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (strlen($filteredEmail) < 8) {
        
    }
    
    $stmt = $conn->prepare("SELECT * FROM `user` WHERE email=:email");
    $stmt->bindParam(':email', $filteredEmail);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        if ($filteredPassword === $filteredConfirmPassword) {
            $hashedPassword = password_hash($filteredPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO `user` (`email`, `password_hash`) VALUES (:email, :passwordHash)");
            $stmt->bindParam(':email', $filteredEmail);
            $stmt->bindParam(':passwordHash', $hashedPassword);
            $queryResult = $stmt->execute();

            if ($queryResult) {
                header("Location: " . $_POST['redirect']);
                exit;
            }
        }
        else {
            $formErrors['password'] = "Passwords do not match!";
        }
    }
    else {
        $formErrors['email'] = "User with this email address already exists!";
    }
}

$_SESSION['sign_error'] = $formErrors;
header("Location: " . $_POST['redirect']);
exit;