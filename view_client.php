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

// Check if client ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: clients.php");
    exit;
}

$client_id = $_GET['id'];

// Get client details
$sql = "SELECT * FROM clients WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $client = mysqli_fetch_assoc($result);
        
        if(!$client) {
            header("location: clients.php");
            exit;
        }
    }
}

// Get client's policies
$sql = "SELECT policy_number, type, premium, start_date, id FROM policies WHERE client_id = ? ORDER BY created_at DESC";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $policies = mysqli_stmt_get_result($stmt);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Client Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="clients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Clients
            </a>
            <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Client
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Name:</div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($client['name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Email:</div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($client['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Phone:</div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($client['phone']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Date of Birth:</div>
                        <div class="col-sm-8"><?php echo date('d M Y', strtotime($client['date_of_birth'])); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4 fw-bold">Address:</div>
                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($client['address'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Policies</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($policies) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Policy Number</th>
                                        <th>Type</th>
                                        <th>Premium</th>
                                        <th>Start Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($policy = mysqli_fetch_assoc($policies)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                                        <td>
                                            <?php 
                                            $type = htmlspecialchars($policy['type']);
                                            $typeClass = '';
                                            switch(strtolower($type)) {
                                                case 'health':
                                                    $typeClass = 'text-success';
                                                    break;
                                                case 'life':
                                                    $typeClass = 'text-primary';
                                                    break;
                                                case 'general':
                                                    $typeClass = 'text-info';
                                                    break;
                                            }
                                            echo '<span class="' . $typeClass . ' fw-bold text-capitalize">' . $type . '</span>';
                                            ?>
                                        </td>
                                        <td>₹<?php echo number_format($policy['premium'], 2); ?></td>
                                        <td><?php echo date('d M Y', strtotime($policy['start_date'])); ?></td>
                                        <td>
                                            <a href="view_policy.php?id=<?php echo $policy['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No policies found for this client.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 