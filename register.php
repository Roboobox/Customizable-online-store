<?php
session_start();

$formErrors = array();

try {
    // Check if data is passed
    if (isset($_POST['email'], $_POST['password'], $_POST['c_password'])
        && $_SERVER['REQUEST_METHOD'] === "POST"
    ) {
        $_SESSION['auth_email'] = $_POST['email'];
        include_once('conn.php');

        $userEmail = $_POST['email'];
        $userPassword = $_POST['password'];
        $userConfirmPassword = $_POST['c_password'];

        // Validate email, password and password matching confirm password
        if (strlen($userEmail) > 254) {
            throw new Exception("Email address cannot exceed 254 characters!", 1);
        }
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email address is not valid!", 1);
        }
        else if (empty($userPassword) || strlen($userPassword) < 8) {
            throw new Exception("Password must be at least 8 characters!", 2);
        }
        else if (strlen($userPassword) > 72) {
            throw new Exception("Password cannot exceed 72 characters!", 2);
        }
        else if ($userPassword !== $userConfirmPassword) {
            throw new Exception("Passwords do not match!", 2);
        }

        // Try to get user with email entered by user in registration form
        $stmt = $conn->prepare("SELECT * FROM `user` WHERE email=:email");
        $stmt->bindParam(':email', $userEmail);
        $stmt->execute();

        // Check if user did not exist
        if ($stmt->rowCount() === 0) {
            // Hash user's password
            $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO `user` (`email`, `password_hash`, `role_id`) VALUES (:email, :passwordHash, :roleId)");
            $stmt->bindParam(':email', $userEmail);
            $stmt->bindParam(':passwordHash', $hashedPassword);
            $stmt->bindValue(':roleId', 0);
            $queryResult = $stmt->execute();

            // If successful then set message and redirect
            if ($queryResult) {
                $_SESSION['sign_success'] = "Account created, you can log in now!";
                header("Location: " . $_POST['redirect'] . '?&su=1');
                exit;
            }
            throw new Exception();
        } else {
            throw new Exception("User with this email address already exists!", 1);
        }
    } else {
        header("Location: index.php");
        exit;
    }
}
catch (Exception $e) {
    if ($e->getCode() === 1) {
        $formErrors['email'] = $e->getMessage();
    }
    else if ($e->getCode() === 2) {
        $formErrors['password'] = $e->getMessage();
    }
    else if ($e->getCode() === 3) {
        $formErrors['general'] = $e->getMessage();
    }
    else {
        $formErrors['general'] = 'Something went wrong. Please try again later! ';
    }
    $_SESSION['sign_error'] = $formErrors;
    header("Location: " . $_POST['redirect']);
    exit;
}