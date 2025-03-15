<?php
require_once __DIR__ . '/../../helpers/functions.php';
check_login();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>
        alert('Invalid request method');
        window.location.href = '/ERC-POS/views/expenses/index.php';
    </script>";
    exit;
}

// Get POST data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$description = trim($_POST['description'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$expense_type = trim($_POST['expense_type'] ?? '');
$expense_date = trim($_POST['expense_date'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$invoice_number = trim($_POST['invoice_number'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (!$id || !$description || $amount <= 0 || !$expense_type || !$expense_date) {
    $_SESSION['error'] = 'Please fill in all required fields';
    echo "<script>
        window.location.href = '/ERC-POS/views/expenses/view.php?id=" . $id . "';
    </script>";
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Update expense
    $stmt = $conn->prepare("
        UPDATE expenses 
        SET 
            description = ?,
            amount = ?,
            expense_type = ?,
            expense_date = ?,
            supplier = ?,
            invoice_number = ?,
            notes = ?,
            updated_by = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $stmt->execute([
        $description,
        $amount,
        $expense_type,
        $expense_date,
        $supplier,
        $invoice_number,
        $notes,
        $_SESSION['user_id'], // Current user as updated_by
        $id
    ]);

    // Log the update in audit_log
    $stmt = $conn->prepare("
        INSERT INTO audit_log (
            user_id,
            action,
            table_name,
            record_id,
            old_values,
            new_values
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Get old values
    $old_values = json_encode([
        'description' => $_POST['old_description'] ?? '',
        'amount' => $_POST['old_amount'] ?? '',
        'expense_type' => $_POST['old_expense_type'] ?? '',
        'expense_date' => $_POST['old_expense_date'] ?? '',
        'supplier' => $_POST['old_supplier'] ?? '',
        'invoice_number' => $_POST['old_invoice_number'] ?? '',
        'notes' => $_POST['old_notes'] ?? ''
    ]);

    // Get new values
    $new_values = json_encode([
        'description' => $description,
        'amount' => $amount,
        'expense_type' => $expense_type,
        'expense_date' => $expense_date,
        'supplier' => $supplier,
        'invoice_number' => $invoice_number,
        'notes' => $notes
    ]);

    $stmt->execute([
        $_SESSION['user_id'],
        'update',
        'expenses',
        $id,
        $old_values,
        $new_values
    ]);

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = 'Expense updated successfully';
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = 'Error updating expense: ' . $e->getMessage();
}

// Redirect back to the expense view page using JavaScript
echo "<script>
    window.location.href = '/ERC-POS/views/expenses/view.php?id=" . $id . "';
</script>";
exit; 