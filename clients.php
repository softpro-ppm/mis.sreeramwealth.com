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
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($client = mysqli_fetch_assoc($clients)): ?>
                    <tr>
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
                <form action="add_client.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control datepicker" name="date_of_birth" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
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

<?php include 'includes/footer.php'; ?> 