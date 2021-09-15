<?php
session_start();

$formErrors = array();

try {
    if (isset($_POST['email'], $_POST['password'], $_POST['c_password'])
        && $_SERVER['REQUEST_METHOD'] === "POST"
    ) {
        $_SESSION['auth_email'] = $_POST['email'];
        include_once('conn.php');

        $filteredEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
        $filteredPassword = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        $filteredConfirmPassword = filter_input(INPUT_POST, 'c_password', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!filter_var($filteredEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email address is not valid", 1);
        }
        else if (empty($filteredPassword) || strlen($filteredPassword) < 8) {
            throw new Exception("Password must be at least 8 characters", 2);
        }
        else if (strlen($filteredPassword) > 72) {
            throw new Exception("Password cannot exceed 72 characters", 2);
        }
        else if (strlen($filteredEmail) > 254) {
            throw new Exception("Email address cannot exceed 254 characters", 1);
        }

        $stmt = $conn->prepare("SELECT * FROM `user` WHERE email=:email");
        $stmt->bindParam(':email', $filteredEmail);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            if ($filteredPassword === $filteredConfirmPassword) {
                $hashedPassword = password_hash($filteredPassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO `user` (`email`, `password_hash`, `role_id`) VALUES (:email, :passwordHash, :roleId)");
                $stmt->bindParam(':email', $filteredEmail);
                $stmt->bindParam(':passwordHash', $hashedPassword);
                $stmt->bindValue(':roleId', 0);
                $queryResult = $stmt->execute();

                if ($queryResult) {
                    $_SESSION['sign_success'] = "Account created, you can log in now!";
                    header("Location: " . $_POST['redirect'] . '?&su=1');
                    exit;
                }
                throw new Exception();
            } else {
                throw new Exception("Passwords do not match!", 2);
            }
        } else {
            throw new Exception("User with this email address already exists!", 1);
        }
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