<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 1) {
    header("Location: ../public/login.php");
    exit;
}

$db = (new Database())->connect();
    
$allowedTypes = ['students', 'teachers'];
$type = $_GET['type'] ?? 'students';

if (!in_array($type, $allowedTypes, true)) {
    $type = 'students';
}

$role_id = ($type === 'teachers') ? 2 : 3;
$role_name = ucfirst(rtrim($type, 's'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $password = password_hash('123456', PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role_id)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $email,
            $password,
            $role_id
        ]);

        header("Location: read.php?type={$type}");
        exit;

    } catch (PDOException $e) {
        error_log("User creation failed: " . $e->getMessage());
        $error = "Unable to create {$role_name}.";
    }
}
?>

<h2>Add <?php echo $role_name; ?></h2>

<?php if (!empty($error)): ?>
    <p style="color:red;">
        <?php echo htmlspecialchars($error); ?>
    </p>
<?php endif; ?>

<form method="POST">
    <?php csrf_field(); ?>

    <label>Name:</label>
    <input type="text"
           name="name"
           required>
    <br><br>

    <label>Email:</label>
    <input type="email"
           name="email"
           required>
    <br><br>

    <button type="submit">
        Create <?php echo $role_name; ?>
    </button>
</form>

<?php require_once '../includes/footer.php'; ?>