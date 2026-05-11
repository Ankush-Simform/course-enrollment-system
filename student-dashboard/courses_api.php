<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

verify_csrf();

header('Content-Type: application/json');

if (
    !isset($_SESSION['user_id']) ||
    (int)$_SESSION['role_id'] !== 3
) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]));
}

$db = (new Database())->connect();

$student_id = (int)$_SESSION['user_id'];

$course_id = (int)($_POST['course_id'] ?? 0);

$action = trim($_POST['action'] ?? '');

try {

    $db->beginTransaction();

    if ($action === 'enroll') {

        $stmt = $db->prepare("
            SELECT
                current_enrolled,
                max_seats
            FROM courses
            WHERE id = ?
            FOR UPDATE
        ");

        $stmt->execute([$course_id]);

        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            throw new Exception('Course not found');
        }


        $stmt = $db->prepare("
            SELECT id
            FROM enrollments
            WHERE student_id = ?
            AND course_id = ?
        ");

        $stmt->execute([
            $student_id,
            $course_id
        ]);

        if ($stmt->fetch()) {
            throw new Exception('Already enrolled');
        }

        if (
            $course['current_enrolled']
            >=
            $course['max_seats']
        ) {
            throw new Exception('Course full');
        }

        $stmt = $db->prepare("
            INSERT INTO enrollments
            (
                student_id,
                course_id,
                status
            )
            VALUES
            (
                ?, ?, 'active'
            )
        ");

        $stmt->execute([
            $student_id,
            $course_id
        ]);

        $stmt = $db->prepare("
            UPDATE courses
            SET current_enrolled = current_enrolled + 1
            WHERE id = ?
        ");

        $stmt->execute([$course_id]);

    }

    elseif ($action === 'cancel') {

        $stmt = $db->prepare("
            DELETE FROM enrollments
            WHERE student_id = ?
            AND course_id = ?
        ");

        $stmt->execute([
            $student_id,
            $course_id
        ]);

        if (!$stmt->rowCount()) {
            throw new Exception('Enrollment not found');
        }

        $stmt = $db->prepare("
            UPDATE courses
            SET current_enrolled =
                CASE
                    WHEN current_enrolled > 0
                    THEN current_enrolled - 1
                    ELSE 0
                END
            WHERE id = ?
        ");

        $stmt->execute([$course_id]);

    }



    else {

        throw new Exception('Invalid action');
    }

    $db->commit();

    echo json_encode([
        'status' => 'success'
    ]);

} catch (Exception $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}