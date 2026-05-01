<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
include '../includes/header.php';
include '../includes/body_top.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 2) {
    header("Location: login.php");
    exit;
}

$db = (new Database())->connect();
$teacherId = $_SESSION['user_id'];
$statusMsg = "";

if (isset($_POST['add_student_submit'])) {

    $targetStudent = (int) $_POST['student_id'];
    $targetCourse  = (int) $_POST['course_id'];

    try {
        $check = $db->prepare("
            SELECT id
            FROM enrollments
            WHERE student_id = ? AND course_id = ?
        ");
        $check->execute([$targetStudent, $targetCourse]);

        if ($check->fetch()) {
            $statusMsg = "Error: Student is already enrolled in this course.";
        } else {
            $courseStmt = $db->prepare("
                SELECT current_enrolled, max_seats
                FROM courses
                WHERE id = ?
                AND instructor_id = ?
                FOR UPDATE
            ");
            $courseStmt->execute([$targetCourse, $teacherId]);
            $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                $statusMsg = "Invalid course selected.";
            } elseif ((int)$course['current_enrolled'] >= (int)$course['max_seats']) {
                $statusMsg = "Course is already full.";
            } else {
                $db->beginTransaction();

                try {

                    $enroll = $db->prepare("
                        INSERT INTO enrollments (student_id, course_id, status)
                        VALUES (?, ?, 'active')
                    ");
                    $enroll->execute([$targetStudent, $targetCourse]);

                    $update = $db->prepare("
                        UPDATE courses
                        SET current_enrolled = current_enrolled + 1
                        WHERE id = ?
                    ");
                    $update->execute([$targetCourse]);

                    $db->commit();

                    $statusMsg = "Student successfully enrolled in the course.";

                } catch (PDOException $e) {

                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    error_log("Enrollment failed: " . $e->getMessage());
                    $statusMsg = "Failed to enroll student. Please try again.";
                }
            }
        }

    } catch (PDOException $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log("Teacher enrollment error: " . $e->getMessage());
        $statusMsg = "System error occurred.";
    }
}


$selectedCourseId = isset($_GET['course_filter']) ? (int)$_GET['course_filter'] : null;

$courseListQuery = $db->prepare("
    SELECT id, course_name
    FROM courses
    WHERE instructor_id = ?
    AND deleted_at IS NULL
");
$courseListQuery->execute([$teacherId]);
$myCourses = $courseListQuery->fetchAll(PDO::FETCH_ASSOC);

$roster = [];

if ($selectedCourseId) {
    $rosterQuery = $db->prepare("
        SELECT 
            e.id as enroll_id,
            u.name as student_name,
            u.email as student_email,
            c.course_name 
        FROM enrollments e
        INNER JOIN users u ON e.student_id = u.id
        INNER JOIN courses c ON e.course_id = c.id
        WHERE c.id = ? AND c.instructor_id = ?
    ");
    $rosterQuery->execute([$selectedCourseId, $teacherId]);
    $roster = $rosterQuery->fetchAll(PDO::FETCH_ASSOC);
}

$allStudents = $db->query("
    SELECT id, name 
    FROM users 
    WHERE role_id = 3 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Teacher Administration</h2>

<?php if ($statusMsg): ?>
    <div style="padding: 10px; border: 1px solid #333; margin-bottom: 15px;">
        <strong>System Message:</strong>
        <?php echo htmlspecialchars($statusMsg); ?>
    </div>
<?php endif; ?>

<section>
    <form method="GET" action="">
        <label><strong>Select Course</strong></label>
        <select name="course_filter" onchange="this.form.submit()">
            <option value="">-- Select One of Your Courses --</option>
            <?php foreach ($myCourses as $course): ?>
                <option value="<?php echo $course['id']; ?>"
                    <?php echo ($selectedCourseId == $course['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Filter</button></noscript>
    </form>
</section>

<br>

<section>
    <h3>Selected Course</h3>
    <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Email</th>
                <th>Course</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($selectedCourseId && $roster): ?>
                <?php foreach ($roster as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php elseif ($selectedCourseId): ?>
                <tr><td colspan="3">No students enrolled.</td></tr>
            <?php else: ?>
                <tr><td colspan="3">Please select a course.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<hr style="margin:30px 0;">

<section>
    <h3>Enroll a New Student</h3>

    <form method="POST">
        <table>
            <tr>
                <td>Student:</td>
                <td>
                    <select name="student_id" required>
                        <option value="">Choose student</option>
                        <?php foreach ($allStudents as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Course:</td>
                <td>
                    <select name="course_id" required>
                        <option value="">Choose course</option>
                        <?php foreach ($myCourses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <button type="submit" name="add_student_submit">Enroll</button>
                </td>
            </tr>
        </table>
    </form>
</section>

<?php include '../includes/body_bottom.php'; ?>