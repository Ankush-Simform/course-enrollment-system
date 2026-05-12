<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';
?>

<h2>Login</h2>
<hr>
<div class="main-wrapper">
    <div class="content-box">
<form id="loginForm" method="post">

    <?php csrf_field(); ?>

    <label>Email:</label><br>
    <input type="email" name="email" id="email" placeholder="example@test.com">
    <br><br>

    <label>Password:</label><br>
    <input type="password" name="password" id="pswd" placeholder="Enter password">
    <br><br>

    <label>CAPTCHA:</label><br>

    <img id="captchaImage" src="captcha.php" alt="CAPTCHA">
    <button type="button" onclick="refreshCaptcha()">🔄</button>

    <br><br>

    <input type="text" name="captcha_input" id="captcha_input" placeholder="Enter CAPTCHA">

    <br><br>

    <button type="submit" id="loginBtn">Login</button>

    <div id="serverError" style="color:red;"></div>

</form></div>
</div>
<script>
function refreshCaptcha() {
    document.getElementById('captchaImage').src = 'captcha.php?' + Date.now();
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

<script>
$(document).ready(function(){

    $("#loginForm").validate({

        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 4
            },
            captcha_input: {
                required: true
            }
        },

        messages: {
            email: {
                required: "Email is required",
                email: "Enter valid email"
            },
            password: {
                required: "Password is required",
                minlength: "Minimum 4 characters required"
            },
            captcha_input: {
                required: "CAPTCHA is required"
            }
        },

        errorElement: "span",
        errorClass: "error",

        submitHandler: function(form) {

            $.ajax({
                url: "login_process.php",
                type: "POST",
                dataType: "json",
                data: $(form).serialize(),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },

                beforeSend: function () {
                    $("#loginBtn").prop("disabled", true).text("Logging in...");
                },

                success: function(res) {

                    $("#loginBtn").prop("disabled", false).text("Login");

                    if (res.status === "success") {
                        window.location.href = res.redirect;
                    } else {
                        $("#serverError").text(res.message);
                        refreshCaptcha();
                    }
                },

                error: function() {
                    $("#loginBtn").prop("disabled", false).text("Login");
                    $("#serverError").text("Server error occurred!");
                }
            });

            return false;
        }
    });

});
</script>

<style>
.error {
    color: red;
    font-size: 13px;
}
</style>

<?php require_once '../includes/body_bottom.php'; ?>