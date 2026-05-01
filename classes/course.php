<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';

$db = (new Database())->connect();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $countStmt = $db->query("SELECT COUNT(*) FROM courses");
    $totalRows = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.course_name,
            c.duration_weeks,
            c.max_seats,
            c.current_enrolled,
            u.name AS instructor_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        ORDER BY c.id DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<a href="../public/dashboard.php">Home</a> |
<a href="../courses/create.php">Add Course</a>

<h2>Course Catalog</h2>

<table border="1" cellpadding="10" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Course Name</th>
            <th>Instructor</th>
            <th>Duration</th>
            <th>Availability</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($courses)): ?>
            <tr>
                <td colspan="6">No courses available.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?php echo $course['id']; ?></td>
                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?>
                    </td>
                    <td><?php echo $course['duration_weeks']; ?> Weeks</td>
                    <td>
                        <?php echo $course['current_enrolled']; ?> /
                        <?php echo $course['max_seats']; ?>
                    </td>
                    <td>
                        <a href="../courses/update.php?id=<?php echo $course['id']; ?>">
                            Edit
                        </a>
                        |
                        <a href="../courses/delete.php?id=<?php echo $course['id']; ?>"
                           onclick="return confirm('Delete this course?');">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<br>

<div>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
            <strong><?php echo $i; ?></strong>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>
</div>

<?php require_once '../includes/footer.php'; ?>