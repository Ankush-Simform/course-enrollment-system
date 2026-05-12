<?php
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../public/login.php");
    exit;
}

include '../includes/header.php';
?>

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">

<link rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<h2>My Dashboard</h2>

<h3>My Courses</h3>
<table id="myTable" class="display">
    <thead>
        <tr>
            <th>Course</th>
            <th>Action</th>
        </tr>
    </thead>
</table>

<hr>

<h3>All Courses</h3>
<table id="courseTable" class="display">
    <thead>
        <tr>
            <th>Course</th>
            <th>Teacher</th>
            <th>Seats</th>
            <th>Action</th>
        </tr>
    </thead>
</table>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    const csrf = $('meta[name="csrf-token"]').attr('content');

    const courseTable = $('#courseTable').DataTable({

    processing: true,
    serverSide: true,   
        ajax: {
            url: 'courses_table_api.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = csrf;
            }
        },
        columns: [{
                data: 'course_name'
            },
            {
                data: 'teacher'
            },
            {
                data: 'seats'
            },
            {
                data: 'action'
            }
        ]
    });

    const myTable = $('#myTable').DataTable({
        ajax: {
            url: 'my_courses_api.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = csrf;
            }
        },
        columns: [{
                data: 'course_name'
            },
            {
                data: 'action'
            }
        ]
    });

    $(document).on('click', '.enroll-btn,.cancel-btn', function() {

        $.post('course_api.php', {
            csrf_token: csrf,
            course_id: $(this).data('id'),
            action: $(this).hasClass('enroll-btn') ?
                'enroll' :
                'cancel'
        }, res => {

            if (res.status === 'success') {
                courseTable.ajax.reload();
                myTable.ajax.reload();
            } else {
                alert(res.message);
            }

        }, 'json');

    });
</script>