<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
error_reporting(E_ALL);
ini_set('display_errors', 0);
verify_csrf();

header('Content-Type: application/json');

$db = (new Database())->connect();

$stmt = $db->prepare("
    SELECT c.id,c.course_name
    FROM enrollments e
    JOIN courses c
    ON c.id=e.course_id
    WHERE e.student_id=?
");

$stmt->execute([$_SESSION['user_id']]);

$data = [];

foreach($stmt as $r){

    $data[] = [

        'course_name'=>$r['course_name'],

        'action'=>"
            <button
                class='cancel-btn'
                data-id='{$r['id']}'
            >
                Cancel
            </button>
        "
    ];
}

echo json_encode(['data'=>$data]);