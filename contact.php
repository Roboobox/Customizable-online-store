<?php
$tab = 'Contact';
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/contact.css?<?=time()?>" rel="stylesheet">
<script src="js/contact.js?v=1" type="text/javascript"></script>
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Contact us</h2>
    </div>
    <div class="contact-form-container bg-white m-auto shadow-sm">
        <form action="" method="post" id="contactForm" novalidate>
            <div class="mb-3">
                <label for="email_input" class="form-label">Your email address</label>
                <input type="email" value="<?=$_SESSION['user_data']['email'] ?? ''?>" class="form-control" id="email_input">
                <div class="invalid-feedback" id="email_error"></div>
            </div>
            <div class="mb-3">
                <label for="message_textarea" class="form-label">Your message</label>
                <textarea class="form-control" id="message_textarea" rows="3"></textarea>
                <div class="invalid-feedback" id="message_error"></div>
            </div>
            <div class="contact-form-footer d-flex flex-column">
                <button class="btn-contact-send w-50 mb-3 mt-3 m-auto"><span id="button_text">SEND</span><span id="loading" class="d-none fas fa-spinner fa-spin fs-5"></span></button>
                <div class="invalid-feedback text-center fw-bold" id="general_error"></div>
                <div class="text-success text-center fw-bold" id="form_success"></div>
                <p class="text-center px-4 mb-0">If you have any questions, feel free to write to us.</p>
                <div class="other_options">
                    <?=isset($storeSettings['store_email']) ? '<div class="text-muted text-center"><i class="far fa-envelope align-middle"></i> <a class="text-muted" href="mailto: '.$storeSettings['store_email'].'">Send Email</a></div>' : ''?>
                    <?=isset($storeSettings['store_phonenr']) ? '<div class="text-muted text-center"><i class="fas fa-phone align-middle"></i> <a class="text-muted" href="tel: '.$storeSettings['store_phonenr'].'">'.$storeSettings['store_phonenr'].'</a></div>' : ''?>
                    <?=isset($storeSettings['store_address']) ? '<div class="text-muted text-center"><i class="fas fa-map-marker-alt align-middle"></i> '.$storeSettings['store_address'].'</div>' : ''?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include_once 'footer.php'?>
