<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SREERAMWEALTH</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery Validation -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>

    <!-- Flatpickr for better date input -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- ApexCharts -->
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>

    <!-- Custom styles -->
    <link href="assets/css/style.css" rel="stylesheet">

    <script>
        // Global date picker configuration
        const defaultDateConfig = {
            dateFormat: "d-m-Y",
            allowInput: true,
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
        };

        // Initialize all date inputs with Flatpickr
        document.addEventListener('DOMContentLoaded', function() {
            // Convert all date inputs to text and initialize Flatpickr
            document.querySelectorAll('input[type="date"], input.datepicker').forEach(function(input) {
                // Create a text input to replace the date input if it's a date type
                if (input.type === 'date') {
                    const textInput = document.createElement('input');
                    textInput.type = 'text';
                    textInput.className = input.className + ' datepicker';
                    textInput.name = input.name;
                    textInput.id = input.id || input.name;
                    textInput.required = input.required;
                    textInput.placeholder = 'DD-MM-YYYY';
                    
                    // Replace the date input with the text input
                    input.parentNode.replaceChild(textInput, input);
                    input = textInput;
                }

                // Get specific configurations based on input name
                let config = { ...defaultDateConfig };
                
                // Date of birth fields
                if (input.name.includes('date_of_birth')) {
                    config.maxDate = 'today';
                }
                
                // Policy start date fields
                if (input.name === 'start_date') {
                    config.minDate = 'today';
                    config.onChange = function(selectedDates) {
                        // Update end date min date when start date changes
                        const endDateInput = document.querySelector('input[name="end_date"]');
                        if (endDateInput && endDateInput._flatpickr) {
                            endDateInput._flatpickr.set('minDate', selectedDates[0] || 'today');
                        }
                    };
                }
                
                // Policy end date fields
                if (input.name === 'end_date') {
                    config.minDate = 'today';
                }

                // Initialize Flatpickr with config
                flatpickr(input, config);

                // Add validation for DD-MM-YYYY format
                input.addEventListener('change', function() {
                    const dateRegex = /^\d{2}-\d{2}-\d{4}$/;
                    if (this.value && !dateRegex.test(this.value)) {
                        this.classList.add('is-invalid');
                        let feedback = this.nextElementSibling;
                        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                            feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            this.parentNode.appendChild(feedback);
                        }
                        feedback.textContent = 'Please enter a valid date in DD-MM-YYYY format';
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Add custom date format validation method for jQuery Validate
            if ($.validator) {
                $.validator.addMethod("dateFormat", function(value, element) {
                    return this.optional(element) || /^\d{2}-\d{2}-\d{4}$/.test(value);
                }, "Please enter a valid date in DD-MM-YYYY format");

                // Add custom validation method for end date greater than start date
                $.validator.addMethod("greaterThan", function(value, element, param) {
                    var startDate = $(param).val();
                    if (!startDate || !value) return true;
                    
                    function parseDate(dateStr) {
                        var parts = dateStr.split("-");
                        return new Date(parts[2], parts[1] - 1, parts[0]);
                    }
                    
                    return parseDate(value) > parseDate(startDate);
                }, "End date must be after start date");
            }
        });
    </script>
    
    <style>
    body {
        background-color: #f8f9fa;
    }
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    .navbar {
        box-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    .navbar-brand img {
        height: 40px;
    }
    /* Dropdown text visibility fix */
    .form-select {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 2.5rem;
    }
    .form-select option {
        white-space: normal;
        padding: 0.5rem;
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
                SREERAMWEALTH
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