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

// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
$username = $_SESSION["username"];
$user_id = $_SESSION["id"];

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have at least 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm the password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
        
    // Check input errors before updating the database
    if(empty($new_password_err) && empty($confirm_password_err)){
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Password updated successfully. Destroy the session, and redirect to login page
                session_destroy();
                header("location: login.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <div class="avatar-circle mb-3">
                            <span class="initials"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($username); ?></h4>
                        <p class="text-muted mb-0">User ID: <?php echo $user_id; ?></p>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Account Information</h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user me-2 text-primary"></i>Username</span>
                            <span class="text-muted"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-id-card me-2 text-primary"></i>User ID</span>
                            <span class="text-muted"><?php echo $user_id; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar me-2 text-primary"></i>Member Since</span>
                            <span class="text-muted"><?php echo date('M d, Y'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Security Settings</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        For security reasons, you will be logged out after changing your password.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Recent Activity</h5>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Last Login</h6>
                                <p class="text-muted mb-0"><?php echo date('M d, Y H:i'); ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Profile Updated</h6>
                                <p class="text-muted mb-0"><?php echo date('M d, Y'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                        </div>
                        <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        </div>
                        <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.initials {
    color: white;
    font-size: 2.5rem;
    font-weight: bold;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -1rem;
    top: 0;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
}

.timeline-content {
    padding-left: 1rem;
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.list-group-item {
    border: none;
    padding: 1rem 0;
}

.list-group-item:not(:last-child) {
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.btn-outline-primary {
    border-color: #667eea;
    color: #667eea;
}

.btn-outline-primary:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
}

.alert {
    border-radius: 10px;
    border: none;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    border-bottom: none;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.input-group-text {
    background: transparent;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}

.form-control:focus + .input-group-text {
    border-color: #ced4da;
}
</style>

<?php include 'includes/footer.php'; ?> 