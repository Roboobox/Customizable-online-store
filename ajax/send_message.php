<?php
header('Content-type: application/json');

$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

$responseArray['status'] = 'success';

if (empty($email)) {
    $responseArray['email'] = 'Email is required!';
} else if (strlen($email) > 254) {
    $responseArray['email'] = 'Email cannot exceed 254 characters!';
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $responseArray['email'] = 'Email address is not valid!';
}

if (empty($message)) {
    $responseArray['message'] = 'Message is required!';
} else if (strlen($message) > 65535) {
    $responseArray['message'] = 'Message cannot exceed 65,535 characters!';
}

if (!isset($responseArray['email']) && !isset($responseArray['message'])) {
    include_once "../conn.php";
    $stmt = $conn->prepare("INSERT INTO `contact_message` (email, message_text) VALUES (:email, :message)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':message', $message);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $responseArray['status'] = 'error';
    }
}

echo json_encode($responseArray);


