<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';

$db = (new Database())->connect();
$userObj = new User($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_input = trim($_POST['captcha_input'] ?? '');

    if (
        !isset($_SESSION['captcha_code']) ||
        strcasecmp($captcha_input, $_SESSION['captcha_code']) !== 0
    ) {
        $message = 'Invalid CAPTCHA!';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format!';
    }
    else {
        $user = $userObj->login($email, $password);

        if ($user) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['user_name'] = $user['name'];

            // Fresh CSRF token after login
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ((int)$user['role_id'] === 1) {
                header('Location: dashboard.php');
            } elseif ((int)$user['role_id'] === 2) {
                header('Location: teacher.php');
            } else {
                header('Location: /classes/student.php');
            }
            exit;
        } else {
            $message = 'Invalid email or password!';
        }
    }

    unset($_SESSION['captcha_code']);
}
?>

<h2>Login</h2>
<hr>

<?php if (!empty($message)): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
    <hr>
<?php endif; ?>

<form method="POST">
   <?php csrf_field(); ?>

    <label>Email:</label><br>
    <input
        type="email"
        name="email"
        required
        placeholder="example@test.com"
    >
    <br><br>

    <label>Password:</label><br>
    <input
        type="password"
        name="password"
        required
        placeholder="Enter password"
    >
    <br><br>

    <label>Verify CAPTCHA:</label><br>

    <img id="captchaImage" src="captcha.php" alt="CAPTCHA">

    <button type="button" onclick="refreshCaptcha()">
        🔄
    </button>

    <br><br>

    <input
        type="text"
        name="captcha_input"
        required
        placeholder="Enter the code above"
    >

    <br><br>

    <button type="submit" name="login">
        Login
    </button>
</form>

<br>
<hr>

<p>
    <a href="signup.php">Don't have an account? Signup</a>
</p>

<script>
function refreshCaptcha() {
    const captcha = document.getElementById('captchaImage');
    captcha.src = 'captcha.php?' + Date.now();
}
</script>

<?php require_once '../includes/body_bottom.php'; ?>