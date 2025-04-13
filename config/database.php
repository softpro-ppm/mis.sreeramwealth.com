<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u820431346_swm');
define('DB_PASSWORD', 'Metx@123');
define('DB_NAME', 'u820431346_swm');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?> 