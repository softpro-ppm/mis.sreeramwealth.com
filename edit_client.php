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
require_once "includes/utils.php";

// Define variables and initialize with empty values
$name = $email = $phone = $date_of_birth = $address = "";
$name_err = $email_err = $phone_err = $date_of_birth_err = $address_err = "";

// Check if client ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: clients.php");
    exit;
}

$client_id = $_GET['id'];

// Get existing client data
$sql = "SELECT * FROM clients WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $client = mysqli_fetch_assoc($result);
        
        if(!$client) {
            header("location: clients.php");
            exit;
        }
        
        // Populate variables with existing data
        $name = $client['name'];
        $email = $client['email'];
        $phone = $client['phone'];
        $date_of_birth = $client['date_of_birth'];
        $address = $client['address'];
    }
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
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
        // Check if email exists (excluding current client)
        $sql = "SELECT id FROM clients WHERE email = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $client_id);
            $param_email = trim($_POST["email"]);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
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
        $input_date = trim($_POST["date_of_birth"]);
        $formatted_date = formatDateForDB($input_date);
        if($formatted_date === null) {
            $date_of_birth_err = "Please enter a valid date in DD-MM-YYYY format.";
        } else {
            $date_of_birth = $formatted_date;
        }
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Check input errors before updating database
    if(empty($name_err) && empty($email_err) && empty($phone_err) && empty($date_of_birth_err) && empty($address_err)){
        
        // Prepare an update statement
        $sql = "UPDATE clients SET name=?, email=?, phone=?, date_of_birth=?, address=? WHERE id=?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssssi", $param_name, $param_email, $param_phone, $param_dob, $param_address, $param_id);
            
            // Set parameters
            $param_name = $name;
            $param_email = $email;
            $param_phone = $phone;
            $param_dob = $date_of_birth;
            $param_address = $address;
            $param_id = $client_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to clients page
                header("location: clients.php?status=updated");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Edit Client</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $client_id; ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($name); ?>" required>
                                    <?php if(!empty($name_err)): ?>
                                        <div class="invalid-feedback"><?php echo $name_err; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                    <?php if(!empty($email_err)): ?>
                                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($phone); ?>" required>
                                    <?php if(!empty($phone_err)): ?>
                                        <div class="invalid-feedback"><?php echo $phone_err; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="text" name="date_of_birth" class="form-control <?php echo (!empty($date_of_birth_err)) ? 'is-invalid' : ''; ?>" 
                                           value="<?php echo $date_of_birth ? formatDateDMY($date_of_birth) : ''; ?>" 
                                           placeholder="DD-MM-YYYY" required>
                                    <?php if(!empty($date_of_birth_err)): ?>
                                        <div class="invalid-feedback"><?php echo $date_of_birth_err; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" 
                                              rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                                    <?php if(!empty($address_err)): ?>
                                        <div class="invalid-feedback"><?php echo $address_err; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Update Client</button>
                            <a href="clients.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>