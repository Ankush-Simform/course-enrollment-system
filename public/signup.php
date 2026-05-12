<?php
session_start();

include '../includes/auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/database.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->connect();
$userObj = new User($db);

$error = "";
$success = "";
$showOtpField = false;

if (isset($_POST['send_otp'])) {

    verify_csrf();

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '') {
        $error = "Name and Email are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {

        $generated_otp = rand(100000, 999999);
        $_SESSION['admin_otp'] = $generated_otp;

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress("ankushkumar.119196@marwadiuniversity.ac.in");

            $mail->isHTML(true);
            $mail->Subject = "Admin OTP";
            $mail->Body = "<h2>OTP: $generated_otp</h2>";

            $mail->send();

            $success = "OTP sent to Owner!";
            $showOtpField = true;

        } catch (Exception $e) {
            $error = "Mail failed: {$mail->ErrorInfo}";
        }
    }
}

if (isset($_POST['register'])) {

    header('Content-Type: application/json');

    $response = ["status"=>"error","message"=>"Something went wrong"];

    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';

    if ($name === '' || $email === '' || $pass === '') {
        $response["message"] = "All fields required";
        echo json_encode($response); exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["message"] = "Invalid email";
        echo json_encode($response); exit;
    }

    if (strlen($pass) < 4) {
        $response["message"] = "Password too short";
        echo json_encode($response); exit;
    }

    /* ADMIN OTP CHECK */
    if ($role_id == 1) {

        $otp = $_POST['otp'] ?? '';

        if (!isset($_SESSION['admin_otp'])) {
            $response["message"] = "Send OTP first";
            echo json_encode($response); exit;
        }

        if ($otp != $_SESSION['admin_otp']) {
            $response["message"] = "Invalid OTP";
            echo json_encode($response); exit;
        }
    }

    if ($userObj->signup($name, $email, $pass, $role_id)) {

        unset($_SESSION['admin_otp']);

        $response["status"] = "success";
        $response["message"] = "Registration successful";
        $response["redirect"] = "/public/login.php";
    } else {
        $response["message"] = "Email already exists";
    }

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Signup System</title>
    <style>
        .hidden {
            display: none;
        }

        .box {
            border: 1px solid #ccc;
            padding: 20px;
            width: 300px;
            margin: 20px auto;
            font-family: Arial;
        }

        .msg {
            color: red;
            font-size: 14px;
        }

        .success {
            color: green;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="box">
        <h3>Sign Up</h3>

        <?php if ($error): ?>
            <p class="msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" id="signupForm">
            <?php csrf_field(); ?>
            <input type="text" name="name" placeholder="Full Name"
                value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

            <input type="email" name="email" placeholder="Your Email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

            <input type="password" name="password" placeholder="Password"><br><br>

            <label>Role:</label>
            <select name="role_id" id="roleSelect" onchange="toggleAdminFields()">
                <option value="3" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == 3) ? 'selected' : ''; ?>>Student</option>
                <option value="2" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == 2) ? 'selected' : ''; ?>>Teacher</option>
                <option value="1" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == 1) ? 'selected' : ''; ?>>Admin</option>
            </select><br><br>

            <div id="adminSection" class="<?php echo ($showOtpField || (isset($_POST['role_id']) && $_POST['role_id'] == 1)) ? '' : 'hidden'; ?>">
                <button type="submit" name="send_otp" id="otpBtn" formnovalidate>Send OTP to Owner</button>
                <div id="otpInput" class="<?php echo ($showOtpField) ? '' : 'hidden'; ?>"><br>
                    <input type="text" name="otp" placeholder="6-Digit OTP" maxlength="6">
                </div>
            </div>

            <br>
            <button type="submit" name="register" id="register">Register Now</button>
            <br> <br>
            <a href="/public/login.php" alt="link">login</a>



        </form>
    </div>

    <script>
        function toggleAdminFields() {
            var role = document.getElementById("roleSelect").value;
            var adminSection = document.getElementById("adminSection");

            if (role == "1") {
                adminSection.classList.remove("hidden");
            } else {
                adminSection.classList.add("hidden");
            }
        }
    </script>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#signupForm').validate({

                rules: {
                    name: {
                        required: true
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 4
                    },
                    role_id: {
                        required: true
                    }
                },

                messages: {
                    name: {
                        required: "Name is required"
                    },
                    email: {
                        required: "Email is required",
                        email: "Enter valid email"
                    },
                    password: {
                        required: "Password is required",
                        minlength: "Min 4 chars"
                    },
                    role_id: {
                        required: "Select role"
                    }
                },

                errorElement: "span",
                errorClass: "error",

                submitHandler: function(form) {

                    $.ajax({
                        url: "signup.php",
                        type: "POST",
                        data: $(form).serialize(),
                        dataType: "json",

                        beforeSend: function() {
                            $("#register").prop("disabled", true).text("Signing up...");
                        },

                        success: function(res) {

                            $("#register").prop("disabled", false).text("Register Now");

                            if (res.status === "success") {
                                alert(res.message);
                                window.location.href = res.redirect;
                            } else {
                                alert(res.message);
                            }
                        },

                        error: function() {
                            $("#register").prop("disabled", false).text("Register Now");
                            alert("Server error");
                        }
                    });

                    return false;
                }
            });

        });
    </script>

</body>

</html>