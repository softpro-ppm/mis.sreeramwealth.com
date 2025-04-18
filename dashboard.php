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

// Get total policies count
$sql_policies = "SELECT COUNT(*) as total FROM policies";
$result_policies = mysqli_query($conn, $sql_policies);
$policies_count = mysqli_fetch_assoc($result_policies)['total'];

// Get total clients count
$sql_clients = "SELECT COUNT(*) as total FROM clients";
$result_clients = mysqli_query($conn, $sql_clients);
$clients_count = mysqli_fetch_assoc($result_clients)['total'];

// Get expiring policies (within next 30 days)
$sql_expiring = "SELECT COUNT(*) as total FROM policies WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$result_expiring = mysqli_query($conn, $sql_expiring);
$expiring_count = mysqli_fetch_assoc($result_expiring)['total'];

// Get policy type distribution
$sql_policy_types = "SELECT type, COUNT(*) as count FROM policies GROUP BY type";
$result_policy_types = mysqli_query($conn, $sql_policy_types);
$policy_types = [];
$policy_counts = [];
while($row = mysqli_fetch_assoc($result_policy_types)) {
    $policy_types[] = ucfirst($row['type']);
    $policy_counts[] = $row['count'];
}

// Get recent policies
$sql_recent = "SELECT p.*, c.name as client_name FROM policies p 
               JOIN clients c ON p.client_id = c.id 
               ORDER BY p.start_date DESC LIMIT 5";
$recent_policies = mysqli_query($conn, $sql_recent);

// Get Monthly Premiums Data
$sql_monthly_premiums = "SELECT 
    MONTH(start_date) as month,
    SUM(premium) as total_premium
    FROM policies 
    WHERE YEAR(start_date) = YEAR(CURRENT_DATE())
    GROUP BY MONTH(start_date)
    ORDER BY month";

$result_monthly = mysqli_query($conn, $sql_monthly_premiums);
$monthly_data = array_fill(0, 12, 0); // Initialize with zeros
while($row = mysqli_fetch_assoc($result_monthly)) {
    $monthly_data[$row['month']-1] = floatval($row['total_premium']);
}
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card primary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Policies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $policies_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card success h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Clients</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $clients_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card warning h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Expiring Policies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expiring_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card info h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Policies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            $sql_active = "SELECT COUNT(*) as total FROM policies WHERE status = 'active'";
                            $result_active = mysqli_query($conn, $sql_active);
                            echo mysqli_fetch_assoc($result_active)['total'];
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Monthly Premiums</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <div id="monthlyPremiumsChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Policy Types Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <div id="policyTypesChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Policies</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped datatable" id="recentPoliciesTable">
                        <thead>
                            <tr>
                                <th># <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Policy Number <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Client Name <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Type <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Start Date <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>End Date <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Premium <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                                <th>Status <button class="btn btn-sm btn-link p-0 sort-btn"><i class="fas fa-sort"></i></button></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; while($policy = mysqli_fetch_assoc($recent_policies)): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
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
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.sort-btn {
    font-size: 0.8rem;
    color: #6c757d;
    transition: color 0.2s;
}

.sort-btn:hover {
    color: #0d6efd;
}

.sorting_asc .sort-btn i,
.sorting_desc .sort-btn i {
    color: #0d6efd;
}

.sorting_asc .sort-btn i:before {
    content: "\f0de";
}

.sorting_desc .sort-btn i:before {
    content: "\f0dd";
}
</style>

<script>
// Initialize DataTable for Recent Policies
$(document).ready(function() {
    $('#recentPoliciesTable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[4, 'desc']], // Sort by Start Date by default
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search policies...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            {
                targets: '_all',
                orderable: true
            }
        ]
    });
});

// Monthly Premiums Chart
var monthlyOptions = {
    series: [{
        name: 'Premium Amount',
        data: <?php echo json_encode($monthly_data); ?>
    }],
    chart: {
        height: 350,
        type: 'area',
        toolbar: {
            show: false
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth',
        width: 2
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    },
    colors: ['#4e73df'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3
        }
    },
    tooltip: {
        y: {
            formatter: function(value) {
                return '₹' + value.toLocaleString('en-IN');
            }
        }
    }
};

var monthlyChart = new ApexCharts(document.querySelector("#monthlyPremiumsChart"), monthlyOptions);
monthlyChart.render();

// Policy Types Chart
var policyTypesOptions = {
    series: <?php echo json_encode($policy_counts); ?>,
    chart: {
        type: 'donut',
        height: 350
    },
    labels: <?php echo json_encode($policy_types); ?>,
    colors: ['#4e73df', '#1cc88a', '#36b9cc'],
    legend: {
        position: 'bottom'
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%'
            }
        }
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
};

var policyTypesChart = new ApexCharts(document.querySelector("#policyTypesChart"), policyTypesOptions);
policyTypesChart.render();
</script> 