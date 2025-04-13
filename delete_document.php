<?php
// Initialize the session
session_start();
 
// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include config file
require_once "config/database.php";

// Check if document ID is provided
if(!isset($_POST['id']) || empty($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Document ID is required']);
    exit;
}

$document_id = intval($_POST['id']);

// Get document details first to delete the file
$sql = "SELECT file_name FROM documents WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $document_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if($doc = mysqli_fetch_assoc($result)) {
    // Delete the file
    $file_path = "uploads/policy_documents/" . $doc['file_name'];
    if(file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete the database record
    $sql = "DELETE FROM documents WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $document_id);
    
    if(mysqli_stmt_execute($stmt)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error deleting document']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Document not found']);
}

mysqli_close($conn);
?> 