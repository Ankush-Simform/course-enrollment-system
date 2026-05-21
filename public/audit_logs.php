<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';
$db = (new Database())->connect();


if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 1) {
    header('Location: ../public/login.php');
    exit;
}


$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

$stmt = $db->prepare("
    SELECT a.*, u.name as admin_name 
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">System Audit Logs</h1>
        <a href="dashboard.php" class="btn btn-sm btn-secondary shadow-sm">Back to Dashboard</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>Changes (JSON)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="small"><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($l['admin_name'] ?? 'System') ?></strong></td>
                            <td>
                                <?php 
                                    $badge = 'badge-secondary';
                                    if($l['action'] == 'INSERT') $badge = 'badge-success';
                                    if($l['action'] == 'UPDATE') $badge = 'badge-primary';
                                    if($l['action'] == 'DELETE') $badge = 'badge-danger';
                                ?>
                                <span class="badge <?= $badge ?>"><?= $l['action'] ?></span>
                            </td>
                            <td><code><?= htmlspecialchars($l['table_name']) ?></code></td>
                            <td>#<?= htmlspecialchars($l['record_id']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" type="button" data-toggle="collapse" data-target="#details-<?= $l['id'] ?>">
                                    View Details
                                </button>
                                <div class="collapse mt-2" id="details-<?= $l['id'] ?>">
                                    <div class="card card-body bg-light small">
                                        <?php if($l['old_values']): ?>
                                            <b class="text-danger">Old:</b> <pre><?= htmlspecialchars($l['old_values']) ?></pre>
                                        <?php endif; ?>
                                        <?php if($l['new_values']): ?>
                                            <b class="text-success">New:</b> <pre><?= htmlspecialchars($l['new_values']) ?></pre>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>"> 
                            <a class="page-link" href="audit_logs.php?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>