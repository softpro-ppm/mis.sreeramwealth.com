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
                            <button type="button" class="btn btn-sm btn-danger delete-policy" data-id="<?php echo $policy['id']; ?>" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
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
                <form action="add_policy.php" method="post" enctype="multipart/form-data" id="policyForm">
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
                                        <button type="button" class="btn btn-success add-document">
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
                            <input type="text" id="start_date" class="form-control datepicker" name="start_date" 
                                   placeholder="DD-MM-YYYY" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="text" id="end_date" class="form-control datepicker" name="end_date" 
                                   placeholder="DD-MM-YYYY" required>
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

<!-- Add this right before the closing </body> tag, after including footer.php -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for start date
    const startDatePicker = flatpickr("#start_date", {
        dateFormat: "d-m-Y",
        allowInput: true,
        minDate: 'today',
        parseDate: (datestr, format) => {
            // Parse DD-MM-YYYY format
            if (datestr.match(/^\d{2}-\d{2}-\d{4}$/)) {
                const [day, month, year] = datestr.split("-");
                return new Date(year, month - 1, day);
            }
            return null;
        },
        formatDate: (date, format) => {
            // Format as DD-MM-YYYY
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}-${month}-${year}`;
        },
        onChange: function(selectedDates, dateStr) {
            // Update end date min date when start date changes
            if (selectedDates[0]) {
                endDatePicker.set('minDate', selectedDates[0]);
            }
        }
    });

    // Initialize Flatpickr for end date
    const endDatePicker = flatpickr("#end_date", {
        dateFormat: "d-m-Y",
        allowInput: true,
        minDate: 'today',
        parseDate: (datestr, format) => {
            // Parse DD-MM-YYYY format
            if (datestr.match(/^\d{2}-\d{2}-\d{4}$/)) {
                const [day, month, year] = datestr.split("-");
                return new Date(year, month - 1, day);
            }
            return null;
        },
        formatDate: (date, format) => {
            // Format as DD-MM-YYYY
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}-${month}-${year}`;
        }
    });

    // Initialize form validation
    $("#policyForm").validate({
        rules: {
            policy_number: {
                required: true,
                minlength: 5
            },
            type: "required",
            start_date: {
                required: true,
                dateFormat: true
            },
            end_date: {
                required: true,
                dateFormat: true,
                greaterThan: "#start_date"
            },
            premium: {
                required: true,
                number: true,
                min: 0
            },
            coverage_amount: {
                required: true,
                number: true,
                min: 0
            }
        },
        messages: {
            policy_number: {
                required: "Please enter policy number",
                minlength: "Policy number must be at least 5 characters"
            },
            type: "Please select policy type",
            start_date: {
                required: "Please enter start date",
                dateFormat: "Please enter a valid date in DD-MM-YYYY format"
            },
            end_date: {
                required: "Please enter end date",
                dateFormat: "Please enter a valid date in DD-MM-YYYY format",
                greaterThan: "End date must be after start date"
            },
            premium: {
                required: "Please enter premium amount",
                number: "Please enter a valid number",
                min: "Premium cannot be negative"
            },
            coverage_amount: {
                required: "Please enter coverage amount",
                number: "Please enter a valid number",
                min: "Coverage amount cannot be negative"
            }
        },
        errorElement: 'div',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback');
            element.closest('.mb-3').append(error);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });

    // Add custom date format validation method
    $.validator.addMethod("dateFormat", function(value, element) {
        return this.optional(element) || /^\d{2}-\d{2}-\d{4}$/.test(value);
    });

    // Add custom validation method for end date greater than start date
    $.validator.addMethod("greaterThan", function(value, element, param) {
        var startDate = $(param).val();
        if (!startDate || !value) return true;
        
        function parseDate(dateStr) {
            var parts = dateStr.split("-");
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }
        
        return parseDate(value) > parseDate(startDate);
    });

    // Form validation on submit
    const form = document.querySelector('#policyForm');
    form.addEventListener('submit', function(e) {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        // Validate date formats
        const dateRegex = /^\d{2}-\d{2}-\d{4}$/;
        let isValid = true;

        if (!dateRegex.test(startDate.value)) {
            e.preventDefault();
            startDate.classList.add('is-invalid');
            isValid = false;
        }

        if (!dateRegex.test(endDate.value)) {
            e.preventDefault();
            endDate.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // Validate end date is after start date
        const start = new Date(startDate.value.split('-').reverse().join('-'));
        const end = new Date(endDate.value.split('-').reverse().join('-'));
        
        if (end <= start) {
            e.preventDefault();
            endDate.classList.add('is-invalid');
            const feedback = endDate.nextElementSibling || document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'End date must be after start date';
            if (!endDate.nextElementSibling) {
                endDate.parentNode.appendChild(feedback);
            }
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