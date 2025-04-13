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
                <form action="add_policy.php" method="post" enctype="multipart/form-data">
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

                    <!-- Document Upload Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">Policy Documents</h6>
                            <div class="document-upload-container border rounded p-3">
                                <div class="row document-row mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label">Document Type</label>
                                        <select class="form-select" name="document_type[]" required>
                                            <option value="">Select Document Type</option>
                                            <option value="policy">Policy Document</option>
                                            <option value="id_proof">ID Proof</option>
                                            <option value="address_proof">Address Proof</option>
                                            <option value="medical">Medical Report</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Upload File</label>
                                        <input type="file" class="form-control" name="document_file[]" required 
                                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <div class="form-text">Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, PNG</div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-success add-document mb-0">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
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

<!-- Add this script before the closing body tag -->
<script>
$(document).ready(function() {
    // Handle dynamic document upload fields
    $('.add-document').click(function() {
        var newRow = $('.document-row:first').clone();
        // Clear previous values
        newRow.find('input[type="file"]').val('');
        newRow.find('select').val('');
        
        // Replace the add button with remove button in the new row
        var addButton = newRow.find('.add-document');
        var removeButton = $('<button type="button" class="btn btn-danger remove-document"><i class="fas fa-minus"></i></button>');
        addButton.replaceWith(removeButton);
        
        // Append the new row to the container
        $('.document-upload-container').append(newRow);
    });

    // Handle remove button click
    $(document).on('click', '.remove-document', function() {
        // Don't remove if it's the last row
        if ($('.document-row').length > 1) {
            $(this).closest('.document-row').remove();
        }
    });

    // File size and type validation
    $(document).on('change', 'input[type="file"]', function() {
        var maxSize = 5 * 1024 * 1024; // 5MB
        var allowedTypes = ['application/pdf', 'application/msword', 
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'image/jpeg', 'image/png'];
        
        if (this.files[0]) {
            // Check file size
            if (this.files[0].size > maxSize) {
                alert('File size exceeds 5MB limit');
                $(this).val('');
                return;
            }
            
            // Check file type
            if (!allowedTypes.includes(this.files[0].type)) {
                alert('Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG files only.');
                $(this).val('');
                return;
            }
        }
    });

    // Show selected filename
    $(document).on('change', 'input[type="file"]', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.form-text').html('Selected file: ' + fileName);
        } else {
            $(this).next('.form-text').html('Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, PNG');
        }
    });
});
</script>

<style>
.document-upload-container {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.document-row {
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background-color: white;
}

.document-row:last-child {
    margin-bottom: 0;
}

.add-document, .remove-document {
    width: 38px;
    height: 38px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.add-document {
    background-color: #198754;
    border-color: #198754;
}

.remove-document {
    background-color: #dc3545;
    border-color: #dc3545;
}

.add-document:hover {
    background-color: #157347;
    border-color: #157347;
}

.remove-document:hover {
    background-color: #bb2d3b;
    border-color: #bb2d3b;
}

.form-text {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.document-row .col-md-2 {
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
}

input[type="file"] {
    padding: 0.375rem;
    cursor: pointer;
}

.document-type-select {
    margin-bottom: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?> 