<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

// Redirect to login page if not logged in
header("Location: login.php");
exit();
?> 