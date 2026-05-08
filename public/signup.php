<?php
include '../includes/header.php';
include '../includes/auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

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
    $name  = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    $owner_email = "ankushkumar.119196@marwadiuniversity.ac.in";
    $generated_otp = rand(100000, 999999);
    $_SESSION['admin_otp'] = $generated_otp;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
         $mail->Host       = $_ENV['MAIL_HOST'];  
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($owner_email);

        $mail->isHTML(true);
        $mail->Subject = 'NEW ADMIN REGISTRATION ATTEMPT';
        
   
        $mail->Body = "
            <h3>Admin Approval Required</h3>
            <p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Action:</strong> Use the code below to authorize this admin account:</p>
            <h2 style='color:red; letter-spacing:5px;'>$generated_otp</h2>
        ";

        $mail->send();
        $success = "Request sent to the Owner. Please enter the OTP provided by him.";
        $showOtpField = true;

    } catch (Exception $e) {
        $error = "Mail failed: {$mail->ErrorInfo}";
    }
}

if (isset($_POST['register'])) {
        verify_csrf();    

    $name = $_POST['name'];
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'];
    $role_id = $_POST['role_id'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {

        if ($role_id == 1) { 
            $user_otp = trim($_POST['otp']);

            if (isset($_SESSION['admin_otp']) && (string)$user_otp === (string)$_SESSION['admin_otp']) {

                if ($userObj->signup($name, $email, $pass, 1)) {
                    unset($_SESSION['admin_otp']);
                    $success = "Admin account created!";
                }

            } else {
                $error = "Invalid OTP code.";
                $showOtpField = true;
            }

        } else {

            if ($userObj->signup($name, $email, $pass, $role_id)) {
                $success = "Registration successful! ";
            } else {
                $error = "Registration failed. Email might exist.";
            }

        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup System</title>
    <style>
        .hidden { display: none; }
        .box { border: 1px solid #ccc; padding: 20px; width: 300px; margin: 20px auto; font-family: Arial; }
        .msg { color: red; font-size: 14px; }
        .success { color: green; font-size: 14px; }
    </style>
</head>
<body>

<div class="box">
    <h3>Sign Up</h3>
    
    <!-- ✅ Escape output -->
    <?php if($error): ?> 
        <p class="msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p> 
    <?php endif; ?>

    <?php if($success): ?> 
        <p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p> 
    <?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
        <input type="text" name="name" placeholder="Full Name" required 
        value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <input type="email" name="email" placeholder="Your Email" required 
        value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <input type="password" name="password" placeholder="Password" required><br><br>
        
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
        <button type="submit" name="register">Register Now</button>
                <br> <br>
                <a href="/public/login.php" alt= "link">login</a>
               


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

</body>
</html>