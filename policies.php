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

// Handle policy deletion
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $policy_id = $_GET['delete'];
    $sql = "DELETE FROM policies WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $policy_id);
        if(mysqli_stmt_execute($stmt)) {
            header("location: policies.php?status=deleted");
            exit();
        }
    }
}

// Get all policies with client information
$sql = "SELECT p.*, c.name as client_name 
        FROM policies p 
        JOIN clients c ON p.client_id = c.id 
        ORDER BY p.created_at DESC";
$policies = mysqli_query($conn, $sql);
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Insurance Policies</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
        <i class="fas fa-plus"></i> Add New Policy
    </button>
</div>

<?php if(isset($_GET['status'])): ?>
    <div class="alert alert-<?php echo $_GET['status'] == 'deleted' ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php 
        if($_GET['status'] == 'deleted') {
            echo "Policy has been deleted successfully.";
        } elseif($_GET['status'] == 'added') {
            echo "New policy has been added successfully.";
        } elseif($_GET['status'] == 'updated') {
            echo "Policy has been updated successfully.";
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
                        <th>Policy Number</th>
                        <th>Client Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Premium</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($policy = mysqli_fetch_assoc($policies)): ?>
                    <tr>
                        <td><?php echo $policy['policy_number']; ?></td>
                        <td><?php echo $policy['client_name']; ?></td>
                        <td><?php echo ucfirst($policy['type']); ?></td>
                        <td><?php echo date('d M Y', strtotime($policy['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($policy['end_date'])); ?></td>
                        <td>₹<?php echo number_format($policy['premium'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $policy['status'] == 'active' ? 'success' : 
                                    ($policy['status'] == 'expired' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($policy['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="view_policy.php?id=<?php echo $policy['id']; ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_policy.php?id=<?php echo $policy['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="policies.php?delete=<?php echo $policy['id']; ?>" class="btn btn-sm btn-danger delete-btn" title="Delete">
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

<!-- Add Policy Modal -->
<div class="modal fade" id="addPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="add_policy.php" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Policy Type</label>
                            <select class="form-select" name="type" id="policyType" required>
                                <option value="">Select Type</option>
                                <option value="health">Health Insurance</option>
                                <option value="life">Life Insurance</option>
                                <option value="general">General Insurance</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php
                                $clients = mysqli_query($conn, "SELECT id, name FROM clients ORDER BY name");
                                while($client = mysqli_fetch_assoc($clients)) {
                                    echo "<option value='{$client['id']}'>{$client['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control datepicker" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control datepicker" name="end_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Coverage Amount</label>
                            <input type="number" class="form-control" name="coverage_amount" id="coverageAmount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Premium</label>
                            <input type="number" class="form-control" name="premium" id="premium" required>
                        </div>
                    </div>
                    
                    <!-- Health Insurance Fields -->
                    <div id="healthFields" class="policy-type-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Coverage Type</label>
                                <select class="form-select" name="health[coverage_type]">
                                    <option value="individual">Individual</option>
                                    <option value="family">Family</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pre-existing Conditions</label>
                                <select class="form-select" name="health[pre_existing_conditions]">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Life Insurance Fields -->
                    <div id="lifeFields" class="policy-type-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Term (Years)</label>
                                <input type="number" class="form-control" name="life[term_years]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Beneficiaries</label>
                                <textarea class="form-control" name="life[beneficiaries]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- General Insurance Fields -->
                    <div id="generalFields" class="policy-type-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Insurance Type</label>
                                <select class="form-select" name="general[insurance_type]">
                                    <option value="vehicle">Vehicle</option>
                                    <option value="property">Property</option>
                                    <option value="travel">Travel</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Details</label>
                                <textarea class="form-control" name="general[property_details]" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 