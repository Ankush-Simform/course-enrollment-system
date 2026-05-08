<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

verify_csrf();

header('Content-Type: application/json');

$db = (new Database())->connect();

$student = $_SESSION['user_id'];


$draw  = $_POST['draw']  ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;

$search = trim($_POST['search']['value'] ?? '');

$total = $db->query("
    SELECT COUNT(*)
    FROM courses
")->fetchColumn();

$sql = "
    SELECT
        c.id,
        c.course_name,
        c.current_enrolled,
        c.max_seats,
        u.name AS teacher
    FROM courses c

    LEFT JOIN users u
        ON c.instructor_id = u.id

    WHERE c.course_name LIKE :search

    LIMIT :start,:length
";

$stmt = $db->prepare($sql);

$stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

$stmt->execute();

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$my = $db->prepare("
    SELECT course_id
    FROM enrollments
    WHERE student_id=?
");

$my->execute([$student]);

$myIds = $my->fetchAll(PDO::FETCH_COLUMN);

$data = [];

foreach ($courses as $c) {

    if (in_array($c['id'], $myIds)) {

        $action = "Enrolled";
    } elseif ($c['current_enrolled'] >= $c['max_seats']) {

        $action = "Full";
    } else {

        $action = "
            <button
                class='enroll-btn'
                data-id='{$c['id']}'
            >
                Enroll
            </button>
        ";
    }

    $data[] = [

        'course_name' => $c['course_name'],

        'teacher' => $c['teacher'] ?: 'N/A',

        'seats' =>
        $c['current_enrolled']
            . '/'
            . $c['max_seats'],

        'action' => $action
    ];
}

echo json_encode([

    "draw" => intval($draw),

    "recordsTotal" => intval($total),

    "recordsFiltered" => intval($total),

    "data" => $data
]);
