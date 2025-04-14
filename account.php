<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-primary sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php">
                            <i class="fas fa-user-circle me-2"></i>
                            Profile Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="account.php">
                            <i class="fas fa-cog me-2"></i>
                            Account Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="security.php">
                            <i class="fas fa-shield-alt me-2"></i>
                            Security
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="notifications.php">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Account Settings</h1>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form id="accountSettingsForm" method="post" action="update_account.php">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword">
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    min-height: calc(100vh - 60px);
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    padding: 1rem;
}

.sidebar .nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link i {
    width: 20px;
    text-align: center;
}
</style>

<script>
$(document).ready(function() {
    // Form validation
    $('#accountSettingsForm').validate({
        rules: {
            username: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                email: true
            },
            currentPassword: {
                required: true,
                minlength: 6
            },
            newPassword: {
                minlength: 6
            },
            confirmPassword: {
                equalTo: "#newPassword"
            }
        },
        messages: {
            username: {
                required: "Please enter your username",
                minlength: "Username must be at least 3 characters"
            },
            email: {
                required: "Please enter your email",
                email: "Please enter a valid email address"
            },
            currentPassword: {
                required: "Please enter your current password",
                minlength: "Password must be at least 6 characters"
            },
            newPassword: {
                minlength: "Password must be at least 6 characters"
            },
            confirmPassword: {
                equalTo: "Passwords do not match"
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 