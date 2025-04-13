<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config/database.php";

// Handle client deletion
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $client_id = $_GET['delete'];
    $sql = "DELETE FROM clients WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $client_id);
        if(mysqli_stmt_execute($stmt)) {
            header("location: clients.php?status=deleted");
            exit();
        }
    }
}

// Get all clients
$sql = "SELECT * FROM clients ORDER BY created_at DESC";
$clients = mysqli_query($conn, $sql);
?>

<?php include 'includes/header.php'; ?>

<!-- Add validation script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

<style>
.error {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}
input.error, textarea.error {
    border-color: #dc3545;
}
input.valid, textarea.valid {
    border-color: #198754;
}
.name-uppercase {
    text-transform: uppercase;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Clients</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="fas fa-plus"></i> Add New Client
    </button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert alert-<?php echo $_GET['status'] == 'deleted' ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php 
        if($_GET['status'] == 'deleted') {
            echo "Client has been deleted successfully.";
        } elseif($_GET['status'] == 'added') {
            echo "New client has been added successfully.";
        } elseif($_GET['status'] == 'updated') {
            echo "Client information has been updated successfully.";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $serial = 1;
                    while($client = mysqli_fetch_assoc($clients)): 
                    ?>
                    <tr>
                        <td><?php echo $serial++; ?></td>
                        <td><?php echo $client['name']; ?></td>
                        <td><?php echo $client['email']; ?></td>
                        <td><?php echo $client['phone']; ?></td>
                        <td><?php echo date('d M Y', strtotime($client['date_of_birth'])); ?></td>
                        <td><?php echo substr($client['address'], 0, 30) . '...'; ?></td>
                        <td>
                            <a href="view_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="clients.php?delete=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger delete-btn" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm" action="add_client.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control name-uppercase" name="name" required 
                               pattern="[A-Z\s]+" title="Please use uppercase letters only"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required
                               pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control datepicker" name="date_of_birth" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required minlength="10"></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize form validation
    $("#clientForm").validate({
        rules: {
            name: {
                required: true,
                pattern: /^[A-Z\s]+$/
            },
            email: {
                required: true,
                email: true,
                remote: {
                    url: "check_email.php",
                    type: "post"
                }
            },
            phone: {
                required: true,
                pattern: /^[0-9]{10}$/,
                minlength: 10,
                maxlength: 10
            },
            date_of_birth: {
                required: true,
                date: true
            },
            address: {
                required: true,
                minlength: 10
            }
        },
        messages: {
            name: {
                required: "Please enter client name",
                pattern: "Name must be in uppercase letters only"
            },
            email: {
                required: "Please enter email address",
                email: "Please enter a valid email address",
                remote: "This email is already registered"
            },
            phone: {
                required: "Please enter phone number",
                pattern: "Please enter a valid 10-digit phone number",
                minlength: "Phone number must be 10 digits",
                maxlength: "Phone number must be 10 digits"
            },
            date_of_birth: {
                required: "Please enter date of birth",
                date: "Please enter a valid date"
            },
            address: {
                required: "Please enter address",
                minlength: "Address must be at least 10 characters long"
            }
        },
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback');
            element.closest('.mb-3').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function(form) {
            // Convert name to uppercase before submitting
            $('input[name="name"]').val($('input[name="name"]').val().toUpperCase());
            form.submit();
        }
    });

    // Auto-uppercase for name input
    $('input[name="name"]').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Phone number validation
    $('input[name="phone"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 