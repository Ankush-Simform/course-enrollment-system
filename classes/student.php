	<?php
    require_once '../includes/auth.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        verify_csrf();

        require_once '../config/database.php';

        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 3) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $db = (new Database())->connect();
        $student_id = $_SESSION['user_id'];

        $action = $_POST['action'] ?? '';
        $course_id = (int)($_POST['course_id'] ?? 0);

        if ($action === 'enroll') {
            try {
                $db->beginTransaction();

                $courseStmt = $db->prepare(
                    "SELECT current_enrolled, max_seats
                    FROM courses
                    WHERE id = ?
                    FOR UPDATE"
                );
                $courseStmt->execute([$course_id]);
                $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

                if (!$course) {
                    $db->rollBack();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Course not found'
                    ]);
                    exit;
                }

                $chk = $db->prepare(
                    "SELECT id
                    FROM enrollments
                    WHERE student_id = ? AND course_id = ?"
                );
                $chk->execute([$student_id, $course_id]);

                if ($chk->fetch()) {
                    $db->rollBack();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Already enrolled'
                    ]);
                    exit;
                }

                if ((int)$course['current_enrolled'] >= (int)$course['max_seats']) {
                    $db->rollBack();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Course is full'
                    ]);
                    exit;
              }
                $stmt = $db->prepare(
                    "INSERT INTO enrollments
                    (student_id, course_id, status)
                    VALUES (?, ?, 'active')"
                );
                $stmt->execute([$student_id, $course_id]);

                $stmt = $db->prepare(
                    "UPDATE courses
                    SET current_enrolled = current_enrolled + 1
                    WHERE id = ?"
                );
                $stmt->execute([$course_id]);

                $db->commit();

                echo json_encode([
                    'status' => 'success'
                ]);
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Enrollment failed. Please try again.'
                ]);
            }

            exit;
        } elseif ($action === 'cancel') {
            try {
                $db->beginTransaction();

                $del = $db->prepare(
                    "DELETE FROM enrollments
                    WHERE student_id = ? AND course_id = ?"
                );
                $del->execute([$student_id, $course_id]);

                if ($del->rowCount() === 0) {
                    $db->rollBack();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Enrollment not found'
                    ]);
                    exit;
                }

                $stmt = $db->prepare(
                    "UPDATE courses
                    SET current_enrolled = current_enrolled - 1
                    WHERE id = ?
                    AND current_enrolled > 0"
                );
                $stmt->execute([$course_id]);

                $db->commit();

                echo json_encode([
                    'status' => 'success'
                ]);
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cancellation failed.'
                ]);
            }

            exit;
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
        }

        exit;
    }

    include_once '../includes/header.php';
    include '../includes/body_top.php';
    require_once '../config/database.php';

    if (!isset($_SESSION['user_id']) || (int)$_SESSION['role_id'] !== 3) {
        header("Location: ../public/login.php");
        exit;
    }

    $db = (new Database())->connect();
    $student_id = $_SESSION['user_id'];

    $myCourses = $db->prepare("
    SELECT c.id, c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
    ");
    $myCourses->execute([$student_id]);
    $myCourses = $myCourses->fetchAll(PDO::FETCH_ASSOC);

    $myIds = array_column($myCourses, 'id');

    $allCourses = $db->query("
    SELECT c.*, u.name AS teacher
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
        ")->fetchAll(PDO::FETCH_ASSOC);
    ?>

	<meta name="csrf-token"
	    content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

	<h2>My Dashboard</h2>

	<h3>My Courses</h3>
	<ul id="myCoursesList">
	    <?php if (!$myCourses): ?>
	        <li class="empty-message">No courses yet.</li>
	    <?php else: ?>
	        <?php foreach ($myCourses as $c): ?>
	            <li data-id="<?= $c['id'] ?>">
	                <?= htmlspecialchars($c['course_name']) ?>
	                <button class="cancel-btn"
	                    data-id="<?= $c['id'] ?>">
	                    Cancel
	                </button>
	            </li>
	        <?php endforeach; ?>
	    <?php endif; ?>
	</ul>

	<hr>

	<h3>All Courses</h3>

	<table border="1" cellpadding="6">
	    <tr>
	        <th>Course</th>
	        <th>Teacher</th>
	        <th>Seats</th>
	        <th>Action</th>
	    </tr>

	    <?php foreach ($allCourses as $c): ?>
	        <tr data-id="<?= $c['id'] ?>">
	            <td><?= htmlspecialchars($c['course_name']) ?></td>
	            <td><?= htmlspecialchars($c['teacher'] ?? 'TBA') ?></td>
	            <td class="seat-cell">
	                <?= $c['current_enrolled'] ?>/<?= $c['max_seats'] ?>
	            </td>
	            <td class="action-cell">
	                <?php if (in_array($c['id'], $myIds)): ?>
	                    <span>Enrolled</span>
	                <?php elseif ($c['current_enrolled'] >= $c['max_seats']): ?>
	                    <span>Full</span>
	                <?php else: ?>
	                    <button class="enroll-btn"
	                        data-id="<?= $c['id'] ?>">
	                        Enroll
	                    </button>
	                <?php endif; ?>
	            </td>
	        </tr>
	    <?php endforeach; ?>
	</table>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<script>
	    const csrfToken = document
	        .querySelector('meta[name="csrf-token"]')
	        .getAttribute('content');

	    $(document).on("click", ".enroll-btn", function() {
	        let btn = $(this);
	        let courseId = btn.data("id");

	        $.ajax({
	            url: "student.php",
	            type: "POST",
	            dataType: "json",
	            data: {
	                action: "enroll",
	                course_id: courseId,
	                csrf_token: csrfToken
	            },
	            success: function(res) {
	                if (res.status === "success") {
	                    let row = btn.closest("tr");

	                    row.find(".action-cell")
	                        .html("<span>Enrolled</span>");

	                    let seatCell = row.find(".seat-cell");
	                    let parts = seatCell.text().split("/");
	                    let current = parseInt(parts[0]) + 1;
	                    let max = parts[1];

	                    seatCell.text(current + "/" + max);

	                    $(".empty-message").remove();

	                    $("#myCoursesList").append(`
                        <li data-id="${courseId}">
                        ${row.find("td:first").text()}
                        <button class="cancel-btn"
                        data-id="${courseId}">
                        Cancel
                        </button>
                        </li>
                        `);
	                } else {
	                    alert(res.message || "Enrollment failed.");
	                }
	            }
	        });
	    });

	    $(document).on("click", ".cancel-btn", function() {
	        let btn = $(this);
	        let courseId = btn.data("id");

	        $.ajax({
	            url: "student.php",
	            type: "POST",
	            dataType: "json",
	            data: {
	                action: "cancel",
	                course_id: courseId,
	                csrf_token: csrfToken
	            },
	            success: function(res) {
	                if (res.status === "success") {

	                    $(`#myCoursesList li[data-id='${courseId}']`).remove();

	                    if ($("#myCoursesList li").length === 0) {
	                        $("#myCoursesList").html(
	                            '<li class="empty-message">No courses yet.</li>'
	                        );
	                    }

	                    let row = $(`tr[data-id='${courseId}']`);

	                    row.find(".action-cell").html(`
                        <button class="enroll-btn"
                        data-id="${courseId}">
                        Enroll
                        </button>
                        `);

	                    let seatCell = row.find(".seat-cell");
	                    let parts = seatCell.text().split("/");
	                    let current = parseInt(parts[0]) - 1;
	                    let max = parts[1];

	                    seatCell.text(current + "/" + max);
	                } else {
	                    alert(res.message || "Cancellation failed.");
	                }
	            }
	        });
	    });
	</script>

	<?php include '../includes/body_bottom.php'; ?>
	<?php include_once '../includes/footer.php'; ?>