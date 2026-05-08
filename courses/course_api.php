<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$db = (new Database())->connect();

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    jsonResponse(['error' => 'Unauthorized']);
}

$action = $_GET['action'] ?? '';

/* ================= DATATABLE FETCH ================= */
if ($action === 'fetch') {

    $draw   = $_GET['draw'] ?? 1;
    $start  = $_GET['start'] ?? 0;
    $length = $_GET['length'] ?? 10;
    $search = $_GET['search']['value'] ?? '';

    // TOTAL RECORDS
    $total = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();

    // BASE QUERY
    $sql = "
        SELECT c.*, u.name AS instructor_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE 1
    ";

    // SEARCH
    if (!empty($search)) {
        $sql .= " AND (c.course_name LIKE :search OR u.name LIKE :search)";
    }

    $sql .= " ORDER BY c.id DESC LIMIT :start, :length";

    $stmt = $db->prepare($sql);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        "draw" => intval($draw),
        "recordsTotal" => intval($total),
        "recordsFiltered" => intval($total),
        "data" => $data
    ]);
}

/* ================= CREATE ================= */
if ($action === 'create') {

    $stmt = $db->prepare("
        INSERT INTO courses (course_name, instructor_id, duration_weeks, max_seats)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['course_name'],
        $_POST['instructor_id'],
        $_POST['duration_weeks'],
        $_POST['max_seats']
    ]);

    jsonResponse(['status' => 'success']);
}

if ($action === 'update') {

    $stmt = $db->prepare("
        UPDATE courses 
        SET course_name=?, instructor_id=?, duration_weeks=?, max_seats=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['course_name'],
        $_POST['instructor_id'],
        $_POST['duration_weeks'],
        $_POST['max_seats'],
        $_POST['id']
    ]);

    jsonResponse(['status' => 'success']);
}

if ($action === 'delete') {

    $stmt = $db->prepare("DELETE FROM courses WHERE id=?");
    $stmt->execute([$_POST['id']]);

    jsonResponse(['status' => 'success']);
}

jsonResponse(['error' => 'Invalid action']);