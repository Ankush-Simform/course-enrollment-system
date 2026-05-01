<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';


if ($_SESSION['role_id'] != 1) {
    die("Unauthorized");
}

$db = (new Database())->connect();

if (isset($_POST['create_course'])) {
    $stmt = $db->prepare("
        INSERT INTO courses 
        (course_name, instructor_id, duration_weeks, max_seats) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['course_name'],
        $_POST['instructor_id'],
        $_POST['duration_weeks'],
        $_POST['max_seats']
    ]);


    header("Location: /classes/course.php");
}

?>
<a href="../classes/course.php?type=courses">Back to Admin</a>
<h2>Add Course</h2>



<form method="POST">
    Course Name: <input type="text" name="course_name"><br><br>
    Instructor ID: <input type="number" name="instructor_id"><br><br>
    Duration: <input type="number" name="duration_weeks"><br><br>
    Max Seats: <input type="number" name="max_seats"><br><br>

    <button name="create_course">Create</button>
</form>

<?php

require_once '../includes/footer.php';?>