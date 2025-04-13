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

// Get current year and month
$current_year = date('Y');
$current_month = date('m');

// Get filter parameters
$year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
$month = isset($_GET['month']) ? intval($_GET['month']) : $current_month;
$report_type = isset($_GET['type']) ? $_GET['type'] : 'revenue';

// Get available years for filter
$sql_years = "SELECT DISTINCT YEAR(start_date) as year FROM policies ORDER BY year DESC";
$result_years = mysqli_query($conn, $sql_years);
$years = [];
while($row = mysqli_fetch_assoc($result_years)) {
    $years[] = $row['year'];
}

// Initialize variables
$sql = "";
$result = null;
$error = "";

try {
    // Revenue Report
    if($report_type == 'revenue') {
        $sql = "SELECT 
            p.policy_number,
            c.name as client_name,
            p.type,
            p.premium,
            p.start_date,
            p.end_date,
            p.status
            FROM policies p
            JOIN clients c ON p.client_id = c.id
            WHERE YEAR(p.start_date) = ?";
        if($month != 'all') {
            $sql .= " AND MONTH(p.start_date) = ?";
        }
        $sql .= " ORDER BY p.start_date DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if($month != 'all') {
            mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $year);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Client Report
    elseif($report_type == 'clients') {
        $sql = "SELECT 
            c.*,
            COUNT(p.id) as total_policies,
            SUM(p.premium) as total_premium
            FROM clients c
            LEFT JOIN policies p ON c.id = p.client_id
            WHERE YEAR(c.created_at) = ?";
        if($month != 'all') {
            $sql .= " AND MONTH(c.created_at) = ?";
        }
        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if($month != 'all') {
            mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $year);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Policy Report
    elseif($report_type == 'policies') {
        $sql = "SELECT 
            p.*,
            c.name as client_name,
            c.email as client_email,
            c.phone as client_phone
            FROM policies p
            JOIN clients c ON p.client_id = c.id
            WHERE YEAR(p.start_date) = ?";
        if($month != 'all') {
            $sql .= " AND MONTH(p.start_date) = ?";
        }
        $sql .= " ORDER BY p.start_date DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if($month != 'all') {
            mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $year);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    // Premium Collection Report
    elseif($report_type == 'collections') {
        $sql = "SELECT 
            pp.*,
            p.policy_number,
            c.name as client_name
            FROM premium_payments pp
            JOIN policies p ON pp.policy_id = p.id
            JOIN clients c ON p.client_id = c.id
            WHERE YEAR(pp.payment_date) = ?";
        if($month != 'all') {
            $sql .= " AND MONTH(pp.payment_date) = ?";
        }
        $sql .= " ORDER BY pp.payment_date DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        if($month != 'all') {
            mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $year);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<?php include 'includes/header.php'; ?>

<!-- Move all CSS links to the head section -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

<!-- Content section -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reports</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" id="exportReport">
                <i class="fas fa-download me-1"></i>Export Report
            </button>
            <button type="button" class="btn btn-outline-primary" id="printReport">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="revenue" <?php echo $report_type == 'revenue' ? 'selected' : ''; ?>>Revenue Report</option>
                        <option value="clients" <?php echo $report_type == 'clients' ? 'selected' : ''; ?>>Client Report</option>
                        <option value="policies" <?php echo $report_type == 'policies' ? 'selected' : ''; ?>>Policy Report</option>
                        <option value="collections" <?php echo $report_type == 'collections' ? 'selected' : ''; ?>>Premium Collection Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        <?php foreach($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        <option value="all" <?php echo $month == 'all' ? 'selected' : ''; ?>>All Months</option>
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $month == $i ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <?php if($report_type == 'revenue'): ?>
                                <th>Policy Number</th>
                                <th>Client Name</th>
                                <th>Policy Type</th>
                                <th>Premium</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            <?php elseif($report_type == 'clients'): ?>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Total Policies</th>
                                <th>Total Premium</th>
                                <th>Created At</th>
                            <?php elseif($report_type == 'policies'): ?>
                                <th>Policy Number</th>
                                <th>Client Name</th>
                                <th>Type</th>
                                <th>Premium</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            <?php elseif($report_type == 'collections'): ?>
                                <th>Payment ID</th>
                                <th>Policy Number</th>
                                <th>Client Name</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <?php if($report_type == 'revenue'): ?>
                                    <td><?php echo htmlspecialchars($row['policy_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td>₹<?php echo number_format($row['premium'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                <?php elseif($report_type == 'clients'): ?>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo $row['total_policies']; ?></td>
                                    <td>₹<?php echo number_format($row['total_premium'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <?php elseif($report_type == 'policies'): ?>
                                    <td><?php echo htmlspecialchars($row['policy_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td>₹<?php echo number_format($row['premium'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                <?php elseif($report_type == 'collections'): ?>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['policy_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Move all script tags to the end, just before closing body tag -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery and DataTables to be fully loaded
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('.datatable')) {
            $('.datatable').DataTable().destroy();
        }

        // Initialize DataTable
        var table = $('.datatable').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'excel',
                    className: 'btn btn-success btn-sm',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    title: function() {
                        return 'Insurance Report - ' + $('select[name="type"] option:selected').text();
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-danger btn-sm',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    title: function() {
                        return 'Insurance Report - ' + $('select[name="type"] option:selected').text();
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn btn-primary btn-sm',
                    text: '<i class="fas fa-print"></i> Print',
                    title: function() {
                        return 'Insurance Report - ' + $('select[name="type"] option:selected').text();
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            }
        });

        // Custom button handlers
        $('#exportReport').on('click', function() {
            $('.buttons-excel:first').trigger('click');
        });

        $('#printReport').on('click', function() {
            $('.buttons-print:first').trigger('click');
        });

        // Style the DataTables buttons
        $('.dt-buttons').addClass('mb-3');
        $('.dt-button').addClass('btn btn-sm mx-1');
    }
});
</script>

<style>
/* DataTables Bootstrap 5 Integration Styles */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    color: inherit;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
    margin-left: 0.5rem;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
}

.dataTables_wrapper .dt-buttons {
    margin-bottom: 1rem;
}

.dt-button {
    position: relative;
    display: inline-block;
    box-sizing: border-box;
    margin-right: 0.333em;
    padding: 0.5em 1em;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.88em;
    line-height: 1.6;
    color: inherit;
    white-space: nowrap;
    overflow: hidden;
    background-color: white;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    text-decoration: none;
    outline: none;
    text-overflow: ellipsis;
}

.dt-button:hover:not(.disabled) {
    border: 1px solid #ced4da;
    background-color: #e9ecef;
}

.buttons-excel {
    color: #fff !important;
    background-color: #198754 !important;
    border-color: #198754 !important;
}

.buttons-pdf {
    color: #fff !important;
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.buttons-print {
    color: #fff !important;
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.table.datatable {
    margin-top: 1rem !important;
    width: 100% !important;
}

.dataTables_wrapper .row {
    margin: 0;
    padding: 1rem 0;
}
</style>

<?php include 'includes/footer.php'; ?> 