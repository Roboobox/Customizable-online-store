<?php
session_start();

$formErrors = array();
try {
    // Check if data is passed
    if (isset($_POST['email'], $_POST['password'])
        && $_SERVER['REQUEST_METHOD'] === "POST"
    ) {
        $_SESSION['auth_email'] = $_POST['email'];
        include_once('conn.php');
        $userEmail = $_POST['email'];
        $userPassword = $_POST['password'];

        // Get user from database by email that user had entered in login form
        $stmt = $conn->prepare("SELECT * FROM `user` WHERE email=:email");
        $stmt->bindParam(':email', $userEmail);
        $stmt->execute();

        // Check if user was found
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch();
            // Check if password matches the password that user entered in login form
            if (password_verify($userPassword, $row['password_hash'])) {
                    // Regenerate session id and save necessary user data in session
                    session_regenerate_id();
                    $userToken = bin2hex(random_bytes(16));

                    $_SESSION['user_id'] = $row['id'];
                    // Generate CSRF token for logged in user
                    $_SESSION['user_token'] = $userToken;
                    $_SESSION['user_role'] = $row['role_id'];
                    $_SESSION['sort'] = $row['product_sort'];
                    $_SESSION['layout'] = $row['product_layout'];
                    $_SESSION['user_data'] = array('email' => $row['email'], 'name' => $row['name'], 'surname' => $row['surname'], 'phoneNr' => $row['mobile']);

                    // Save session cat data to user database cart
                    syncSessionCartWithDB($conn);
                    // Redirect to page where login form was opened
                    header("Location: " . $_POST['redirect']);
                    exit;
            }
            throw new Exception("Incorrect email or password!", 3);
        } else {
            throw new Exception("Incorrect email or password!", 3);
        }
    } else {
        header("Location: index.php");
        exit;
    }
}
catch (Exception $e) {
    // Return error messages
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
        $formErrors['general'] = 'Something went wrong. Please try again later!';
    }
    $_SESSION['sign_error'] = $formErrors;
    header("Location: " . $_POST['redirect']);
    exit;
}

function syncSessionCartWithDB($conn): void
{
    if (isset($_SESSION['cart'])) {
        $sessionCart = $_SESSION['cart'];

        // Get user database cart if it exists
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = :userId AND is_active = :cartActive");
        $stmt->bindParam(':userId', $_SESSION['user_id']);
        $stmt->bindValue(':cartActive', 1);
        $stmt->execute();

        $userDbCart = $stmt->fetch();

        if ($userDbCart) {
            // If the cart exists, select product id's of the cart items
            $userDbCartId = $userDbCart['id'];

            $cartSql = "SELECT product_id FROM `cart_item` WHERE cart_id = :cartId";
            $stmt = $conn->prepare($cartSql);
            $stmt->bindParam(':cartId', $userDbCartId);
            $stmt->execute();
            $userCartItems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // If the cart doesn't exist, make a new cart for this user
            $stmt = $conn->prepare("INSERT INTO `cart` (is_active, user_id) VALUES (:cartActive, :userId)");
            $stmt->bindValue(':cartActive', 1, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $_SESSION['user_id']);
            $stmt->execute();
            // Get id of the new cart
            $userDbCartId = $conn->lastInsertId();
            // Create empty array of cart items because the new cart will be empty
            $userCartItems = array();
        }

        // Check if session cart item is in database
        $insertQuerySql = "INSERT INTO `cart_item` (cart_id, product_id, quantity) VALUES ";
        $insertData = array();
        $insertCounter = 0;

        foreach ($sessionCart as $product_id => $quantity) {
            // If is in database UPDATE quantity otherwise INSERT new cart item
            if (!empty($userCartItems) && in_array($product_id, $userCartItems, false)) {

                $stmt = $conn->prepare("UPDATE `cart_item` SET quantity = quantity + :quantity WHERE cart_id = :userCartId");
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':userCartId', $userDbCartId);
                $stmt->execute();
            } else {
                // Creates batch insert template (inserts multiple rows with one query)
                $insertQuerySql .= "(" . ":cartId" . $insertCounter . ", :productId" . $insertCounter . ", :quantity" . $insertCounter . "), ";
                $insertData[$insertCounter] = array($userDbCartId, $product_id, $quantity);
                $insertCounter++;
            }
        }
        if ($insertCounter > 0) {
            // Removes trailing comma and whitespace
            $insertQuerySql = substr($insertQuerySql, 0, -2);
            $stmt = $conn->prepare($insertQuerySql);

            for ($i = 0; $i < $insertCounter; $i++) {
                $stmt->bindParam(':cartId' . $i, $insertData[$i][0]);
                $stmt->bindParam(':productId' . $i, $insertData[$i][1]);
                $stmt->bindParam(':quantity' . $i, $insertData[$i][2]);
            }
            $stmt->execute();
        }
    }
}
