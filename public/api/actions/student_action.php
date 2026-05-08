<?php
require_once '../config/database.php';
$db = (new Database())->connect();

$action = $_GET['action'] ?? '';

if ($action == 'fetch') {
    $stmt = $db->query("SELECT id, name, email FROM users WHERE role_id = 3");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

if ($action == 'delete') {
    $id = $_POST['id'];
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$id]);
    echo json_encode(['success' => $success]);
    exit;
}