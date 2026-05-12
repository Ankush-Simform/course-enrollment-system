<?php

require_once '../../config/database.php';

header('Content-Type: application/json');

$db = (new Database())->connect();

$data = [

    'students' => $db->query("
        SELECT COUNT(*)
        FROM users
        WHERE role_id=3
    ")->fetchColumn(),

    'teachers' => $db->query("
        SELECT COUNT(*)
        FROM users
        WHERE role_id=2
    ")->fetchColumn(),

    'courses' => $db->query("
        SELECT COUNT(*)
        FROM courses
    ")->fetchColumn(),

    'enrollments' => $db->query("
        SELECT COUNT(*)
        FROM enrollments
    ")->fetchColumn()
];

echo json_encode($data);