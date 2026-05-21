<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 1) {
    header('Location: ../public/login.php');
    exit;
}

$db = (new Database())->connect();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    header('Location: ../student/read.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $stmt = $db->prepare(
        "UPDATE users
         SET name = ?, email = ?
         WHERE id = ?"
    );

    $stmt->execute([
        trim($_POST['name'] ?? ''),
        trim($_POST['email'] ?? ''),
        $id
    ]);

    header('Location: ../student/read.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT name, email
     FROM users
     WHERE id = ?"
);
$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    die('Student not found.');
}
?>

<form method="POST">
    <?php csrf_field(); ?>

    <input
        type="text"
        name="name"
        value="<?= htmlspecialchars($user['name']) ?>"
        required
    >
    <br><br>

    <input
        type="email"
        name="email"
        value="<?= htmlspecialchars($user['email']) ?>"
        required
    >
    <br><br>

    <button type="submit">Update</button>
</form>