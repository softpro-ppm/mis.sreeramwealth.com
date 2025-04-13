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

// Check if policy ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: policies.php");
    exit;
}

$policy_id = $_GET['id'];

// Get policy details with client information
$sql = "SELECT p.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
        CASE 
            WHEN p.type = 'health' THEN h.coverage_type
            WHEN p.type = 'life' THEN l.term_years
            WHEN p.type = 'general' THEN g.insurance_type
        END as type_detail,
        CASE 
            WHEN p.type = 'health' THEN h.pre_existing_conditions
            WHEN p.type = 'life' THEN l.beneficiaries
            WHEN p.type = 'general' THEN g.property_details
        END as additional_detail
        FROM policies p 
        JOIN clients c ON p.client_id = c.id 
        LEFT JOIN health_insurance h ON p.id = h.policy_id
        LEFT JOIN life_insurance l ON p.id = l.policy_id
        LEFT JOIN general_insurance g ON p.id = g.policy_id
        WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $policy_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    header("location: policies.php");
    exit;
}

$policy = mysqli_fetch_assoc($result);

// Get policy documents
$sql = "SELECT * FROM documents WHERE policy_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $policy_id);
mysqli_stmt_execute($stmt);
$documents = mysqli_stmt_get_result($stmt);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Policy Details</h2>
    <div>
        <a href="edit_policy.php?id=<?php echo $policy_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Policy
        </a>
        <a href="policies.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Policies
        </a>
    </div>
</div>

<div class="row">
    <!-- Policy Details Card -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Policy Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Policy Number:</strong><br> <?php echo $policy['policy_number']; ?></p>
                        <p><strong>Type:</strong><br> <?php echo ucfirst($policy['type']); ?></p>
                        <p><strong>Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $policy['status'] == 'active' ? 'success' : 
                                    ($policy['status'] == 'expired' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($policy['status']); ?>
                            </span>
                        </p>
                        <p><strong>Start Date:</strong><br> <?php echo date('d M Y', strtotime($policy['start_date'])); ?></p>
                        <p><strong>End Date:</strong><br> <?php echo date('d M Y', strtotime($policy['end_date'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Coverage Amount:</strong><br> ₹<?php echo number_format($policy['coverage_amount'], 2); ?></p>
                        <p><strong>Premium:</strong><br> ₹<?php echo number_format($policy['premium'], 2); ?></p>
                        <?php if($policy['type'] == 'health'): ?>
                            <p><strong>Coverage Type:</strong><br> <?php echo ucfirst($policy['type_detail']); ?></p>
                            <p><strong>Pre-existing Conditions:</strong><br> <?php echo $policy['additional_detail'] ? 'Yes' : 'No'; ?></p>
                        <?php elseif($policy['type'] == 'life'): ?>
                            <p><strong>Term (Years):</strong><br> <?php echo $policy['type_detail']; ?></p>
                            <p><strong>Beneficiaries:</strong><br> <?php echo $policy['additional_detail']; ?></p>
                        <?php elseif($policy['type'] == 'general'): ?>
                            <p><strong>Insurance Type:</strong><br> <?php echo ucfirst($policy['type_detail']); ?></p>
                            <p><strong>Property Details:</strong><br> <?php echo $policy['additional_detail']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Policy Documents</h5>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($documents) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Uploaded On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($doc = mysqli_fetch_assoc($documents)): ?>
                                <tr>
                                    <td><?php echo ucwords(str_replace('_', ' ', $doc['document_type'])); ?></td>
                                    <td><?php echo $doc['file_name']; ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($doc['created_at'])); ?></td>
                                    <td>
                                        <a href="uploads/policy_documents/<?php echo $doc['file_name']; ?>" 
                                           class="btn btn-sm btn-info" target="_blank" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="uploads/policy_documents/<?php echo $doc['file_name']; ?>" 
                                           class="btn btn-sm btn-success" download title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No documents uploaded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Client Information Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Client Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong><br> <?php echo $policy['client_name']; ?></p>
                <p><strong>Email:</strong><br> <?php echo $policy['client_email']; ?></p>
                <p><strong>Phone:</strong><br> <?php echo $policy['client_phone']; ?></p>
                <div class="mt-3">
                    <a href="view_client.php?id=<?php echo $policy['client_id']; ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-user"></i> View Client Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.875em;
    padding: 0.5em 0.75em;
}
</style>

<?php include 'includes/footer.php'; ?> 