<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self';");
header('X-XSS-Protection: 1; mode=block');

$db = (new Database())->connect();
if (
    !isset($_SESSION['role_id']) ||
    (int)$_SESSION['role_id'] !== 1
) {
    http_response_code(403);
    echo json_encode(
        [
            'status'  => 'error',
            'message' => 'Unauthorized'
        ],
        JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
    );

    exit;
}

function response(array $data, int $code = 200): void
{
    http_response_code($code);
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

function clean(string $value): string
{
    $value = trim($value);
    $value = str_replace("\0", '', $value);
    $value = mb_convert_encoding(
        $value,
        'UTF-8',
        'UTF-8'
    );
    $value = strip_tags($value);
    return $value;
}

$action = clean($_GET['action'] ?? '');

if ($action === 'fetch') {
    $draw = (int)($_GET['draw'] ?? 1);
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = max(1, min(100, (int)($_GET['length'] ?? 10)) );
    $search = clean( $_GET['search']['value'] ?? '' );

    $type = clean(
        $_GET['type'] ?? 'students'
    );

    $role_id = ($type === 'teachers') ? 2 : 3;

    $columns = [
        0 => 'id',
        1 => 'name',
        2 => 'email'
    ];
    $orderColumnIndex =(int)($_GET['order'][0]['column'] ?? 0);

    $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc');

    $orderColumn = $columns[$orderColumnIndex] ?? 'id';

    $orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';

    $base =
        "FROM users
         WHERE role_id = ?
         AND deleted_at IS NULL";

    $total = $db->prepare(
        "SELECT COUNT(*) $base"
    );

    $total->execute([$role_id]);
    $totalRecords =
        (int)$total->fetchColumn();
    $sql =
        "SELECT id, name, email $base";
    $params = [$role_id];
    if ($search !== '') {

        $sql .=
            " AND (
                name LIKE ?
                OR email LIKE ?
            )";

        $searchTerm = "%{$search}%";

        $params[] = $searchTerm;
        $params[] = $searchTerm;}

    $countSql = "SELECT COUNT(*) " . substr($sql, strpos($sql, "FROM"));
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $filteredRecords =
        (int)$countStmt->fetchColumn();

    $sql .=
        " ORDER BY {$orderColumn} {$orderDir}
          LIMIT {$start}, {$length}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars(
                $row['name'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
            'email' => htmlspecialchars(
                $row['email'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
        ];
    }
    response([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(
        [
            'status'  => 'error',
            'message' => 'Invalid request'
        ],
        405
    );
}

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token'] ?? ''
    )
) {
    response(
        [
            'status'  => 'error',
            'message' => 'CSRF validation failed'
        ],
        403
    );
}
if ($action === 'delete') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {

        response(
            [
                'status'  => 'error',
                'message' => 'Invalid ID'
            ],
            400
        );
    }
    $stmt = $db->prepare(
        "UPDATE users
         SET deleted_at = NOW()
         WHERE id = ?"
    );
    $stmt->execute([$id]);
    response([
        'status' => 'success'
    ]);
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    if ($id <= 0) {
        response(
            [
                'status'  => 'error',
                'message' => 'Invalid ID'
            ],
            400
        );
    }
    if (
        $name === '' ||
        mb_strlen($name) < 2 ||
        mb_strlen($name) > 100
    ) {
        response(
            [
                'status'  => 'error',
                'message' => 'Invalid name'
            ],
            400
        );
    }
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        mb_strlen($email) > 190
    ) {
        response(
            [
                'status'  => 'error',
                'message' => 'Invalid email'
            ],
            400
        );
    }
    $stmt = $db->prepare(
        "UPDATE users
         SET name = ?, email = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $name,
        $email,
        $id
    ]);

    response([
        'status' => 'success'
    ]);
}
response(
    [
        'status'  => 'error',
        'message' => 'Invalid action'
    ],
    400
);
