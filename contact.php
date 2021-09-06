<?php
$tab = 'Contact';
include_once 'head.php';
include_once 'header.php'
?>
<link href="css/contact.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Contact us</h2>
    </div>
    <div class="contact-form-container m-auto shadow-sm">
        <form>
            <div class="mb-3">
                <label for="name_input" class="form-label">Name</label>
                <input type="text" class="form-control" id="name_input">
            </div>
            <div class="mb-3">
                <label for="surname_input" class="form-label">Surname</label>
                <input type="text" class="form-control" id="surname_input">
            </div>
            <div class="mb-3">
                <label for="message_textarea" class="form-label">Your message</label>
                <textarea class="form-control" id="message_textarea" rows="3"></textarea>
            </div>
            <div class="contact-form-footer d-flex flex-column">
                <button class="btn-contact-send w-50 mb-3 mt-3 m-auto">SEND</button>
                <p class="text-center px-4 mb-0">If you have any questions, feel free to write to us.</p>
            </div>
        </form>
    </div>
</div>

<?php include_once 'footer.php'?>
