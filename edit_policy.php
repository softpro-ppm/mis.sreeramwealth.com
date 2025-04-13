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
$success_msg = $error_msg = "";

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $coverage_amount = floatval($_POST['coverage_amount']);
    $premium = floatval($_POST['premium']);
    
    // Validate start date
    if(empty(trim($_POST["start_date"]))){
        $start_date_err = "Please enter start date.";
    } else{
        $start_date = date('Y-m-d', strtotime(trim($_POST["start_date"])));
    }
    
    // Validate end date
    if(empty(trim($_POST["end_date"]))){
        $end_date_err = "Please enter end date.";
    } else{
        $end_date = date('Y-m-d', strtotime(trim($_POST["end_date"])));
    }

    // Update policy details
    $sql = "UPDATE policies SET 
            status = ?,
            coverage_amount = ?,
            premium = ?,
            start_date = ?,
            end_date = ?,
            updated_at = NOW()
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    $param_start_date = date('Y-m-d', strtotime($start_date));
    $param_end_date = date('Y-m-d', strtotime($end_date));
    mysqli_stmt_bind_param($stmt, "sddssi", $status, $coverage_amount, $premium, $param_start_date, $param_end_date, $policy_id);
    
    if(mysqli_stmt_execute($stmt)) {
        // Update type-specific details
        $policy_type = mysqli_real_escape_string($conn, $_POST['type']);
        
        if($policy_type == 'health') {
            $coverage_type = mysqli_real_escape_string($conn, $_POST['coverage_type']);
            $pre_existing = isset($_POST['pre_existing_conditions']) ? 1 : 0;
            
            $sql = "UPDATE health_insurance SET 
                    coverage_type = ?,
                    pre_existing_conditions = ?
                    WHERE policy_id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sii", $coverage_type, $pre_existing, $policy_id);
            mysqli_stmt_execute($stmt);
        }
        elseif($policy_type == 'life') {
            $term_years = intval($_POST['term_years']);
            $beneficiaries = mysqli_real_escape_string($conn, $_POST['beneficiaries']);
            
            $sql = "UPDATE life_insurance SET 
                    term_years = ?,
                    beneficiaries = ?
                    WHERE policy_id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $term_years, $beneficiaries, $policy_id);
            mysqli_stmt_execute($stmt);
        }
        elseif($policy_type == 'general') {
            $insurance_type = mysqli_real_escape_string($conn, $_POST['insurance_type']);
            $property_details = mysqli_real_escape_string($conn, $_POST['property_details']);
            
            $sql = "UPDATE general_insurance SET 
                    insurance_type = ?,
                    property_details = ?
                    WHERE policy_id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $insurance_type, $property_details, $policy_id);
            mysqli_stmt_execute($stmt);
        }

        // Handle document uploads
        if(isset($_FILES['document_file'])) {
            $document_types = $_POST['document_type'];
            $upload_dir = "uploads/policy_documents/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach($_FILES['document_file']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['document_file']['error'][$key] == 0) {
                    $file_name = $_FILES['document_file']['name'][$key];
                    $file_size = $_FILES['document_file']['size'][$key];
                    $file_tmp = $_FILES['document_file']['tmp_name'][$key];
                    $file_type = $_FILES['document_file']['type'][$key];
                    
                    // Generate unique filename
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $unique_file = uniqid() . '_' . $policy_id . '.' . $file_ext;
                    
                    // Check file size (5MB max)
                    if($file_size > 5242880) {
                        $error_msg = "File size must be less than 5MB";
                        continue;
                    }
                    
                    // Check file type
                    $allowed = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
                    if(!in_array($file_ext, $allowed)) {
                        $error_msg = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, PNG";
                        continue;
                    }
                    
                    if(move_uploaded_file($file_tmp, $upload_dir . $unique_file)) {
                        // Save document info to database
                        $doc_type = mysqli_real_escape_string($conn, $document_types[$key]);
                        $sql = "INSERT INTO documents (policy_id, document_type, file_name, created_at) 
                                VALUES (?, ?, ?, NOW())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iss", $policy_id, $doc_type, $unique_file);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
        
        $success_msg = "Policy updated successfully!";
    } else {
        $error_msg = "Error updating policy. Please try again.";
    }
}

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

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Policy</h2>
        <a href="view_policy.php?id=<?php echo $policy_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to View
        </a>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $policy_id; ?>" method="post" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <!-- Policy Details Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Policy Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Policy Number</label>
                                <input type="text" class="form-control" value="<?php echo $policy['policy_number']; ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type" required>
                                    <option value="health" <?php echo $policy['type'] == 'health' ? 'selected' : ''; ?>>Health Insurance</option>
                                    <option value="life" <?php echo $policy['type'] == 'life' ? 'selected' : ''; ?>>Life Insurance</option>
                                    <option value="general" <?php echo $policy['type'] == 'general' ? 'selected' : ''; ?>>General Insurance</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending" <?php echo $policy['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo $policy['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="expired" <?php echo $policy['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Coverage Amount (₹)</label>
                                <input type="number" step="0.01" class="form-control" name="coverage_amount" 
                                       value="<?php echo $policy['coverage_amount']; ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Premium (₹)</label>
                                <input type="number" step="0.01" class="form-control" name="premium" 
                                       value="<?php echo $policy['premium']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="text" class="form-control" name="start_date" 
                                       value="<?php echo formatDateDMY($policy['start_date']); ?>" 
                                       placeholder="DD-MM-YYYY" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="text" class="form-control" name="end_date" 
                                       value="<?php echo formatDateDMY($policy['end_date']); ?>" 
                                       placeholder="DD-MM-YYYY" required>
                            </div>
                        </div>

                        <?php if($policy['type'] == 'health'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Coverage Type</label>
                                    <select class="form-select" name="coverage_type" required>
                                        <option value="individual" <?php echo $policy['type_detail'] == 'individual' ? 'selected' : ''; ?>>Individual</option>
                                        <option value="family" <?php echo $policy['type_detail'] == 'family' ? 'selected' : ''; ?>>Family</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pre-existing Conditions</label>
                                    <select class="form-select" name="pre_existing_conditions">
                                        <option value="0" <?php echo !$policy['additional_detail'] ? 'selected' : ''; ?>>No</option>
                                        <option value="1" <?php echo $policy['additional_detail'] ? 'selected' : ''; ?>>Yes</option>
                                    </select>
                                </div>
                            </div>
                        <?php elseif($policy['type'] == 'life'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Term (Years)</label>
                                    <input type="number" class="form-control" name="term_years" 
                                           value="<?php echo $policy['type_detail']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Beneficiaries</label>
                                    <textarea class="form-control" name="beneficiaries" rows="3" required><?php echo $policy['additional_detail']; ?></textarea>
                                </div>
                            </div>
                        <?php elseif($policy['type'] == 'general'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Insurance Type</label>
                                    <select class="form-select" name="insurance_type" required>
                                        <option value="vehicle" <?php echo $policy['type_detail'] == 'vehicle' ? 'selected' : ''; ?>>Vehicle</option>
                                        <option value="property" <?php echo $policy['type_detail'] == 'property' ? 'selected' : ''; ?>>Property</option>
                                        <option value="travel" <?php echo $policy['type_detail'] == 'travel' ? 'selected' : ''; ?>>Travel</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Property Details</label>
                                    <textarea class="form-control" name="property_details" rows="3" required><?php echo $policy['additional_detail']; ?></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documents Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Policy Documents</h5>
                    </div>
                    <div class="card-body">
                        <div class="document-upload-container">
                            <div class="row document-row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Document Type</label>
                                    <select class="form-select" name="document_type[]">
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
                                    <input type="file" class="form-control" name="document_file[]" 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <div class="form-text">Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, PNG</div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-success add-document">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php if(mysqli_num_rows($documents) > 0): ?>
                            <hr>
                            <h6>Existing Documents</h6>
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
                                                <button type="button" class="btn btn-sm btn-danger delete-document" 
                                                        data-id="<?php echo $doc['id']; ?>" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
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

                <div class="card mt-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dynamic document upload fields
    $(document).on('click', '.add-document', function() {
        var documentRow = `
            <div class="row document-row mb-3">
                <div class="col-md-5">
                    <label class="form-label">Document Type</label>
                    <select class="form-select" name="document_type[]">
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
                    <input type="file" class="form-control" name="document_file[]" 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="form-text">Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, PNG</div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger remove-document">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        `;
        
        $(this).closest('.document-upload-container').append(documentRow);
    });

    // Handle remove button click
    $(document).on('click', '.remove-document', function() {
        $(this).closest('.document-row').remove();
    });

    // Handle document deletion
    $('.delete-document').click(function() {
        if(confirm('Are you sure you want to delete this document?')) {
            var docId = $(this).data('id');
            $.post('delete_document.php', {id: docId}, function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Error deleting document. Please try again.');
                }
            }, 'json');
        }
    });

    // File size validation
    $(document).on('change', 'input[type="file"]', function() {
        var maxSize = 5 * 1024 * 1024; // 5MB
        var allowedTypes = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'];
        var fileName = this.value.toLowerCase();
        var validFile = false;
        
        // Check file extension
        for(var i = 0; i < allowedTypes.length; i++) {
            if(fileName.endsWith(allowedTypes[i])) {
                validFile = true;
                break;
            }
        }
        
        if (!validFile) {
            alert('Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG files only.');
            $(this).val('');
            return;
        }
        
        if (this.files[0] && this.files[0].size > maxSize) {
            alert('File size must be less than 5MB');
            $(this).val('');
            return;
        }

        // Show selected filename
        if (this.files[0]) {
            $(this).next('.form-text').html('Selected file: ' + this.files[0].name);
        } else {
            $(this).next('.form-text').html('Max file size: 5MB. Allowed types: PDF, DOC, DOCX, JPG, PNG');
        }
    });
});

function formatDateDMY($date) {
    return date('d-m-Y', strtotime($date));
}
</script>

<?php include 'includes/footer.php'; ?>