<?php

session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$db = (new Database())->connect();

function res($data, $code = 200)
{
    http_response_code($code);

    echo json_encode(
        $data,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    );

    exit;
}

function clean($v)
{
    return trim(strip_tags(str_replace("\0", '', (string)$v)));
}

if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    res(['error' => 'Unauthorized'], 403);
}

$action = clean($_GET['action'] ?? '');

if ($action === 'fetch') {

    $draw = (int)($_GET['draw'] ?? 1);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = max(1, min(100, (int)($_GET['length'] ?? 10)));
    $search = clean($_GET['search']['value'] ?? '');

    $columns = [
        0 => 'c.id',
        1 => 'c.course_name',
        2 => 'u.name',
        3 => 'c.duration_weeks',
        4 => 'c.max_seats'
    ];

    $colIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $dir = strtolower($_GET['order'][0]['dir'] ?? 'desc');

    $order = $columns[$colIndex] ?? 'c.id';
    $dir = $dir === 'asc' ? 'ASC' : 'DESC';

    $total = (int)$db
        ->query("SELECT COUNT(*) FROM courses")
        ->fetchColumn();

    $sql = "
        SELECT
            c.id,
            c.course_name,
            c.duration_weeks,
            c.max_seats,
            u.name AS instructor_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE 1
    ";

    $params = [];

    if ($search !== '') {

        $sql .= "
            AND (
                c.course_name LIKE :search
                OR u.name LIKE :search
            )
        ";

        $params[':search'] = "%$search%";
    }

    $countSql = "
        SELECT COUNT(*)
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE 1
    ";

    if ($search !== '') {

        $countSql .= "
            AND (
                c.course_name LIKE :search
                OR u.name LIKE :search
            )
        ";
    }

    $countStmt = $db->prepare($countSql);

    foreach ($params as $k => $v) {
        $countStmt->bindValue($k, $v, PDO::PARAM_STR);
    }

    $countStmt->execute();

    $filtered = (int)$countStmt->fetchColumn();

    $sql .= " ORDER BY $order $dir LIMIT :start,:length";

    $stmt = $db->prepare($sql);

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }

    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);

    $stmt->execute();

    $data = [];

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $data[] = [
            'id' => (int)$r['id'],
            'course_name' => htmlspecialchars($r['course_name'], ENT_QUOTES, 'UTF-8'),
            'instructor_name' => htmlspecialchars($r['instructor_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'duration_weeks' => (int)$r['duration_weeks'],
            'max_seats' => (int)$r['max_seats']
        ];
    }

    res([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    res(['error' => 'Method not allowed'], 405);
}

if ($action === 'create') {

    $course = clean($_POST['course_name'] ?? '');
    $instructor = (int)($_POST['instructor_id'] ?? 0);
    $duration = (int)($_POST['duration_weeks'] ?? 0);
    $seats = (int)($_POST['max_seats'] ?? 0);

    if ($course === '' || $instructor <= 0 || $duration <= 0 || $seats <= 0) {
        res(['error' => 'Invalid data'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO courses (
            course_name,
            instructor_id,
            duration_weeks,
            max_seats
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $course,
        $instructor,
        $duration,
        $seats
    ]);

    res(['status' => 'success']);
}

if ($action === 'update') {

    $id = (int)($_POST['id'] ?? 0);
    $course = clean($_POST['course_name'] ?? '');
    $instructor = (int)($_POST['instructor_id'] ?? 0);
    $duration = (int)($_POST['duration_weeks'] ?? 0);
    $seats = (int)($_POST['max_seats'] ?? 0);

    if ($id <= 0 || $course === '') {
        res(['error' => 'Invalid data'], 400);
    }

    $stmt = $db->prepare("
        UPDATE courses
        SET
            course_name=?,
            instructor_id=?,
            duration_weeks=?,
            max_seats=?
        WHERE id=?
    ");

    $stmt->execute([
        $course,
        $instructor,
        $duration,
        $seats,
        $id
    ]);

    res(['status' => 'success']);
}

if ($action === 'delete') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        res(['error' => 'Invalid ID'], 400);
    }

    $stmt = $db->prepare("DELETE FROM courses WHERE id=?");
    $stmt->execute([$id]);

    res(['status' => 'success']);
}

res(['error' => 'Invalid action'], 400);