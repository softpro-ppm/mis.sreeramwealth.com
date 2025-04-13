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
$policy_number = $client_id = $type = $start_date = $end_date = $premium = $coverage_amount = "";
$policy_number_err = $client_id_err = $type_err = $start_date_err = $end_date_err = $premium_err = $coverage_amount_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate policy number
    if(empty(trim($_POST["policy_number"]))){
        $policy_number = "POL" . date('Ymd') . rand(1000, 9999);
    } else{
        $policy_number = trim($_POST["policy_number"]);
    }
    
    // Validate client
    if(empty(trim($_POST["client_id"]))){
        $client_id_err = "Please select a client.";
    } else{
        $client_id = trim($_POST["client_id"]);
    }
    
    // Validate type
    if(empty(trim($_POST["type"]))){
        $type_err = "Please select a policy type.";
    } else{
        $type = trim($_POST["type"]);
    }
    
    // Validate dates
    if(empty(trim($_POST["start_date"]))){
        $start_date_err = "Please enter start date.";
    } else{
        $start_date = trim($_POST["start_date"]);
    }
    
    if(empty(trim($_POST["end_date"]))){
        $end_date_err = "Please enter end date.";
    } else{
        $end_date = trim($_POST["end_date"]);
    }
    
    // Validate premium
    if(empty(trim($_POST["premium"]))){
        $premium_err = "Please enter premium amount.";
    } else{
        $premium = trim($_POST["premium"]);
    }
    
    // Validate coverage amount
    if(empty(trim($_POST["coverage_amount"]))){
        $coverage_amount_err = "Please enter coverage amount.";
    } else{
        $coverage_amount = trim($_POST["coverage_amount"]);
    }
    
    // Check input errors before inserting in database
    if(empty($client_id_err) && empty($type_err) && empty($start_date_err) && empty($end_date_err) && empty($premium_err) && empty($coverage_amount_err)){
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert into policies table
            $sql = "INSERT INTO policies (policy_number, client_id, type, start_date, end_date, premium, coverage_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sisssdd", $policy_number, $client_id, $type, $start_date, $end_date, $premium, $coverage_amount);
                
                if(mysqli_stmt_execute($stmt)){
                    $policy_id = mysqli_insert_id($conn);
                    
                    // Insert into specific policy type table
                    switch($type) {
                        case 'health':
                            $sql = "INSERT INTO health_insurance_details (policy_id, coverage_type, pre_existing_conditions, family_coverage) VALUES (?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "isii", $policy_id, $_POST['health']['coverage_type'], $_POST['health']['pre_existing_conditions'], $_POST['health']['family_coverage']);
                            break;
                            
                        case 'life':
                            $sql = "INSERT INTO life_insurance_details (policy_id, term_years, beneficiaries) VALUES (?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "iis", $policy_id, $_POST['life']['term_years'], $_POST['life']['beneficiaries']);
                            break;
                            
                        case 'general':
                            $sql = "INSERT INTO general_insurance_details (policy_id, insurance_type, property_details) VALUES (?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "iss", $policy_id, $_POST['general']['insurance_type'], $_POST['general']['property_details']);
                            break;
                    }
                    
                    if(mysqli_stmt_execute($stmt)){
                        // Commit transaction
                        mysqli_commit($conn);
                        header("location: policies.php?status=added");
                        exit();
                    }
                }
            }
            
            // If we reach here, something went wrong
            throw new Exception("Error in policy creation");
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            echo "Something went wrong. Please try again later.";
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?> 