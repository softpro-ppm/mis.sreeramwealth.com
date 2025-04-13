<?php
// Initialize the session
session_start();
 
// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    
    // Check if email exists in database
    $sql = "SELECT id FROM clients WHERE email = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            // Return true if email doesn't exist (valid), false if it exists (invalid)
            echo mysqli_stmt_num_rows($stmt) === 0 ? "true" : "false";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
} else {
    header("location: clients.php");
    exit;
}
?> 