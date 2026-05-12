<?php
session_start();
require_once '../includes/header.php';
require_once '../includes/body_top.php';

if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 1) {
    die("Unauthorized");
}
?>

<button id="addCourseBtn">+ Add Course</button>

<table id="courseTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Course</th>
            <th>Instructor</th>
            <th>Duration</th>
            <th>Seats</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>

<!-- MODAL -->
<div id="courseModal" style="display:none;">
    <input type="hidden" id="course_id">

    <input type="text" id="course_name" placeholder="Course"><br>
    <input type="number" id="instructor_id" placeholder="Instructor"><br>
    <input type="number" id="duration_weeks"><br>
    <input type="number" id="max_seats"><br>

    <button id="saveCourseBtn">Save</button>
    <button onclick="$('#courseModal').hide()">Close</button>
</div>

<?php require_once '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {

    let table = $('#courseTable').DataTable({

        processing: true,
        serverSide: true,

        ajax: {
            url: 'course_api.php?action=fetch',
            type: 'GET'
        },

        columns: [
            { data: 'id' },
            { data: 'course_name' },
            { data: 'instructor_name' },
            { data: 'duration_weeks' },
            { data: 'max_seats' },

            {
                data: null,
                render: function (data) {

                    return `
                        <button class="editBtn" data-id="${data.id}">Edit</button>
                        <button class="deleteBtn" data-id="${data.id}">Delete</button>
                    `;
                }
            }
        ]
    });

    $('#addCourseBtn').click(function () {

        $('#course_id').val('');
        $('#course_name').val('');
        $('#instructor_id').val('');
        $('#duration_weeks').val('');
        $('#max_seats').val('');

        $('#courseModal').show();
    });


    $('#saveCourseBtn').click(function () {

        let id = $('#course_id').val();
        let action = id ? 'update' : 'create';

        $.post('course_api.php?action=' + action, {

            id: id,
            course_name: $('#course_name').val(),
            instructor_id: $('#instructor_id').val(),
            duration_weeks: $('#duration_weeks').val(),
            max_seats: $('#max_seats').val()

        }, function () {

            $('#courseModal').hide();
            table.ajax.reload(null, false);
        });
    });

    $(document).on('click', '.editBtn', function () {

        let row = table.row($(this).closest('tr')).data();

        $('#course_id').val(row.id);
        $('#course_name').val(row.course_name);
        $('#instructor_id').val(row.instructor_id);
        $('#duration_weeks').val(row.duration_weeks);
        $('#max_seats').val(row.max_seats);

        $('#courseModal').show();
    });


    $(document).on('click', '.deleteBtn', function () {

        if (!confirm('Delete course?')) return;

        $.post('course_api.php?action=delete', {
            id: $(this).data('id')
        }, function () {

            table.ajax.reload(null, false);
        });
    });

});
</script>