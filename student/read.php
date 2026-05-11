<?php
session_start();
$type = $_GET['type'] ?? 'students';
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/body_top.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<a href="create.php">Add Student</a>

<table id="apiDataTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>

<!-- ✅ BOOTSTRAP MODAL (FIXED) -->
<div class="modal fade" id="apiEditModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-body">

        <input type="hidden" id="api_id">

        <input type="text" id="api_name" class="form-control mb-2" placeholder="Name">

        <input type="email" id="api_email" class="form-control mb-2" placeholder="Email">

        <button id="apiSaveBtn" class="btn btn-primary w-100">Save</button>

      </div>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- IMPORTANT: Bootstrap JS (FIX for modal issue) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const csrf = "<?= $_SESSION['csrf_token'] ?>";
const type = "<?= $type ?>";

const modal = new bootstrap.Modal(document.getElementById('apiEditModal'));

const table = $('#apiDataTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'student_api.php',
        type: 'GET',
        data: function (d) {
            d.action = 'fetch';
            d.type = type;
        }{ data: 'name' },
    },
    columns: [
        { data: 'id' },
        
        { data: 'email' },
        {
            data: null,
            orderable: false,
            render: function (data, type, row) {
                return `
                    <button class="btn btn-sm btn-primary edit-btn"
                        data-id="${row.id}"
                        data-name="${row.name}"
                        data-email="${row.email}">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn"
                        data-id="${row.id}">
                        Delete
                    </button>
                `;
            }
        }
    ]
});

$(document).on('click', '.edit-btn', function () {

    $('#api_id').val($(this).data('id'));
    $('#api_name').val($(this).data('name'));
    $('#api_email').val($(this).data('email'));

    modal.show();
});

$('#apiSaveBtn').click(function () {

    $.ajax({
        url: 'student_api.php?action=update',
        type: 'POST',
        dataType: 'json',
        data: {
            id: $('#api_id').val(),
            name: $('#api_name').val(),
            email: $('#api_email').val(),
            csrf_token: csrf
        },
        success: function (res) {

            if (res.status === 'success') {
                modal.hide();
                table.ajax.reload(null, false);
            } else {
                alert(res.message);
            }
        }
    });
});

$(document).on('click', '.delete-btn', function () {

    if (!confirm("Delete this user?")) return;

    $.post('student_api.php?action=delete', {
        id: $(this).data('id'),
        csrf_token: csrf
    }, function (res) {

        if (res.status === 'success') {
            table.ajax.reload(null, false);
        } else {
            alert(res.message);
        }

    }, 'json');
});
</script>

<?php include '../includes/body_bottom.php'; ?>
<?php include '../includes/footer.php'; ?>