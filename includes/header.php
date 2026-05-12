<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role_id'] ?? 'null'; 
?>


<?php if ($role == 1): ?>
    <a href="../public/dashboard.php">Admin</a>

<?php elseif ($role == 2): ?>
    <a href="teacher_dashboard.php">Teacher</a>

<?php endif; ?>
 <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">


<header style="
background: #f7f5f1;
    padding: 0 5%; 
    height: 70px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;     
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
">
    <div style="font-size: 1.5rem; font-weight: bold; color: #333;">
       <a href="index.php" style="text-decoration: none; color: inherit;">Enrollment<span style="color: #0a0a0a;">System</span></a>
    </div>

    <nav style="display: flex; gap: 20px;">
        <a href="#" style="text-decoration: none; color: #555; font-weight: 500;">Home</a>
        
        <?php if ($role === 'admin'): ?>
            <a href="admin_dashboard.php" style="text-decoration: none; color: #555;">Manage Users</a>
            <a href="reports.php" style="text-decoration: none; color: #555;">Reports</a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="my_classes.php" style="text-decoration: none; color: #555;">My Classes</a>
            <a href="grading.php" style="text-decoration: none; color: #555;">Grading</a>
        <?php elseif ($role === 'student'): ?>
            <a href="my_courses.php" style="text-decoration: none; color: #555;">Courses</a>
            <a href="results.php" style="text-decoration: none; color: #555;">Results</a>
        <?php endif; ?>
    </nav>

    <div style="display: flex; align-items: center; gap: 15px;">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div style="font-size: 0.9rem; color: #666;">
                Logged in as: <span style="color: #333; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span style="background: #e9ecef; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 5px; text-transform: uppercase;">
                    <?php echo $role; ?>
                </span>
            </div>
            <a href="/public/logout.php" style="
                background: #ff4d4d; 
                color: white; 
                padding: 8px 16px; 
                border-radius: 5px; 
                text-decoration: none; 
                font-size: 0.85rem;
                transition: 0.3s;
            ">Logout</a>
        <?php else: ?>
            <a href="login.php" style="text-decoration: none; color: #555; font-weight: 500;">Login</a>
            <a href="signup.php" style="
                background: #007bff; 
                color: white; 
                padding: 8px 16px; 
                border-radius: 5px; 
                text-decoration: none;
            ">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<!-- jQuery FIRST -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<!-- DataTables core -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- Buttons extension -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- <script>
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
</script> -->