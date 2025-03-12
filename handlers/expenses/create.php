<?php
require_once __DIR__ . '/../../helpers/functions.php';

// Check if user is logged in
check_login();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: /ERC-POS/views/expenses/index.php');
    exit;
}

try {
    // Validate and sanitize input
    $description = trim($_POST['description'] ?? '');
    $expense_type = $_POST['expense_type'] ?? 'other';
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $supplier = trim($_POST['supplier'] ?? '');
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $multiple_items = isset($_POST['multiple_items']);
    $items_list = $multiple_items ? trim($_POST['items_list'] ?? '') : '';
    
    // Validate required fields
    if (empty($description)) {
        throw new Exception('Description is required');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than zero');
    }
    
    // Validate expense type
    $valid_expense_types = ['ingredient', 'utility', 'salary', 'rent', 'equipment', 'maintenance', 'other'];
    if (!in_array($expense_type, $valid_expense_types)) {
        throw new Exception('Invalid expense type');
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // If multiple items, format the notes to include the items list
    if ($multiple_items && !empty($items_list)) {
        // Add the items list to the notes
        $formatted_items = "ITEMS INCLUDED:\n" . $items_list;
        
        // Append to existing notes or set as notes
        if (!empty($notes)) {
            $notes .= "\n\n" . $formatted_items;
        } else {
            $notes = $formatted_items;
        }
    }
    
    // Insert expense
    $stmt = $conn->prepare("
        INSERT INTO expenses (
            description,
            expense_type,
            amount,
            expense_date,
            supplier,
            invoice_number,
            notes,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $description,
        $expense_type,
        $amount,
        $expense_date,
        $supplier,
        $invoice_number,
        $notes,
        $_SESSION['user_id']
    ]);
    
    $expense_id = $conn->lastInsertId();
    
    // Log the action
    log_audit(
        $_SESSION['user_id'],
        'create',
        'expenses',
        $expense_id,
        null,
        [
            'description' => $description,
            'expense_type' => $expense_type,
            'amount' => $amount,
            'expense_date' => $expense_date,
            'multiple_items' => $multiple_items ? 'Yes' : 'No'
        ]
    );
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = 'Expense added successfully';
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['error'] = 'Error adding expense: ' . $e->getMessage();
}

// Redirect back to expenses page
header('Location: /ERC-POS/views/expenses/index.php'); 