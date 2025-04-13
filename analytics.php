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

// Revenue Analytics
$sql_revenue = "SELECT 
    MONTH(start_date) as month,
    SUM(premium) as total_premium,
    COUNT(*) as policy_count
    FROM policies 
    WHERE YEAR(start_date) = $current_year
    GROUP BY MONTH(start_date)";

$result_revenue = mysqli_query($conn, $sql_revenue);
$monthly_revenue = array_fill(0, 12, 0);
$monthly_policies = array_fill(0, 12, 0);
while($row = mysqli_fetch_assoc($result_revenue)) {
    $monthly_revenue[$row['month'] - 1] = $row['total_premium'];
    $monthly_policies[$row['month'] - 1] = $row['policy_count'];
}

// Policy Renewal Rate
$sql_renewals = "SELECT 
    COUNT(*) as total_policies,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as renewed_policies
    FROM policies 
    WHERE end_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND CURDATE()";

$result_renewals = mysqli_query($conn, $sql_renewals);
$renewal_data = mysqli_fetch_assoc($result_renewals);
$renewal_rate = $renewal_data['total_policies'] > 0 ? 
    ($renewal_data['renewed_policies'] / $renewal_data['total_policies']) * 100 : 0;

// Client Acquisition
$sql_clients = "SELECT 
    MONTH(created_at) as month,
    COUNT(*) as new_clients
    FROM clients 
    WHERE YEAR(created_at) = $current_year
    GROUP BY MONTH(created_at)";

$result_clients = mysqli_query($conn, $sql_clients);
$monthly_clients = array_fill(0, 12, 0);
while($row = mysqli_fetch_assoc($result_clients)) {
    $monthly_clients[$row['month'] - 1] = $row['new_clients'];
}

// Premium Collection Efficiency
$sql_collections = "SELECT 
    MONTH(payment_date) as month,
    SUM(amount) as collected_amount,
    COUNT(*) as payment_count
    FROM premium_payments 
    WHERE YEAR(payment_date) = $current_year AND status = 'completed'
    GROUP BY MONTH(payment_date)";

$result_collections = mysqli_query($conn, $sql_collections);
$monthly_collections = array_fill(0, 12, 0);
$monthly_payments = array_fill(0, 12, 0);
while($row = mysqli_fetch_assoc($result_collections)) {
    $monthly_collections[$row['month'] - 1] = $row['collected_amount'];
    $monthly_payments[$row['month'] - 1] = $row['payment_count'];
}

// Risk Assessment
$sql_risk = "SELECT 
    type,
    COUNT(*) as total,
    AVG(coverage_amount) as avg_coverage,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM policies 
    GROUP BY type";

$result_risk = mysqli_query($conn, $sql_risk);
$risk_data = [];
while($row = mysqli_fetch_assoc($result_risk)) {
    $risk_data[] = $row;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Advanced Analytics Dashboard</h2>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" id="exportData">
            <i class="fas fa-download me-1"></i>Export Data
        </button>
        <button type="button" class="btn btn-outline-primary" id="refreshData">
            <i class="fas fa-sync-alt me-1"></i>Refresh
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card primary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Revenue (YTD)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₹<?php echo number_format(array_sum($monthly_revenue), 2); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
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
                            Policy Renewal Rate</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($renewal_rate, 1); ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-sync fa-2x text-gray-300"></i>
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
                            New Clients (YTD)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo array_sum($monthly_clients); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
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
                            Collection Efficiency</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            $total_due = array_sum($monthly_revenue);
                            $total_collected = array_sum($monthly_collections);
                            $efficiency = $total_due > 0 ? ($total_collected / $total_due) * 100 : 0;
                            echo number_format($efficiency, 1); ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Revenue & Policy Trends</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div id="revenueChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Risk Assessment</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div id="riskChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Client Acquisition</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div id="clientsChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Premium Collections</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div id="collectionsChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Revenue & Policy Trends Chart
var revenueOptions = {
    series: [{
        name: 'Revenue',
        data: <?php echo json_encode($monthly_revenue); ?>
    }, {
        name: 'Policies',
        data: <?php echo json_encode($monthly_policies); ?>
    }],
    chart: {
        type: 'area',
        height: 350,
        toolbar: {
            show: false
        }
    },
    colors: ['#4e73df', '#1cc88a'],
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
    yaxis: [{
        title: {
            text: 'Revenue (₹)'
        }
    }, {
        opposite: true,
        title: {
            text: 'Policies'
        }
    }],
    tooltip: {
        y: [{
            formatter: function (val) {
                return "₹" + val.toLocaleString('en-IN')
            }
        }, {
            formatter: function (val) {
                return val
            }
        }]
    }
};

var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
revenueChart.render();

// Risk Assessment Chart
var riskOptions = {
    series: <?php echo json_encode(array_column($risk_data, 'total')); ?>,
    chart: {
        type: 'donut',
        height: 350
    },
    labels: <?php echo json_encode(array_column($risk_data, 'type')); ?>,
    colors: ['#4e73df', '#1cc88a', '#36b9cc'],
    legend: {
        position: 'bottom'
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Policies',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                        }
                    }
                }
            }
        }
    }
};

var riskChart = new ApexCharts(document.querySelector("#riskChart"), riskOptions);
riskChart.render();

// Client Acquisition Chart
var clientsOptions = {
    series: [{
        name: 'New Clients',
        data: <?php echo json_encode($monthly_clients); ?>
    }],
    chart: {
        type: 'bar',
        height: 350,
        toolbar: {
            show: false
        }
    },
    colors: ['#4e73df'],
    plotOptions: {
        bar: {
            borderRadius: 4,
            horizontal: false,
        }
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    }
};

var clientsChart = new ApexCharts(document.querySelector("#clientsChart"), clientsOptions);
clientsChart.render();

// Premium Collections Chart
var collectionsOptions = {
    series: [{
        name: 'Collections',
        data: <?php echo json_encode($monthly_collections); ?>
    }],
    chart: {
        type: 'line',
        height: 350,
        toolbar: {
            show: false
        }
    },
    colors: ['#4e73df'],
    stroke: {
        curve: 'smooth',
        width: 2
    },
    markers: {
        size: 5
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return "₹" + val.toLocaleString('en-IN')
            }
        }
    }
};

var collectionsChart = new ApexCharts(document.querySelector("#collectionsChart"), collectionsOptions);
collectionsChart.render();

// Export Data Functionality
document.getElementById('exportData').addEventListener('click', function() {
    // Create a CSV string
    let csv = 'Month,Revenue,Policies,New Clients,Collections\n';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    for(let i = 0; i < 12; i++) {
        csv += `${months[i]},${monthly_revenue[i]},${monthly_policies[i]},${monthly_clients[i]},${monthly_collections[i]}\n`;
    }
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'analytics_data.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
});

// Refresh Data Functionality
document.getElementById('refreshData').addEventListener('click', function() {
    location.reload();
});
</script> 