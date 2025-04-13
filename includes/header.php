<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Flatpickr for better date input -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr for all date inputs
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(function(input) {
                // Create a text input to replace the date input
                const textInput = document.createElement('input');
                textInput.type = 'text';
                textInput.className = input.className;
                textInput.name = input.name;
                textInput.id = input.id;
                textInput.required = input.required;
                textInput.placeholder = 'DD-MM-YYYY';
                
                // Replace the date input with the text input
                input.parentNode.replaceChild(textInput, input);

                // Initialize Flatpickr
                flatpickr(textInput, {
                    dateFormat: "d-m-Y",
                    allowInput: true,
                    altInput: true,
                    altFormat: "d-m-Y",
                    defaultHour: 12,
                    maxDate: input.hasAttribute('max') ? 'today' : null,
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
                        // Trigger change event for validation
                        textInput.dispatchEvent(new Event('change'));
                    }
                });

                // Set initial value if exists
                if (input.value) {
                    const date = new Date(input.value);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    textInput._flatpickr.setDate(`${day}-${month}-${year}`);
                }
            });
        });
    </script>
    
    <style>
    body {
        background-color: #f8f9fa;
    }
    .navbar {
        box-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    .navbar-brand img {
        height: 40px;
    }
    /* Flatpickr custom styles */
    .flatpickr-calendar {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 3px 13px rgba(0,0,0,0.08);
    }
    .flatpickr-day.selected {
        background: #0d6efd;
        border-color: #0d6efd;
    }
    .flatpickr-day.today {
        border-color: #0d6efd;
    }
    .flatpickr-day:hover {
        background: #e9ecef;
    }
    </style>
</head>
<body>
    <?php require_once 'utils.php'; ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                Insurance Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php"><i class="fas fa-users"></i> Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="policies.php"><i class="fas fa-file-contract"></i> Policies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <?php if(isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['success_msg']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['error_msg']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>