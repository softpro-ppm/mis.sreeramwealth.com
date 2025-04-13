<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once "config/database.php";

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($result) > 0;
}

// Array of required tables
$required_tables = ['policies', 'clients', 'documents', 'health_insurance', 'life_insurance', 'general_insurance'];

// Check each table
foreach ($required_tables as $table) {
    if (!tableExists($conn, $table)) {
        die("Missing required table: $table");
    }
}

// Verify policies table structure
$result = mysqli_query($conn, "DESCRIBE policies");
if (!$result) {
    die("Error checking policies table structure: " . mysqli_error($conn));
}

$required_fields = [
    'id' => 'int',
    'policy_number' => 'varchar',
    'client_id' => 'int',
    'type' => 'varchar',
    'status' => 'varchar',
    'coverage_amount' => 'decimal',
    'premium' => 'decimal',
    'start_date' => 'date',
    'end_date' => 'date'
];

$missing_fields = [];
while ($row = mysqli_fetch_assoc($result)) {
    $field_name = $row['Field'];
    $field_type = strtolower($row['Type']);
    
    if (isset($required_fields[$field_name])) {
        if (strpos($field_type, $required_fields[$field_name]) === false) {
            $missing_fields[] = "$field_name (wrong type: $field_type, expected: {$required_fields[$field_name]})";
        }
        unset($required_fields[$field_name]);
    }
}

if (!empty($required_fields)) {
    $missing = implode(", ", array_keys($required_fields));
    die("Missing required fields in policies table: $missing");
}

// Test query
$test_query = "SELECT p.*, c.name as client_name 
               FROM policies p 
               JOIN clients c ON p.client_id = c.id 
               LIMIT 1";

$result = mysqli_query($conn, $test_query);
if (!$result) {
    die("Error testing query: " . mysqli_error($conn));
}

echo "Database structure verification completed successfully.";
?> 