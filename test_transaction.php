<?php
// Set JSON content type header
header('Content-Type: application/json');

// Suppress warnings and notices
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once __DIR__ . '/helpers/functions.php';

try {
    // Test transaction functions
    begin_transaction('test_transaction');
    
    // Try to execute a simple query
    $conn->query("SELECT 1");
    
    // Create a savepoint
    begin_transaction('test_savepoint');
    
    // Try another query
    $conn->query("SELECT 2");
    
    // Commit the transaction
    commit_transaction();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction functions are working correctly'
    ]);
} catch (Exception $e) {
    // Rollback if there's an error
    if (isset($conn) && $conn->inTransaction()) {
        rollback_transaction('test_transaction');
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Transaction test failed: ' . $e->getMessage()
    ]);
} 