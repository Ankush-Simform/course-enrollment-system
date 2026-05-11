<?php
session_start();

require_once '../config/database.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

$db = (new Database())->connect();
$userObj = new User($db);

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$captcha_input = trim($_POST['captcha_input'] ?? '');

$response = [];

if (!isset($_SESSION['captcha_code']) ||
    strcasecmp($captcha_input, $_SESSION['captcha_code']) !== 0) {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid CAPTCHA!"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format!"
    ]);
    exit;
}

$user = $userObj->login($email, $password);

if ($user) {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['user_name'] = $user['name'];

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    unset($_SESSION['captcha_code']);

    $redirect = match ((int)$user['role_id']) {
        1 => "dashboard.php",
        2 => "teacher.php",
        default => "/student-dashboard/student.php"
    };

    echo json_encode([
        "status" => "success",
        "redirect" => $redirect
    ]);
    exit;

} else {

    unset($_SESSION['captcha_code']);

    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password!"
    ]);
    exit;
}
