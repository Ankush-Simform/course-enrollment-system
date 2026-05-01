<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../classes/enrollment.php'; 

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    die("Unauthorized access.");
}
    
$db = (new Database())->connect();

if (isset($_POST['btn_enroll'])) {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];

    try {
        $checkStmt = $db->prepare("SELECT current_enrolled, max_seats FROM courses WHERE id = ?");
        $checkStmt->execute([$course_id]);
        $course = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($course && $course['current_enrolled'] < $course['max_seats']) {
            
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$student_id, $course_id]);

            $updateStmt = $db->prepare("UPDATE courses SET current_enrolled = current_enrolled + 1 WHERE id = ?");
            $updateStmt->execute([$course_id]);

            $db->commit();
            header("Location: ../classes/manage_enrollments.php?type=enrollment&status=success");
            exit;
        } else {
            echo "<p style='color:red;'>Error: Course is full or does not exist.</p>";
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo "<p style='color:red;'>Error: Student is already enrolled in this course.</p>";
    }
}

$students = $db->query("SELECT id, name FROM users WHERE role_id = 3")->fetchAll(PDO::FETCH_ASSOC);
$courses = $db->query("SELECT id, course_name, current_enrolled, max_seats FROM courses")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2> New Enrollment</h2>

<form method="POST" style="max-width: 400px; border: 1px solid #ccc; padding: 20px; border-radius: 8px;">
    
    <label>Select Student:</label><br>
    <select name="student_id" required style="width: 100%; padding: 8px; margin-bottom: 15px;">
        <option value="">Select Student </option>
        <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (ID: <?= $s['id'] ?>)</option>
        <?php endforeach; ?>
    </select>

    <label>Select Course:</label><br>
    <select name="course_id" required style="width: 100%; padding: 8px; margin-bottom: 15px;">
        <option value=""> Select Course </option>
        <?php foreach ($courses as $c): ?>
            <?php $full = ($c['current_enrolled'] >= $c['max_seats']); ?>
            <option value="<?= $c['id'] ?>" <?= $full ? 'disabled' : '' ?>>
                <?= htmlspecialchars($c['course_name']) ?> 
                (<?= $c['current_enrolled'] ?>/<?= $c['max_seats'] ?> seats)
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="btn_enroll" style="background-color: #28a745; color: white; padding: 10px; border: none; width: 100%; cursor: pointer;">
        Enroll Student
    </button>
    
    <p><a href="../classes/manage_enrollments.php?type=enrollment">Back to List</a></p>
</form>