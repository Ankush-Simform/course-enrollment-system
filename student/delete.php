<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role_id'] ?? 0) != 1) {
    header("Location: ../public/login.php");
    exit;
}

$db = (new Database())->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid ID");
}

try {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../student/read.php?type=students");
    exit;

} catch (PDOException $e) {
   error_log("User delete failed: " . $e->getMessage());

    die("An unexpected error occurred while deleting the user.");
}
?>