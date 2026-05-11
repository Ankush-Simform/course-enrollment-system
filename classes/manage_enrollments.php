<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/body_top.php';
require_once 'enrollment.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 1) {
    header('Location: ../public/login.php');
    exit;
}

$db = (new Database())->connect();
$enrollManager = new Enrollment($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0) {
        if ($action === 'cancel') {
            $enrollManager->cancel($id);
        }

        if ($action === 'complete') {
            $stmt = $db->prepare(
                "UPDATE enrollments
                 SET status = 'completed'
                 WHERE id = ?"
            );
            $stmt->execute([$id]);
        }
    }

    header('Location: manage_enrollments.php');
    exit;
}

$list = $enrollManager->getAll();
?>

<div style="display:flex;justify-content:space-between;align-items:center;">
    <a href="../public/dashboard.php"
       style="padding:8px 15px;background:#007bff;color:white;text-decoration:none;border-radius:5px;">
        Home
    </a>

    <h3>Enrollment Management</h3>

    <a href="../Enrollment/create.php"
       style="padding:8px 15px;background:#28a745;color:white;text-decoration:none;border-radius:5px;">
        + New Enrollment
    </a>
</div>

<table border="1" cellpadding="10" id= "table"
       style="width:100%;border-collapse:collapse;margin-top:20px;">

    <thead style="background:#333;color:#fff;">
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Course</th>
            <th>Instructor</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($list)): ?>
            <tr>
                <td colspan="7" style="text-align:center;">
                    No records found.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>

                    <td>
                        <strong>
                            <?php echo htmlspecialchars($row['student_name']); ?>
                        </strong>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['course_name']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                    </td>

                    <td>
                        <?php echo date(
                            'M d, Y',
                            strtotime($row['enrolled_date'])
                        ); ?>
                    </td>

                    <td>
                        <?php echo ucfirst($row['status']); ?>
                    </td>

                    <td>
                        <?php if ($row['status'] === 'active'): ?>

                            <form method="POST" style="display:inline;">
                                <?php csrf_field(); ?>
                                <input type="hidden"
                                       name="id"
                                       value="<?php echo $row['id']; ?>">
                                <input type="hidden"
                                       name="action"
                                       value="complete">

                                <button type="submit"
                                        onclick="return confirm('Mark as completed?')">
                                    Complete
                                </button>
                            </form>

                            |

                            <form method="POST" style="display:inline;">
                                <?php csrf_field(); ?>
                                <input type="hidden"
                                       name="id"
                                       value="<?php echo $row['id']; ?>">
                                <input type="hidden"
                                       name="action"
                                       value="cancel">

                                <button type="submit"
                                        onclick="return confirm('Cancel this enrollment?')">
                                    Cancel
                                </button>
                            </form>

                        <?php else: ?>
                            <span style="color:#666;">No Actions</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once '../includes/footer.php'; ?>

<script>
let table;

$(document).ready(function () {
  table = $("#table").DataTable({
    destroy: true, // it will destroy the datatables if already exists in the table
    "searching":true,
    "paging":true,
    "pageLength":5,
    "columnDefs":[{
        "targets":[3],
        "orderable":true   
    },
    {
      "targets":[],
      "visible":false,
      "searchable":true 
    }
  ],
    
    dom:'Bfrtip', 
    
    buttons:[
      'copy','csv','excel','pdf','print'
    ]
  });
});
</script>