<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';


if (($_SESSION['role_id'] ?? 0) != 1) exit;

$db = (new Database())->connect();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid ID");
}

/* Fetch course */
$stmt = $db->prepare("SELECT * FROM courses WHERE id=?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Course not found");
}

/* Update */
if (isset($_POST['update_course'])) {

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
        $id
    ]);

    header("Location: /classes/course.php");
    exit;
}
?>

<h2>Edit Course</h2>

<form method="POST">
    Course Name:
    <input type="text" name="course_name" value="<?php echo htmlspecialchars((string)$course['course_name'], ENT_QUOTES, 'UTF-8'); ?>"><br><br>

    Instructor ID:
    <input type="number" name="instructor_id" value="<?php echo htmlspecialchars((string)$course['course_name'], ENT_QUOTES, 'UTF-8'); ?>"><br><br>

    Duration:
    <input type="number" name="duration_weeks" value="<?php echo htmlspecialchars((string)$course['course_name'], ENT_QUOTES, 'UTF-8'); ?>"><br><br>

    Max Seats:
    <input type="number" name="max_seats" value="<?php echo htmlspecialchars((string)$course['course_name'], ENT_QUOTES, 'UTF-8'); ?>"><br><br>

    <button name="update_course">Update</button>
</form>

<?php
require_once '../includes/footer.php';
?>