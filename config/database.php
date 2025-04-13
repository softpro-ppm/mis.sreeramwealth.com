<?php
/* Database credentials */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u820431346_swm');
define('DB_PASSWORD', 'JyLk]abVA9a$');
define('DB_NAME', 'u820431346_swm');

/* Attempt to connect to MySQL database */
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if(!$conn) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to ensure proper encoding
if (!mysqli_set_charset($conn, "utf8mb4")) {
    die("Error setting charset: " . mysqli_error($conn));
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?> 