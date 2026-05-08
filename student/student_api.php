<?php
declare(strict_types=1);

session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT
    );

    exit;
}

function cleanString(?string $value): string
{
    $value = trim((string)$value);

    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

    $value = strip_tags($value);

    return $value;
}

try {

    $db = (new Database())->connect();

    $action = $_GET['action'] ?? '';

   if ($action === 'fetch') {

    $draw = (int)($_GET['draw'] ?? 1);
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    $search = trim($_GET['search']['value'] ?? '');

    $type = $_GET['type'] ?? 'students';
    $role_id = ($type === 'teachers') ? 2 : 3;

    $totalStmt = $db->prepare("
        SELECT COUNT(*) FROM users
        WHERE role_id = ? AND deleted_at IS NULL
    ");
    $totalStmt->execute([$role_id]);
    $totalRecords = (int)$totalStmt->fetchColumn();

    $sql = "
        SELECT id, name, email
        FROM users
        WHERE role_id = :role
        AND deleted_at IS NULL
    ";

    if ($search !== '') {
        $sql .= " AND (name LIKE :search OR email LIKE :search)";
    }

    $sql .= " LIMIT :start, :length";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':role', $role_id, PDO::PARAM_INT);

    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }

    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $search ? count($rows) : $totalRecords,
        "data" => $rows
    ]);
}

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Invalid request'
        ], 405);
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        jsonResponse([
            'status' => 'error',
            'message' => 'CSRF failed'
        ], 403);
    }

    if ($action === 'delete') {

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Invalid ID'
            ], 400);
        }

        $stmt = $db->prepare("
            UPDATE users
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        jsonResponse([
            'status' => 'success',
            'message' => 'Deleted'
        ]);
    }

    if ($action === 'update') {

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        $name = cleanString($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (
            !$id ||
            mb_strlen($name) < 2 ||
            mb_strlen($name) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Invalid input'
            ], 400);
        }

        if (!preg_match("/^[a-zA-Z\s.'-]+$/u", $name)) {
            jsonResponse([
                'status' => 'error',
                'message' => 'Invalid name format'
            ], 400);
        }

        $stmt = $db->prepare("
            UPDATE users
            SET name = ?, email = ?
            WHERE id = ?
        ");

        $stmt->execute([$name, $email, $id]);

        jsonResponse([
            'status' => 'success',
            'message' => 'Updated'
        ]);
    }

    jsonResponse([
        'status' => 'error',
        'message' => 'Invalid action'
    ], 400);

} catch (Throwable $e) {

    jsonResponse([
        'status' => 'error',
        'message' => 'Server error'
    ], 500);
}