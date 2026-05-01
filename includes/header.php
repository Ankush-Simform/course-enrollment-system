<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role_id'] ?? ''; 
?>

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