<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: signup.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_otp = $_POST['otp'];
    $correct_otp = $_SESSION['temp_user']['otp'];

    if ($user_otp == $correct_otp) {
        $db = (new Database())->connect();
        $userObj = new User($db);
        $data = $_SESSION['temp_user'];

        if ($userObj->signup($data['name'], $data['email'], $data['pass'], $data['role'])) {
            unset($_SESSION['temp_user']);
            echo "Admin Verified and Created! <a href='login.php'>Login</a>";
        }
    } else {
        echo "Invalid OTP!";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Admin OTP Verification</h2>
    <p>Please enter the 6-digit code sent to the owner.</p>
    <form method="POST">
        <input type="text" name="otp" maxlength="6" required placeholder="000000">
        <input type="submit" value="Verify Admin">
    </form>
</body>
</html>