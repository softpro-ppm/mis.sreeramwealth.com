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
$year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$report_type = isset($_GET['type']) ? $_GET['type'] : 'revenue';

// Get available years for filter
$sql_years = "SELECT DISTINCT YEAR(start_date) as year FROM policies ORDER BY year DESC";
$result_years = mysqli_query($conn, $sql_years);
$years = [];
while($row = mysqli_fetch_assoc($result_years)) {
    $years[] = $row['year'];
}

// Revenue Report
if($report_type == 'revenue') {
    $sql = "SELECT 
        p.policy_number,
        c.name as client_name,
        p.policy_type,
        p.premium,
        p.start_date,
        p.end_date,
        p.status
        FROM policies p
        JOIN clients c ON p.client_id = c.id
        WHERE YEAR(p.start_date) = $year";
    if($month != 'all') {
        $sql .= " AND MONTH(p.start_date) = $month";
    }
    $sql .= " ORDER BY p.start_date DESC";
}

// Client Report
elseif($report_type == 'clients') {
    $sql = "SELECT 
        c.*,
        COUNT(p.id) as total_policies,
        SUM(p.premium) as total_premium
        FROM clients c
        LEFT JOIN policies p ON c.id = p.client_id
        WHERE YEAR(c.created_at) = $year";
    if($month != 'all') {
        $sql .= " AND MONTH(c.created_at) = $month";
    }
    $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
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
        WHERE YEAR(p.start_date) = $year";
    if($month != 'all') {
        $sql .= " AND MONTH(p.start_date) = $month";
    }
    $sql .= " ORDER BY p.start_date DESC";
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
        WHERE YEAR(pp.payment_date) = $year";
    if($month != 'all') {
        $sql .= " AND MONTH(pp.payment_date) = $month";
    }
    $sql .= " ORDER BY pp.payment_date DESC";
}

$result = mysqli_query($conn, $sql);
?>

<?php include 'includes/header.php'; ?>

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
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <?php if($report_type == 'revenue'): ?>
                                <td><?php echo htmlspecialchars($row['policy_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['policy_type']); ?></td>
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
                                <td><?php echo htmlspecialchars($row['policy_type']); ?></td>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('.datatable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    // Export Report
    $('#exportReport').click(function() {
        $('.datatable').DataTable().button('excel').trigger();
    });

    // Print Report
    $('#printReport').click(function() {
        $('.datatable').DataTable().button('print').trigger();
    });
});
</script> 