<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$tab = 'Contact';
include_once 'conn.php';
include_once 'head.php';
include_once 'header.php';

// TODO: Make validation
$formErrors = getFormValidationErrors($conn);
if (empty($formErrors)) {
    if (isset($_POST['account_name'], $_POST['account_surname'], $_POST['account_phonenr'])) {
        $userUpdateSql = "UPDATE user SET name = :name, surname = :surname, mobile = :phoneNr WHERE id = :userId";
        $stmt = $conn->prepare($userUpdateSql);

        if (empty($_POST['account_name'])) {
            $stmt->bindValue(':name', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':name', $_POST['account_name']);
        }

        if (empty($_POST['account_surname'])) {
            $stmt->bindValue(':surname', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':surname', $_POST['account_surname']);
        }

        if (empty($_POST['account_phonenr'])) {
            $stmt->bindValue(':phoneNr', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':phoneNr', $_POST['account_phonenr']);
        }
        $stmt->bindParam(':userId', $_SESSION['user_id']);

        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $_SESSION['user_data']['name'] = $_POST['account_name'];
            $_SESSION['user_data']['surname'] = $_POST['account_surname'];
            $_SESSION['user_data']['phoneNr'] = $_POST['account_phonenr'];
            $formSuccessMsg = "Account info updated";
        }

    } else if (isset($_POST['account_passold'], $_POST['account_passnew'], $_POST['account_passconfirm'])) {
        $userUpdateSql = "UPDATE user SET password_hash = :passwordHash WHERE id = :userId";
        $hashedPassword = password_hash($_POST['account_passconfirm'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare($userUpdateSql);
        $stmt->bindParam(':passwordHash', $hashedPassword);
        $stmt->bindParam(':userId', $_SESSION['user_id']);
        $stmt->execute();
        $formSuccessMsg = "Password changed";
    } else if (isset($_POST['account_sort'], $_POST['account_layout'])) {
        $userUpdateSql = "UPDATE user SET product_sort = :sort, product_layout = :layout WHERE id = :userId";
        $stmt = $conn->prepare($userUpdateSql);
        $stmt->bindParam(':sort', $_POST['account_sort']);
        $stmt->bindParam(':layout', $_POST['account_layout']);
        $stmt->bindParam(':userId', $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $_SESSION['sort'] = $_POST['account_sort'];
            $_SESSION['layout'] = $_POST['account_layout'];
            $formSuccessMsg = "Preferences updated";
        }

    }
}

function getFormValidationErrors($conn) {
    $formErrors = array();
    if (isset($_POST['account_name'], $_POST['account_surname'], $_POST['account_phonenr'])) {
        $fields = array('account_name' => 'Name', 'account_surname' => 'Surname', 'account_phonenr' => 'Phone number');
        
        foreach ($fields as $field => $label) {
            if (($field === 'account_name' || $field === 'account_surname')) {
                if (strlen($_POST[$field]) > 255) {
                    $formErrors[$field] = $label . ' cannot be longer than 255 symbols';
                } else if (!preg_match("/[A-Za-z]/", $_POST[$field])) {
                    $formErrors[$field] = $label . ' can only be letters';
                }
            } else if ($field === 'account_phonenr') {
                if (strlen($_POST[$field]) > 20) {
                    $formErrors[$field] = $label . ' cannot be longer than 20 digits';
                } else if (!preg_match("/[0-9]/", $_POST[$field])) {
                    $formErrors[$field] = $label . ' can only be numbers';
                }
            }
        }
        
    } else if (isset($_POST['account_passold'], $_POST['account_passnew'], $_POST['account_passconfirm'])) {
        if ($_POST['account_passnew'] !== $_POST['account_passconfirm']) {
            $formErrors['account_passnew'] = "Passwords do not match";
            $formErrors['account_passconfirm'] = "";
        } else if (strlen($_POST['account_passnew']) < 8) {
            $formErrors['account_passnew'] = "Password must be at least 8 characters";
        } else if (strlen($_POST['account_passnew']) > 72) {
            $formErrors['account_passnew'] = "Password cannot exceed 72 characters";
        } else {
            $stmt = $conn->prepare("SELECT password_hash FROM user WHERE id=:userId");
            $stmt->bindParam(':userId', $_SESSION['user_id']);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch();
                if (!password_verify($_POST['account_passold'], $row['password_hash'])) {
                    $formErrors['account_passold'] = "Incorrect password";
                }
            } else {
                $formErrors['account_passold'] = "Incorrect password";
            }
        }
    } else if (isset($_POST['account_sort'], $_POST['account_layout'])) {
        $fieldValues = array('account_sort' => array('A to Z', 'Z to A', 'Price desc', 'Price asc'), 'account_layout' => array('grid', 'list'));
        foreach ($fieldValues as $field => $values) {
            if (!in_array($_POST[$field], $values, true)) {
                $formErrors[$field] = 'Incorrect option picked';
            }    
        }
    }
    
    return $formErrors;    
}
?>
<link href="css/account.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 <?=(isset($formSuccessMsg) ? '' : 'mb-4')?>">Account settings</h2>
        <div class="ms-2 px-3 py-1 mb-3 text-white text-update-success <?=(isset($formSuccessMsg) ? '' : 'd-none')?>"><?=$formSuccessMsg ?? ''?></div>
    </div>
    <div class="row">
        <div class="col p-4 bg-white shadow-sm border">
            <h4 class="mt-md-2 mt-4">Account</h4>
            <form method="POST" action="account.php">
                <div class="row g-3 mt-3 pb-4 border-bottom text-muted">
                    <div class="col-md-4 pe-sm-3">
                        <label for="firstName" class="form-label fw-bold">First name</label>
                        <input type="text" required pattern="[A-Za-z]+" name="account_name" title="Letters only" value="<?=htmlspecialchars($_SESSION['user_data']['name'] ?? '')?>" class="form-control <?=(isset($formErrors['account_name']) ? 'is-invalid' : '')?>" id="firstName" placeholder="John" maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_name'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 pe-sm-3 ps-sm-3">
                        <label for="lastName" class="form-label fw-bold">Last name</label>
                        <input type="text" required pattern="[A-Za-z]+" name="account_surname" title="Letters only" value="<?=htmlspecialchars($_SESSION['user_data']['surname'] ?? '')?>" class="form-control <?=(isset($formErrors['account_surname']) ? 'is-invalid' : '')?>" id="lastName" placeholder="Smith" maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_surname'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 ps-sm-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <input disabled type="email" name="account_email" value="<?=htmlspecialchars($_SESSION['user_data']['email'] ?? '')?>" class="form-control" id="email" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4 pe-sm-3">
                        <label for="phoneNr" class="form-label fw-bold">Phone number</label>
                        <input type="tel" pattern="[0-9]+" name="account_phonenr" title="Numbers only" value="<?=htmlspecialchars($_SESSION['user_data']['phoneNr'] ?? '')?>" class="form-control  <?=(isset($formErrors['account_phonenr']) ? 'is-invalid' : '')?>" id="phoneNr" maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_phonenr'] ?? ''?></div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4 pe-sm-3 d-flex align-items-end">
                            <button type="submit" id="submit_account" class="btn w-50 btn-primary fw-bold">Save</button>
                        </div>
                    </div>
                </div>
            </form>
            <h4 class="mt-4">Password</h4>
            <form method="POST" action="account.php">
                <div class="row g-3 mt-3 pb-4 border-bottom text-muted">
                    <div class="col-md-4 pe-sm-3">
                        <label for="passwordOld" class="form-label fw-bold">Old password</label>
                        <input type="password" name="account_passold" class="form-control <?=(isset($formErrors['account_passold']) ? 'is-invalid' : '')?>" id="passwordOld" placeholder="" value="" required maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_passold'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 pe-sm-3">
                        <label for="passwordNew" class="form-label fw-bold">New password</label>
                        <input type="password" name="account_passnew" class="form-control <?=(isset($formErrors['account_passnew']) ? 'is-invalid' : '')?>" id="passwordNew" placeholder="" value="" required maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_passnew'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 pe-sm-3">
                        <label for="passwordConfirm" class="form-label fw-bold">Confirm password</label>
                        <input type="password" name="account_passconfirm" class="form-control <?=(isset($formErrors['account_passconfirm']) ? 'is-invalid' : '')?>" id="passwordConfirm" placeholder="" value="" required maxlength="255">
                        <div class="invalid-feedback"><?=$formErrors['account_passconfirm'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" id="submit_password" class="btn w-50 btn-primary fw-bold">Change</button>
                    </div>
                </div>
            </form>
            <h4 class="mt-4">Preferences</h4>
            <form method="POST" action="account.php">
                <div class="row g-3 mt-3 pb-4 text-muted">
                    <div class="col-md-4 pe-sm-3">
                        <label for="productSort" class="form-label fw-bold">Default product sorting</label>
                        <select id="productSort" name="account_sort" class="form-select w-auto <?=(isset($formErrors['account_sort']) ? 'is-invalid' : '')?>" aria-label="Sorting select">
                            <option value="A to Z" selected>A to Z</option>
                            <option value="Z to A">Z to A</option>
                            <option value="Price desc">Price descending</option>
                            <option value="Price asc">Price ascending</option>
                        </select>
                        <div class="invalid-feedback"><?=$formErrors['account_sort'] ?? ''?></div>
                    </div>
                    <div class="col-md-4 pe-sm-3">
                        <label for="productLayout" class="form-label fw-bold">Default product layout</label>
                        <select id="productLayout" name="account_layout" class="form-select w-auto <?=(isset($formErrors['account_layout']) ? 'is-invalid' : '')?>" aria-label="Layout select">
                            <option value="grid" selected>Grid</option>
                            <option value="list">List</option>
                        </select>
                        <div class="invalid-feedback"><?=$formErrors['account_layout'] ?? ''?></div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-4 mt-0 d-flex align-items-end">
                            <button type="submit" id="submit_pref" class="btn w-50 btn-primary fw-bold">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
<!--    <div class="contact-form-container m-auto shadow-sm">-->
<!--        <form>-->
<!--            <div class="mb-3">-->
<!--                <label for="name_input" class="form-label">Name</label>-->
<!--                <input type="text" class="form-control" id="name_input">-->
<!--            </div>-->
<!--            <div class="mb-3">-->
<!--                <label for="surname_input" class="form-label">Surname</label>-->
<!--                <input type="text" class="form-control" id="surname_input">-->
<!--            </div>-->
<!--            <div class="mb-3">-->
<!--                <label for="message_textarea" class="form-label">Your message</label>-->
<!--                <textarea class="form-control" id="message_textarea" rows="3"></textarea>-->
<!--            </div>-->
<!--            <div class="contact-form-footer d-flex flex-column">-->
<!--                <button class="btn-contact-send w-50 mb-3 mt-3 m-auto">SEND</button>-->
<!--                <p class="text-center px-4 mb-0">If you have any questions, feel free to write to us.</p>-->
<!--            </div>-->
<!--        </form>-->
<!--    </div>-->
</div>

<?php include_once 'footer.php'?>