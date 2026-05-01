<?php
require_once '../config/database.php';
include '../includes/header.php';
include '../includes/body_top.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    die("Unauthorized access.");
}

$db = (new Database())->connect();

/*
|--------------------------------------------------------------------------
| Validate Type
|--------------------------------------------------------------------------
*/
$allowedTypes = ['students', 'teachers'];
$type = $_GET['type'] ?? 'students';

if (!in_array($type, $allowedTypes, true)) {
    $type = 'students';
}

$page = max(1, (int)($_GET['page'] ?? 1));

$limit = 10;
$offset = ($page - 1) * $limit;

$role_id = ($type === 'teachers') ? 2 : 3;

$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
$stmt->execute([$role_id]);
$totalRows = $stmt->fetchColumn();

$totalPages = max(1, ceil($totalRows / $limit));

$stmt = $db->prepare("
    SELECT id, name, email
    FROM users
    WHERE role_id = ?
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $role_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<a href="../public/dashboard.php">Home</a><br>

<!-- Dynamic Add Link -->
<a href="../student/create.php?type=<?php echo urlencode($type); ?>">
    Add <?php echo ucfirst(rtrim($type, 's')); ?>
</a>

<h3><?php echo ucfirst($type); ?> List</h3>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Action</th>
    </tr>

    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['id']); ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
                <a href="../student/update.php?id=<?php echo $u['id']; ?>">
                    Update
                </a>

                <a href="../student/delete.php?id=<?php echo $u['id']; ?>"
                   onclick="return confirm('Are you sure you want to delete this user?');">
                    Delete
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<br>

<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="read.php?type=<?php echo urlencode($type); ?>&page=<?php echo $i; ?>">
        <?php echo $i; ?>
    </a>
<?php endfor; ?>

<?php
include '../includes/body_bottom.php';
include '../includes/footer.php';
?>