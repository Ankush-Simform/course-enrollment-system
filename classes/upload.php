<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../includes/header.php';
require_once '../includes/body_top.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['user_csv'])) {

    $file = $_FILES['user_csv']['tmp_name'];

    if (is_uploaded_file($file) && $_FILES['user_csv']['size'] > 0) {

        $fileType = mime_content_type($file);
        if ($fileType !== 'text/plain' && $fileType !== 'text/csv') {
            $message = "<div style='color:red;'>Only CSV files allowed</div>";
        } else {

            $handle = fopen($file, "r");
            fgetcsv($handle, 1000, ",", '"', "\\");
            $db = (new Database())->connect();
            $db->beginTransaction();

            try {

                $stmtUser = $db->prepare("
                    INSERT INTO users (name, email, password, role_id)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        name = VALUES(name),
                        password = VALUES(password),
                        role_id = VALUES(role_id)
                ");

                $stmtProfile = $db->prepare("
                    INSERT INTO profiles (user_id) VALUES (?)
                ");

                $count = 0;

                while (($data = fgetcsv($handle, 1000, ",", '"', "\\")) !== false)  {

                    $name     = htmlspecialchars(trim($data[0] ?? ''));
                    $email    = filter_var(trim($data[1] ?? ''), FILTER_VALIDATE_EMAIL);
                    $password = trim($data[2] ?? '');
                    $role_id  = intval($data[3] ?? 2);

                    if (!$name || !$email || !$password) {
                        continue;
                    }

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    $stmtUser->execute([$name, $email, $hashedPassword, $role_id]);

                    $user_id = $db->lastInsertId();

                    if (!$user_id) {
                        $stmtFetch = $db->prepare("SELECT id FROM users WHERE email = ?");
                        $stmtFetch->execute([$email]);
                        $user_id = $stmtFetch->fetchColumn();
                    }

                    $stmtProfile->execute([$user_id]);

                    $count++;
                }
 
                $db->commit();

                $message = "<div style='color:green;'>Success! $count users imported.</div>";

            } catch (Exception $e) {

                $db->rollBack();
                $message = "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
            }

            fclose($handle);
        }

    } else {
        $message = "<div style='color:orange;'>Please upload valid file</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk User Upload</title>
</head>
<body>

<div style="max-width:600px;margin:auto;">
    <h2>Bulk User Upload</h2>

    <?php echo $message; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="user_csv" accept=".csv" required><br><br>
        <button type="submit">Upload</button>
    </form>

    <p>CSV Format: Name, Email, Password, Role_ID</p>
</div>

</body>
</html>

<?php  require_once '../includes/footer.php';
?>