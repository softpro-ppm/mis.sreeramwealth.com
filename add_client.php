<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config/database.php";

// Helper functions
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function formatDateForDB($date) {
    return date('Y-m-d', strtotime($date));
}

// Define variables and initialize with empty values
$name = $email = $phone = $date_of_birth = $address = "";
$name_err = $email_err = $phone_err = $date_of_birth_err = $address_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Log POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a name.";
    } else{
        $name = trim($_POST["name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else{
        // Check if email exists
        $sql = "SELECT id FROM clients WHERE email = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                error_log("Error checking email: " . mysqli_error($conn));
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Error preparing email check statement: " . mysqli_error($conn));
        }
    }
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter a phone number.";
    } else{
        $phone = trim($_POST["phone"]);
    }
    
    // Validate date of birth
    if(empty(trim($_POST["date_of_birth"]))){
        $date_of_birth_err = "Please enter date of birth.";
    } else{
        $date = trim($_POST["date_of_birth"]);
        if (!isValidDate($date)) {
            $date_of_birth_err = "Please enter a valid date.";
        } else {
            $date_of_birth = formatDateForDB($date);
        }
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Check input errors before inserting in database
    if(empty($name_err) && empty($email_err) && empty($phone_err) && empty($date_of_birth_err) && empty($address_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO clients (name, email, phone, date_of_birth, address) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_name, $param_email, $param_phone, $param_dob, $param_address);
            
            // Set parameters
            $param_name = $name;
            $param_email = $email;
            $param_phone = $phone;
            $param_dob = $date_of_birth;
            $param_address = $address;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to clients page
                header("location: clients.php?status=added");
                exit();
            } else{
                error_log("Error inserting client: " . mysqli_error($conn));
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            error_log("Error preparing insert statement: " . mysqli_error($conn));
        }
    } else {
        error_log("Validation errors: " . print_r([
            'name_err' => $name_err,
            'email_err' => $email_err,
            'phone_err' => $phone_err,
            'date_of_birth_err' => $date_of_birth_err,
            'address_err' => $address_err
        ], true));
    }
    
    // Close connection
    mysqli_close($conn);
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Client</h2>
        <a href="clients.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Clients
        </a>
    </div>

    <?php if(!empty($name_err) || !empty($email_err) || !empty($phone_err) || !empty($date_of_birth_err) || !empty($address_err)): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php if(!empty($name_err)) echo "<li>$name_err</li>"; ?>
            <?php if(!empty($email_err)) echo "<li>$email_err</li>"; ?>
            <?php if(!empty($phone_err)) echo "<li>$phone_err</li>"; ?>
            <?php if(!empty($date_of_birth_err)) echo "<li>$date_of_birth_err</li>"; ?>
            <?php if(!empty($address_err)) echo "<li>$address_err</li>"; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>">
                        <div class="invalid-feedback"><?php echo $phone_err; ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $date_of_birth; ?>">
                        <div class="invalid-feedback"><?php echo $date_of_birth_err; ?></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $address; ?></textarea>
                    <div class="invalid-feedback"><?php echo $address_err; ?></div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Add Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 