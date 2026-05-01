<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 1) {
    die("Unauthorized");
}

$db = (new Database())->connect();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    die("Invalid Course ID");
}

$stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
$stmt->execute([$id]);

header("Location: ../classes/course.php?type=courses");
exit;